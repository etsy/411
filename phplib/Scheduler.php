<?php

namespace FOO;

/**
 * Class Scheduler
 * The 411 Scheduler. Responsible for scheduling a variety of jobs and doing some maintenance tasks.
 * @package FOO
 */
class Scheduler {
    const LOG_NAMESPACE = '411_Scheduler';

    /**
     * Entrypoint to scheduler.
     * @param int $date The current date.
     * @param bool $backfill Whether we're attempting to backfill this point in time.
     */
    public function process($date, $backfill) {
        Logger::info('Scheduler run', ['time' => $date], self::LOG_NAMESPACE);
        cli_set_process_title('411] Scheduler');
        print "[+] Scheduler: $date\n";
        $timer = new Timer();
        $timer->start();

        $sites = SiteFinder::getAll();

        // Fork off a subprocess for each site.
        $pid_data = [];
        foreach($sites as $site) {
            $pid = pcntl_fork();
            if($pid === -1) {
                Logger::err('Fork failed', ['time' => $date], self::LOG_NAMESPACE);
                print "[-] Error: $date\n";
                continue;
            }

            // This is the child. Launch the scheduler for this site.
            if($pid === 0) {
                $cmd = sprintf('%s/bin/cron.php', BASE_DIR);
                $args = ["--date=$date", "--site={$site['id']}"];
                if($backfill) {
                    $args[] = '--backfill';
                }
                pcntl_exec($cmd, $args, $_ENV);
                exit(1);
            // This is the parent. Keep track of the child.
            } else {
                $pid_data[$pid] = $site['id'];
            }
        }
        // Wait for all subprocesses to finish. Log if there was a non-zero return code.
        foreach($pid_data as $pid=>$site_id) {
            pcntl_waitpid($pid, $status);
            if($status != 0) {
                Logger::err('Scheduler error', ['site' => $site_id, 'ret' => $status], self::LOG_NAMESPACE);
            }
        }

        $timer->stop();
        Logger::info('Scheduler done', ['taken' => $timer->taken()], self::LOG_NAMESPACE);
        cli_set_process_title('411] Done');
    }

    /**
     * Entrypoint to backfill Searches.
     * @param int $start_date The starting timestamp.
     * @param int $end_date The ending timestamp.
     * @param int $max_jobs The maximum number of jobs to run simultaneously.
     */
    public function backfill($start_date, $end_date, $max_jobs=5) {
        Logger::info('Scheduler backfill run', ['start' => $start_date, 'end' => $end_date], self::LOG_NAMESPACE);
        cli_set_process_title('411] Backfill');
        print "[+] Backfill: $start_date to $end_date\n";
        $timer = new Timer();
        $timer->start();

        $curr_date = $start_date;
        $pid_data = [];

        while($curr_date < $end_date) {
            // Keep spawning jobs till we hit the limit.
            $pid = pcntl_fork();
            if($pid === -1) {
                Logger::err('Fork failed', ['start_time' => $start_date, 'end_time' => $end_date], self::LOG_NAMESPACE);
                $curr_date += 60;
                continue;
            }

            if($pid === 0) {
                Logger::info('Process backfill', ['date' => $curr_date], self::LOG_NAMESPACE);
                $cmd = sprintf('%s/bin/cron.php', BASE_DIR);
                pcntl_exec($cmd, ["--backfill", "--date=$curr_date"], $_ENV);
                exit(1);
            } else {
                $pid_data[$pid] = $curr_date;
            }
            $curr_date += 60;

            // If we've run out of job slots, wait for one to open up.
            if(count($pid_data) >= $max_jobs) {
                $pid = pcntl_wait($status);
                if($status != 0) {
                    Logger::err('Scheduler backfill error', ['date' => $pid_data[$pid], 'ret' => $status], self::LOG_NAMESPACE);
                    unset($pid_data[$pid]);
                }
            }
        }

        // Wait for all subprocesses to finish. Log if there was a non-zero return code.
        foreach($pid_data as $pid=>$date) {
            pcntl_waitpid($pid, $status);
            if($status != 0) {
                Logger::err('Scheduler backfill error', ['date' => $date, 'ret' => $status], self::LOG_NAMESPACE);
            }
        }

        $timer->stop();
        Logger::info('Scheduler backfill done', ['taken' => $timer->taken()], self::LOG_NAMESPACE);
        cli_set_process_title('411] Done');
    }

