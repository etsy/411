#!/usr/bin/php
<?php

/**
 * Entrypoint for scheduling various 411 jobs.
 */

require_once(__DIR__ . '/../phplib/411bootstrap.php');

$args = getopt('h', [
    'help',
    'backfill',
    'date:',
    'site:',
]);

// Syntax checks
if(
    $args === false ||
    FOO\Util::exists($args, 'h') ||
    FOO\Util::exists($args, 'help')
) {
    print "Usage: cron.php [--backfill] [--date=date] [--site=site_id]\n";
    exit(0);
}

// Determine the start time of this run.
$time = $_SERVER['REQUEST_TIME'] - 10;
if(FOO\Util::exists($args, 'date')) {
    $time = (int) $args['date'];
}

$backfill = FOO\Util::exists($args, 'backfill');

$sch = new FOO\Scheduler;

// Process a specific site, if one was specified. Otherwise, process all of them.
if(FOO\Util::exists($args, 'site')) {
    $site = FOO\SiteFinder::getById((int) FOO\Util::get($args, 'site'));
    if(is_null($site)) {
        print "Unable to find site\n";
        exit(1);
    }

    $sch->processSite($site, $time, $backfill);
} else {
    $sch->process($time, $backfill);
}
exit(0);
