<?php

class TestHelper {
    public static function setupDB() {
        FOO\DB::connect('sqlite::memory:', null, null);

        $schema_file = __DIR__ . '/../db.sql';
        $stmts = explode(';', file_get_contents($schema_file));
        foreach($stmts as $stmt) {
            $stmt = trim($stmt);
            if(strlen($stmt) == 0) {
                continue;
            }
            FOO\DB::query($stmt);
        }
        FOO\Cookie::setWrite(false);
        FOO\SiteFinder::clearSite();
    }

    public static function teardownDB() {
        FOO\Auth::setUserId(0);
        FOO\Cookie::setWrite(true);
        FOO\DB::disconnect();
    }

    public static function populateDB($arr) {
        foreach($arr as $row) {
            $sql = sprintf('INSERT INTO `%s` VALUES %s', $row[0], FOO\DB::inPlaceholder(count($row) - 1));
            FOO\DB::query($sql, array_slice($row, 1), FOO\DB::CNT);
        }
    }

    public static function populateUsers() {
        self::populateDB([
            [FOO\User::$TABLE, 1, 0, 'admin', 'Admin', '', 'test@test.com', true, '', 0, 0, 0],
            [FOO\User::$TABLE, 2, 0, 'user', 'User', '', 'test@test.com', false, '', 0, 0, 0],
        ]);
    }

    public static function becomeAdmin() {
        FOO\Auth::setUserId(1);
    }

    public static function becomeUser() {
        FOO\Auth::setUserId(2);
    }

    public static function populateSite() {
        self::populateDB([
            [FOO\Site::$TABLE, 1, 'FOO', '411', 0, 0, 0],
        ]);
    }

    public static function enableSite() {
        FOO\SiteFinder::setSite(FOO\Sitefinder::getById(1));
    }

    public static function invokeMethod(&$object, $methodName, array $parameters=[]) {
        $reflection = new \ReflectionClass(is_string($object) ? $object:get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($method->isStatic() ? null:$object, $parameters);
    }
}