    /**
     * Process a single site.
     * @param Site $site The site.
     * @param int $date The current date.
     * @param bool $backfill Whether we're attempting to backfill this point in time.
     */
    public function processSite($site, $date, $backfill) {
        Logger::info('Process site', ['id' => $site['id'], 'date' => $date, 'backfill' => $backfill], self::LOG_NAMESPACE);
        $base_title = sprintf('411] Time: %d Site: %d', $date, $site['id']);
        cli_set_process_title($base_title);
        $timer = new Timer();
        $timer->start();

        SiteFinder::setSite($site);
        $cfg = new DBConfig;
        $meta = new DBMeta;

        if($cfg['cron_enabled']) {
            print("[+] Maintenance\n");
            cli_set_process_title($base_title . ' Maintenance');
            $this->maintenance($date);

            print("[+] Search Health\n");
            cli_set_process_title($base_title . ' Search Health');
            $search_health = $this->health($date);

            print("[+] Rollups\n");
            cli_set_process_title($base_title . ' Rollups');
            $this->processRollups($date);

            print("[+] Searches\n");
            cli_set_process_title($base_title . ' Searches');
            $this->processSearches($date, $search_health, $backfill);

            if($cfg['summary_enabled']) {
                print("[+] Summary\n");
                cli_set_process_title($base_title . ' Summary');
                $this->processSummary($date, $backfill);
            }

            print("[+] Autoclose\n");
            cli_set_process_title($base_title . ' Autoclose');
            $this->processAutoclose($date, $backfill);

            print("[+] Cleanup\n");
            cli_set_process_title($base_title . ' Autoclose');
            $this->processCleanup($date);

            if(!$backfill) {
                $meta['last_cron_date'] = $date;
            }
        } else {
            print("[+] Scheduler disabled\n");
        }

        $timer->stop();
        Logger::info('Site done', ['taken' => $timer->taken(), 'cron' => !empty($cfg['cron_enabled'])], self::LOG_NAMESPACE);
        cli_set_process_title($base_title . ' Done');
    }

    /**
     * Schedule rollups.
     * @param int $date The current date.
     */
    private function processRollups($date) {
        // Schedule hourly rollup, if necessary.
        $lastjob = JobFinder::getLastByQuery(['type' => Rollup_Job::$TYPE, 'target_id' => Rollup_Job::I_HOURLY]);
        if(is_null($lastjob) || $date - $lastjob['target_date'] >= 1 * 60 * 60 - 5) {
            $rollupjob = new Rollup_Job();
            $rollupjob['target_id'] = Rollup_Job::I_HOURLY;
            $rollupjob['target_date'] = $date;
            $rollupjob->store();

            Logger::info('Schedule hourly rollup', ['job_id' => $rollupjob['id']], self::LOG_NAMESPACE);
        }

        // Schedule daily rollup, if necessary.
        $lastjob = JobFinder::getLastByQuery(['type' => Rollup_Job::$TYPE, 'target_id' => Rollup_Job::I_DAILY]);
        if(is_null($lastjob) || $date - $lastjob['target_date'] >= 24 * 60 * 60 - 5) {
            $rollupjob = new Rollup_Job();
            $rollupjob['target_id'] = Rollup_Job::I_DAILY;
            $rollupjob['target_date'] = $date;
            $rollupjob->store();

            Logger::info('Schedule daily rollup', ['job_id' => $rollupjob['id']], self::LOG_NAMESPACE);
        }
    }

