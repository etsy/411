<?php

class AuthTest extends TestCase {
    public function testLogout() {
        TestHelper::becomeAdmin();
        FOO\Auth::logout();
        $this->assertFalse(FOO\Auth::isAuthenticated());
    }

    public function testLogin() {
        TestHelper::populateDB([
            [FOO\User::$TABLE, 1, 0, 'admin', 'Admin', password_hash('pass', PASSWORD_DEFAULT), 'test@test.com', 'UTC', true, '', '', 0, 0, 0],
        ]);

        $this->assertNull(FOO\Auth::login('admin', ''));

        $this->assertNotNull(FOO\Auth::login('admin', 'pass'));
    }

    public function testIsAuthenticated() {
        TestHelper::populateUsers();
        TestHelper::becomeUser();
        $this->assertTrue(FOO\Auth::isAuthenticated());

        TestHelper::becomeAdmin();
        $this->assertTrue(FOO\Auth::isAuthenticated());
    }

    public function testIsAdmin() {
        TestHelper::populateUsers();
        TestHelper::becomeUser();
        $this->assertFalse(FOO\Auth::isAdmin());

        TestHelper::becomeAdmin();
        $this->assertTrue(FOO\Auth::isAdmin());
    }
}
