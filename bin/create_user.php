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
$newuser['password'] = password_hash(FOO\Util::prompt("Password"), PASSWORD_DEFAULT);
$newuser['email'] = FOO\Util::prompt("Email");
$newuser['admin'] = strtolower(FOO\Util::prompt("Admin (y/n)")) === 'y';
$newuser->store();

printf("\nUser created! ID: %d\n", $newuser['id']);
