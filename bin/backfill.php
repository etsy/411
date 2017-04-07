#!/usr/bin/env php
<?php

/**
 * Entrypoint for backfilling results.
 * Useful if 411 fails catastrophically.
 */

require_once(__DIR__ . '/../phplib/411bootstrap.php');

// Arg parsing.
if($argc < 3) {
    print "Usage: backfill.php start_date end_date [max_jobs]\n";
    exit(0);
}

$start_date = (int) $argv[1];
$end_date = (int) $argv[2];
$max_jobs = 2;

if($argc > 3) {
    $max_jobs = (int) $argv[3];
}

if($max_jobs < 1) {
    print "[-] max_jobs must be at least 1\n";
    exit(1);
}

$sch = new FOO\Scheduler;
$sch->backfill($start_date, $end_date, $max_jobs);
