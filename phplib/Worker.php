<?php namespace FOO;

/**
 * Class Worker
 * The 411 Worker. Runs jobs.
 * @package FOO
 */
class Worker {
    const LOG_NAMESPACE = '411_Worker';

    /**
     * Entrypoint to worker.
     */
    public function process() {
        Logger::info('Worker run', [], self::LOG_NAMESPACE);
        cli_set_process_title('411] Worker');
        print "[+] Worker\n";
        $timer = new Timer();
        $timer->start();

        $sites = SiteFinder::getAll();

        // Fork off a subprocess for each site.
        $pid_data = [];
        foreach($sites as $site) {
            $pid = pcntl_fork();
            if($pid === -1) {
                Logger::err('Fork failed', [], self::LOG_NAMESPACE);
                continue;
            }

            // This is the child. Launch the scheduler for this site.
            if($pid === 0) {
                $cmd = sprintf('%s/bin/worker.php', BASE_DIR);
                $args = ["--site={$site['id']}"];
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
                Logger::err('Worker error', ['site' => $site_id, 'ret' => $status], self::LOG_NAMESPACE);
            }
        }

        $timer->stop();
        Logger::info('Worker done', ['taken' => $timer->taken()], self::LOG_NAMESPACE);
        cli_set_process_title('411] Done');
    }

    /**
     * Entrypoint to run jobs.
     * @param Site $site The site object.
     * @param int $date The current date.
     */
    public function processSite($site, $date) {
        $base_title = sprintf('411] Time: %d Site: %d', $date, $site['id']);
        cli_set_process_title($base_title);
        print "[+] Worker: $date\n";
        $timer = new Timer();
        $timer->start();

        SiteFinder::setSite($site);
        $cfg = new DBConfig;

        if($cfg['worker_enabled']) {
            $ids = JobFinder::getPendingIds();
            printf("[+] Job count: %d\n", count($ids));

            // Grab a job from the database and run it!
            foreach($ids as $id) {
                $job = JobFinder::getAndLock($id, $date);
                // If we can't grab a lock for this job, skip it.
                if(is_null($job)) {
                    continue;
                }
                // If the job is unable to run, mark it failed.
                if(!$job->shouldRun($date)) {
                    $job['state'] = Job::ST_FAIL;
                    $job->store();
                    continue;
                }

                Hook::call('job.start');
                Logger::info('Execute job', ['id' => $job['id']], self::LOG_NAMESPACE);
                cli_set_process_title($base_title . ' Job: ' . $job['id']);
                printf("[+] Running job: %d, Type: %s, Target: %d\n", $id, $job['type'], $job['target_id']);
                $jobtimer = new Timer();
                $jobtimer->start();
                $errors = [];


                // Run the job and log whether it succeeded.
                // Jobs can fail in one of two ways:
                // - They can throw an exception.
                // - They can return a non-empty array of errors (and the error is not ignorable).
                $success = false;
                $retry = false;
                try {
                    list($data, $errors, $ignorable) = $job->run();
                    $job['state'] = Job::ST_SUCC;
                    $job['completion'] = 100;
                    $job->store();
                    if(count($errors) == 0 || $ignorable) {
                        $success = true;
                    } else {
                        $retry = $job->shouldRetry($date);
                    }
                } catch(JobCancelException $e) {
                    Logger::info('Job cancelled', ['id' => $job['id']], self::LOG_NAMESPACE);
                    Logger::except($e);
                    $errors[] = $e->getMessage();
                } catch(JobFailException $e) {
                    $retry = true;
                    Logger::info('Job failed', ['id' => $job['id']], self::LOG_NAMESPACE);
                    Logger::except($e);
                    $errors[] = $e->getMessage();
                } catch(\Exception $e) {
                    $retry = true;
                    Logger::info('Job failed', ['id' => $job['id']], self::LOG_NAMESPACE);
                    Logger::except($e);
                    $errors[] = $e->getMessage();
                }

                Hook::call('job.end', [$success]);

                // Extra processing after the job is done executing.
                try {
                    if($success) {
                        $job->onSuccess();
                    } else {
                        $job->onFail($errors);

                        if($retry) {
                            // Cancel the job if it's been attempted more than MAX_TRIES times.
                            $job['state'] = $job['tries'] > Job::MAX_TRIES ? Job::ST_CANC:Job::ST_FAIL;
                        } else {
                            $job['state'] = Job::ST_CANC;
                        }
                        $job->store();
                    }
                    $job->onFinish();
                } catch(\Exception $e) {
                    Logger::info('Job post processing failed', ['id' => $job['id']], self::LOG_NAMESPACE);
                    Logger::except($e);
                }

                $jobtimer->stop();
                Logger::info('Finish job', ['id' => $job['id'], 'success' => $success, 'taken' => $jobtimer->taken()], self::LOG_NAMESPACE);
            }
        } else {
            print("[+] Worker disabled\n");
        }

        $timer->stop();
        Logger::info('Worker done', ['taken' => $timer->taken()], self::LOG_NAMESPACE);
        cli_set_process_title('411] Done');
    }
}
