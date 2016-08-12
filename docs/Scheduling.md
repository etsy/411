Scheduling
==========

When you set up a new Search in 411, a number of things happen behind the scenes to automatically execute it. 411 uses a job-based execution system, which allows it to keep track of unsuccessful jobs and reschedule them.


Scheduler
---------

The 411 scheduler runs every minute and is responsible for generating jobs. Searches, rollups, weekly summaries are all scheduled via this mechanism. Splitting up job generation and execution helps improve the reliability of the system. Ex: A slow job won't stop jobs from being scheduled


Worker
------

The 411 worker runs after the scheduler and executes pending jobs. If a job runs successfully, the worker updates it and moves on. Things get slightly more interesting when a job errors. If the job failed in a recoverable way (JobFail), then the worker reschedules the job for 15 minutes in the future (up to 3 times). Otherwise, if the job can't be recovered (JobCancel), then the worker will give up on it.


### Search Job ###

Next, let's get into some specifics on how the Search job works.

- The Search is executed and generates a list of Alerts.
- The Alerts are passed through the list of registered Filters.
- The remaining Alerts are sent to the list of registered Targets.
- Alert emails (if the Search is configured for them) are sent out.

If the Search errors out, the job checks whether the Search `isTimeBased`. If so, the job is rescheduled for 15 minutes in the future (up to 3 times). Otherwise, the job is discarded.