    /**
     * Schedule Searches.
     * @param int $date The current date.
     * @param array $search_health The health information.
     * @param bool $backfill Whether we're attempting to backfill this point in time.
     */
    private function processSearches($date, $search_health, $backfill) {
        foreach(SearchFinder::getByQuery(['enabled' => 1]) as $search) {
            // If the search doesn't need to run OR
            // the search type is failing and isn't time based
            // skip it!
            $key = $search::$TYPE;
            if(!is_null($search::getSources())) {
                $key = sprintf('%s[%s]', $search::$TYPE, $search['source']);
            }
            if(
                !$search->shouldRun($date, $backfill) ||
                (!Util::get($search_health, $key, false) && !$search->isTimeBased())
            ) {
                continue;
            }

            $searchjob = new Search_Job();
            $searchjob['target_id'] = $search['id'];
            $searchjob['target_date'] = $date;
            $searchjob->store();

            Logger::info('Schedule search', ['id' => $search['id'], 'job_id' => $searchjob['id']], self::LOG_NAMESPACE);
        }
    }

    /**
     * Schedule weekly summary.
     * @param int $date The current timestamp.
     * @param bool $backfill Whether we're attempting to backfill this point in time.
     */
    private function processSummary($date, $backfill) {
        $dt = new \DateTime("@$date");
        $w = (int) $dt->format('N');
        $h = (int) $dt->format('H');
        $m = (int) $dt->format('i');

        // Run at the start of each week. (Monday morning)
        if($w == 1 && $h == 0 && $m == 0) {
            $summaryjob = new Summary_Job();
            $summaryjob['target_date'] = $date;
            $summaryjob->store();
        }
    }

    /**
     * Schedule autoclose job.
     * @param int $date The current timestamp.
     * @param bool $backfill Whether we're attempting to backfill this point in time.
     */
    private function processAutoclose($date, $backfill) {
        // Run hourly.
        $lastjob = JobFinder::getLastByQuery(['type' => Autoclose_Job::$TYPE]);
        if(is_null($lastjob) || $date - $lastjob['target_date'] >= 1 * 60 * 60 - 5) {
            $autoclosejob = new Autoclose_Job();
            $autoclosejob['target_date'] = $date;
            $autoclosejob->store();
        }
    }

    /**
     * Schedule cleanup job.
     * @param int $date The current timestamp.
     */
    private function processCleanup($date) {
        // Run daily.
        $lastjob = JobFinder::getLastByQuery(['type' => Cleanup_Job::$TYPE]);
        if(is_null($lastjob) || $date - $lastjob['target_date'] >= 24 * 60 * 60 - 5) {
            $cleanupjob = new Cleanup_Job();
            $cleanupjob['target_date'] = $date;
            $cleanupjob->store();
        }
    }

    /**
     * Execute some maintenance tasks.
     * @param int $date The current date.
     */
    private function maintenance($date) {
        // Archive expired Filters and Targets.
        FilterFinder::reap($date);
        TargetFinder::reap($date);

        // Fail stalled Jobs.
        JobFinder::fail($date);
    }

    /**
     * Update and return Search type health information.
     * @param int $date The current date.
     * @return array Health information.
     */
    private function health($date) {
        $search_health = [];

        $cfg = new DBConfig;
        $meta = new DBMeta;

        // Collect health information for all Search types.
        $search_health = (new Health_REST)->getSearchHealth();
        foreach($search_health as $type=>$working) {
            // Send an email if the state has changed.
            $last_status = (bool) Util::get($meta, "search_$type", true);
            if($last_status !== $working) {
                $meta["search_$type"] = $working;
                if($cfg['error_email_enabled']) {
                    if($working) {
                        Notification::sendSearchTypeRecoveryEmail($cfg['default_email'], $type);
                    } else {
                        Notification::sendSearchTypeErrorEmail($cfg['default_email'], $type);
                    }
                }
            }
        }

        return $search_health;
    }
}
