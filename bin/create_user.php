#!/usr/bin/php
<?php

/**
 * Script to create a user.
 */
require_once(__DIR__ . '/../phplib/411bootstrap.php');

// Set the site.
$site = null;
$sites = FOO\SiteFinder::getAll();
if(count($sites) == 1) {
    $site = $sites[0];
} else {
    $site = FOO\SiteFinder::getById((int)FOO\Util::prompt("Site ID"));
}
if(is_null($site)) {
    echo "Site not found\n";
    exit(1);
}
FOO\SiteFinder::setSite($site);

echo "Creating new user\n";

$newuser = new FOO\User();
$newuser['name'] = FOO\Util::prompt("Username");
$newuser['real_name'] = FOO\Util::prompt("Real name");
$newuser->setPassword(FOO\Util::prompt("Password"));
$newuser['email'] = FOO\Util::prompt("Email");
$newuser['admin'] = strtolower(FOO\Util::prompt("Admin (y/n)")) === 'y';
$newuser['api_key'] = FOO\Random::base64_bytes(FOO\User::API_KEY_LEN);
$newuser->store();

printf("\nUser created! ID: %d\n", $newuser['id']);
