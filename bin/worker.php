#!/usr/bin/php
<?php

/**
 * Entrypoint for the job execution process.
 */
require_once(__DIR__ . '/../phplib/411bootstrap.php');

$args = getopt('h', [
    'help',
    'site:',
]);

// Syntax checks
$site_set = FOO\Util::exists($args, 'site');
if(
    $args === false ||
    FOO\Util::exists($args, 'h') ||
    FOO\Util::exists($args, 'help')
) {
    print "Usage: worker.php [--site=site_id]\n";
    exit(0);
}

$work = new FOO\Worker;

if(FOO\Util::exists($args, 'site')) {
    $site = FOO\SiteFinder::getById((int) FOO\Util::get($args, 'site'));
    if(is_null($site)) {
        print "Unable to find site\n";
        exit(1);
    }

    $work->processSite($site, $_SERVER['REQUEST_TIME']);
} else {
    $work->process();
}
exit(0);
