#!/usr/bin/php
<?php

/**
 * Entrypoint for syncing Alerts in ES with db.
 */

require_once(__DIR__ . '/../phplib/411bootstrap.php');

$args = getopt('h', [
    'help',
    'site::',
]);

// Syntax checks
$site_set = FOO\Util::exists($args, 'site');
if(
    $args === false ||
    FOO\Util::exists($args, 'h') ||
    FOO\Util::exists($args, 'help') ||
    !FOO\Util::exists($args, 'site')
) {
    print "Usage: es_sync.php --site=site_id\n";
    exit(0);
}

$site = FOO\SiteFinder::getById((int) FOO\Util::get($args, 'site'));
if(is_null($site)) {
    print "Unable to find site\n";
    exit(1);
}

FOO\SiteFinder::setSite($site);

$job = new FOO\Sync_Job();
$job['target_date'] = $_SERVER['REQUEST_TIME'];
$job->run();

exit(0);
