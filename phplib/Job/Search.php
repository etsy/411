<?php

namespace FOO;

/**
 * Class Search_Job
 * Represents a scheduled execution of a Search.
 * @package FOO
 */
class Search_Job extends Job {
    public static $TYPE = 'search';

    public function shouldRetry($date) {
        $search = SearchFinder::getById($this->obj['target_id']);
        return $search && $search->isTimeBased();
    }

    /**
     * Process a single Search.
     * @param bool $commit Whether to save Alerts.
     * @return array An array of Alerts, array of errors and whether failures are ignorable.
     */
    public function run($commit=true) {
        $search = SearchFinder::getById($this->obj['target_id']);
        if(is_null($search)) {
            throw new JobCancelException(sprintf("Search doesn't exist: %d", $this->obj['target_id']));
        }

        return $this->_run($commit, $search);
    }

    /**
     * Process a single Search.
     * @param bool $commit Whether to save Alerts.
     * @param Search $search The Search object.
     * @return array An array of Alerts, array of errors and whether failures are ignorable.
     */
    public function _run($commit, Search $search) {
        $alerts = [];
        $errors = [];

        // Don't allow saving Alerts if the Search isn't in the DB.
        if($search->isNew()) {
            $commit = false;
        }

        // Whether to update the Search. We only want to do this when the current SearchJob is the newest one available.
        $job = JobFinder::getLastByQuery(['type' => Search_Job::$TYPE, 'target_id' => $search['id']]);
        $search_commit = $commit && (is_null($job) || $this->obj['last_execution_date'] >= $job['target_date']);
        if($search_commit) {
            $search['last_status'] = '';
        }

        // Attempt to run the Search. If it fails, we note the error and continue.
        $search_success = false;
        try {
            $alerts = $search->execute($this->obj['target_date']);
            $search_success = true;
        } catch(SearchException $e) {
            $errors[] = sprintf("SearchException: %s", $e->getMessage());
            Logger::except($e);
        } catch(\Exception $e) {
            $errors[] = sprintf("Catch all: %s", $e->getMessage());
            Logger::except($e);
        }

        $this->setCompletion(40);

        $filters = $search->getFilters();
        $targets = $search->getTargets();

        // Take each Alert result and pass it through the pipeline.
        list($final_alerts, $new_errors) = $this->processFilters($alerts, $filters, $this->obj['target_date']);
        $errors = array_merge($errors, $new_errors);
        if($commit) {
            $new_errors = $this->processTargets($final_alerts, $targets, $this->obj['target_date']);
            $errors = array_merge($errors, $new_errors);
        }

        $this->setCompletion(60);

        $prev_success = $search['last_success_date'] === $search['last_execution_date'];
        $curr_success = count($errors) === 0;

        // Email logic.
        if($commit) {
            $cfg = new DBConfig();
            $is_flapping = $search['flap_rate'] > Search::FLAP_THRES;

            if($cfg['error_email_enabled'] && !$is_flapping) {
                $to = $search->getEmails();

                if(!$curr_success && $search['last_error_email_date'] + $cfg['error_email_throttle'] * 60 <= $this->obj['target_date']) {
                    // Update the last_error_email_date field only when $search_commit is set.
                    // This effectively means that emails generated from running the Search via the frontend won't
                    // affect the throttle. We'll save this to the database in a bit.
                    if($search_commit) {
                        $search['last_error_email_date'] = $this->obj['target_date'];
                    }

                    Notification::sendSearchErrorEmail($to, $search, $errors, $this->getDebugData());
                }

                // Send recovery email if state changed from failure to success.
                if(!$prev_success && $curr_success) {
                    Notification::sendSearchRecoveryEmail($to, $search, $this->getDebugData());
                }
            }
        }

        $this->setCompletion(80);

        // Send Alerts email if configured for ondemand notifications and there are Alerts.
        if($commit) {
            if(count($final_alerts) > 0 && $search['notif_type'] == Search::NT_ONDEMAND) {
                Notification::sendAlertEmail(
                    $search->getEmails(),
                    $search,
                    $final_alerts,
                    $search['notif_format'] == Search::NF_CONTENTONLY,
                    $this->getDebugData()
                );
            }
        }

        $this->setCompletion(90);

        // Update the last execution of this search, if it's not new.
        if($search_commit) {
            $search['last_execution_date'] = $this->obj['target_date'];

            // Track last success/failure date.
            if($curr_success) {
                $search['last_status'] = $search['last_status'] ?: sprintf('%d Alerts generated', count($alerts));
                $search['last_success_date'] = $this->obj['target_date'];
            } else {
                $search['last_status'] = implode("\n", $errors);
                $search['last_failure_date'] = $this->obj['target_date'];
            }

            // Track how often the state of this Search changes.
            $search['flap_rate'] = $search['flap_rate'] * (1 - Search::FLAP_WEIGHT) + ($prev_success != $curr_success) * Search::FLAP_WEIGHT;

            $search->store();
        }

        // Record any errors.
        if(!$curr_success) {
            Logger::err('Search error', ['id' => $search['id'], 'job_id' => $this->obj['job_id'], 'ignorable' => $search_success, 'errors' => $errors], self::LOG_NAMESPACE);
        }

        return [$final_alerts, $errors, $search_success];
    }

