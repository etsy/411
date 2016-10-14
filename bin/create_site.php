#!/usr/bin/php
<?php

/**
 * Script to create a new site.
 */
require_once(__DIR__ . '/../phplib/411bootstrap.php');

echo "Creating new site\n";

$newsite = new FOO\Site();
$newsite['name'] = FOO\Util::prompt("Site name");
$newsite['host'] = FOO\Util::prompt("Hostname");
$newsite->store();

FOO\SiteFinder::setSite($newsite);
$cfg = new FOO\DBConfig();
$cfg['cookie_secret'] = FOO\Random::base64_bytes(FOO\Cookie::SECRET_LEN);
$cfg['cron_enabled'] = 1;
$cfg['worker_enabled'] = 1;
$cfg['summary_enabled'] = 1;
$cfg['last_cron_date'] = 0;
$cfg['last_rollup_date'] = 0;
$cfg['error_email_enabled'] = 1;
$cfg['error_email_throttle'] = 30;
$cfg['from_email'] = FOO\Util::prompt('From email');
$cfg['from_error_email'] = FOO\Util::prompt('From Error email');
$cfg['default_email'] = FOO\Util::prompt('Default To email');

printf("\nSite created! ID: %d\n", $newsite['id']);
