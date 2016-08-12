<?php

class UserTest extends DBTestCase {
    public function testFinderGetByName() {
        TestHelper::populateDB([
            [FOO\User::$TABLE, 1, 0, 'user', 'User', '', 'user@test.com', false, '', 0, 0, 0]
        ]);

        $this->assertSame(1, FOO\UserFinder::getByName('user')['id']);
    }
}
