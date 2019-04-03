#!/usr/bin/env php
<?php

require_once('/app/phplib/411bootstrap.php');

$newsite = new FOO\Site();
$newsite['name'] = 'FourOneOne';
$newsite['host'] = 'fouroneone';
$newsite['secure'] = false;
$newsite->store();

FOO\SiteFinder::setSite($newsite);
$cfg = new FOO\DBConfig();
$cfg['cookie_secret'] = FOO\Random::base64_bytes(FOO\Cookie::SECRET_LEN);
$cfg['timezone'] = 'UTC';
$cfg['cron_enabled'] = 1;
$cfg['worker_enabled'] = 1;
$cfg['summary_enabled'] = 1;
$cfg['last_cron_date'] = 0;
$cfg['last_rollup_date'] = 0;
$cfg['error_email_enabled'] = 1;
$cfg['error_email_throttle'] = 30;
$cfg['from_email'] = 'alert@fouroneone';
$cfg['from_error_email'] = 'error@fouroneone';
$cfg['default_email'] = 'admin@example.com';

$newuser = new FOO\User();
$newuser['name'] = 'admin';
$newuser['real_name'] = 'Admin';
$newuser->setPassword('admin');
$newuser['email'] = 'admin@example.com';
$newuser['admin'] = true;
$newuser['api_key'] = FOO\Random::base64_bytes(FOO\User::API_KEY_LEN);
$newuser->store();
