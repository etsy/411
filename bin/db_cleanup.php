#!/usr/bin/env php
<?php

/**
 * A script to remove any archived data from the database.
 */
require_once(__DIR__ . '/../phplib/411bootstrap.php');

$tables = [
    FOO\Alert::$TABLE,
    FOO\Filter::$TABLE,
    FOO\Group::$TABLE,
    FOO\GroupTarget::$TABLE,
    FOO\SList::$TABLE,
    FOO\Site::$TABLE,
    FOO\Job::$TABLE,
    FOO\Search::$TABLE,
    FOO\SearchLog::$TABLE,
    FOO\Report::$TABLE,
    FOO\ReportTarget::$TABLE,
    FOO\Target::$TABLE,
    FOO\User::$TABLE,
];

foreach($tables as $table) {
    print "Cleaning up '$table': ";
    $count = FOO\DB::query(sprintf('DELETE FROM `%s` WHERE `archived` = 1', $table), [], FOO\DB::CNT);
    print "$count\n";
}
