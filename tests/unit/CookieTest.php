<?php

class CookieTest extends DBTestCase {
    /**
     * @expectedException FOO\CookieException
     */
    public function testKeyLength() {
        TestHelper::populateSite();
        TestHelper::enableSite();

        TestHelper::populateDB([
            [FOO\DBConfig::$TABLE, 1, 'cookie_secret', 'x'],
        ]);

        $_COOKIE[FOO\Cookie::COOKIE_KEY] = 'xx' . FOO\Cookie::SEP . 'yy';

        FOO\Cookie::read();
    }

    public function testRead() {
        $_COOKIE[FOO\Cookie::COOKIE_KEY] = 'xx';
        FOO\Cookie::read();
        $this->assertSame([], FOO\Cookie::getAll());

        $_COOKIE[FOO\Cookie::COOKIE_KEY] = 'xx' . FOO\Cookie::SEP . 'yy';
        FOO\Cookie::read();
        $this->assertSame([], FOO\Cookie::getAll());

        $_COOKIE[FOO\Cookie::COOKIE_KEY] = FOO\Cookie::generate(['x' => 'y'], 0);
        FOO\Cookie::read();
        $this->assertSame([], FOO\Cookie::getAll());
    }

    public function testDirty() {
        $_COOKIE[FOO\Cookie::COOKIE_KEY] = '';
        FOO\Cookie::read();
        $this->assertTrue(FOO\Cookie::isDirty());

        FOO\Cookie::write();
        $this->assertFalse(FOO\Cookie::isDirty());

        FOO\Cookie::set('x', 'y');
        $this->assertTrue(FOO\Cookie::isDirty());
    }

    public function testGet() {
        $_COOKIE[FOO\Cookie::COOKIE_KEY] = FOO\Cookie::generate(['x' => 'y'], $_SERVER['REQUEST_TIME']);
        FOO\Cookie::read();

        $this->assertSame('y', FOO\Cookie::get('x'));
    }

    public function testSet() {
        FOO\Cookie::set('x', 'y');

        $this->assertSame('y', FOO\Cookie::get('x'));
    }
}