    public function shouldRun($date) {
        $meta = new DBMeta;
        return (bool) Util::get($meta, sprintf("search_%s", static::$TYPE), true);
    }

    /**
     * Process and Finalize all Filters.
     * @param Alert[] $alerts The Alert object to process.
     * @param Filter[] $filters The list of Filters to use.
     * @param int $date The current date.
     * @return array An array of Alerts and errors.
     */
    private function processFilters(array $alerts, array $filters, $date) {
        $errors = [];

        foreach($filters as $filter) {
            $new_alerts = [];
            // Call process on each Filter.
            foreach($alerts as $alert) {
                try {
                    $new_alerts = array_merge($new_alerts, $filter->process($alert, $date));
                } catch(\Exception $e) {
                    $new_alerts[] = $alert;
                    Logger::err('Filter exception: process', ['exception' => $e->getMessage(), 'filter_id' => $filter['id']], self::LOG_NAMESPACE);
                    $errors[] = sprintf('Filter %s: %s', $filter['type'], $e->getMessage());
                }
            }

            // Call finalize on each Filter.
            try {
                $alerts = array_merge($new_alerts, $filter->finalize($date));
            } catch(\Exception $e) {
                $alerts = $new_alerts;
                Logger::err('Filter exception: finalize', ['exception' => $e->getMessage(), 'filter_id' => $filter['id']], self::LOG_NAMESPACE);
                $errors[] = sprintf('Filter %s: %s', $filter['type'], $e->getMessage());
            }
        }

        return [$alerts, $errors];
    }

    /**
     * Process and Finalize all Targets.
     * @param Alert[] $alerts The Alerts to process.
     * @param Target[] $targets The list of Targets to use.
     * @param int $date The current date.
     * @return string[] An array of errors.
     */
    private function processTargets(array $alerts, array $targets, $date) {
        $errors = [];
        foreach($targets as $target) {
            // Call process on each Target -> Alert.
            foreach($alerts as $alert) {
                try {
                    $target->process($alert, $date);
                } catch(\Exception $e) {
                    Logger::err('Target exception: process', ['exception' => $e->getMessage(), 'target_id' => $target['id']], self::LOG_NAMESPACE);
                    $errors[] = sprintf('Target %s: %s', $target['type'], $e->getMessage());
                }
            }

            // Call finalize on each Target.
            try {
                $target->finalize($date);
            } catch(\Exception $e) {
                Logger::err('Target exception: finalize', ['exception' => $e->getMessage(), 'target_id' => $target['id']], self::LOG_NAMESPACE);
                $errors[] = sprintf('Target %s: %s', $target['type'], $e->getMessage());
            }
        }
        return $errors;
    }
}
