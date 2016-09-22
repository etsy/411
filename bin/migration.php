#!/usr/bin/php
<?php

/**
 * DB migration script.
 */
require_once(__DIR__ . '/../phplib/411bootstrap.php');

define('VER_FN', __DIR__ . '/../version.txt');

function ver_cmp($a, $b) {
    $ar = array_map('intval', explode('.', $a));
    $br = array_map('intval', explode('.', $b));

    // Compare each part of the version string.
    for($i = 0; $i < 3; ++$i) {
        $d = $ar[$i] - $br[$i];
        if($d != 0) {
            return $d;
        }
    }
    return 0;
}

$old_ver = VERSION;
// Load in the current version from the version file.
if(file_exists(VER_FN)) {
    $ver = trim(@file_get_contents(VER_FN));
    if($ver !== false) {
        $old_ver = $ver;
    }
}

printf("Migrating from %s to %s\n", $old_ver, VERSION);

/**
 * Migration logic
 */

if(ver_cmp($old_ver, '1.1.0') < 0) {
    // Add indices to speed up common queries.
    FOO\DB::query('CREATE INDEX `jobs_target_date_idx` ON `jobs`(`target_date`)');
    FOO\DB::query('CREATE INDEX `type_target_id_site_id_archived_idx` ON `jobs`(type, target_id, site_id, archived)');
    FOO\DB::query('CREATE INDEX `jobs_type_target_id_site_id_archived_idx` ON `jobs`(type, target_id, site_id, archived)');

    // Add new Alert fields and update.
    FOO\DB::query('ALTER TABLE `alerts` ADD COLUMN `type` VARCHAR(64) NOT NULL DEFAULT ""');
    FOO\DB::query('ALTER TABLE `alerts` ADD COLUMN `name` VARCHAR(255) NOT NULL DEFAULT ""');
    FOO\DB::query('ALTER TABLE `alerts` ADD COLUMN `category` VARCHAR(64) NOT NULL DEFAULT "general"');
    FOO\DB::query('ALTER TABLE `alerts` ADD COLUMN `tags` VARCHAR(255) NOT NULL DEFAULT ""');
    FOO\DB::query('ALTER TABLE `alerts` ADD COLUMN `priority` UNSIGNED INTEGER NOT NULL DEFAULT 0');
    FOO\DB::query('CREATE INDEX `alerts_type_idx` ON `alerts`(`type`)');
    foreach(FOO\DB::query('SELECT * FROM `searches`') as $search_data) {
        FOO\DB::query('UPDATE `alerts` SET `type`=?, `name`=?, `category`=?, `tags`=?, `priority`=? WHERE `site_id`=? AND `search_id`=?', [
            $search_data['type'], $search_data['name'], $search_data['category'], $search_data['tags'], $search_data['priority'],
            $search_data['site_id'], $search_data['search_id']
        ]);
    }
}

/**
 * Migration logic
 */

file_put_contents(VER_FN, VERSION);

print "Migration complete!\n";
