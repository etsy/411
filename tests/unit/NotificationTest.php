<?php

class NotificationTest extends TestCase {

    public function setUp() {
        parent::setUp();
        $this->setSite();
    }

    public function tearDown() {
        $this->clearSite();
        parent::tearDown();
    }

    public function setSite() {
        $site = $this->getMockObject('FOO\Site', ['id' => 0, 'name' => 'Test411', 'host' => 'test.com']);
        FOO\SiteFinder::setSite($site);
    }

    public function clearSite() {
        FOO\SiteFinder::clearSite();
    }

    public function testGetFrom() {
        TestHelper::populateDB([
            [FOO\DBConfig::$TABLE, 0, 'from_email', 'test@test.com'],
            [FOO\DBConfig::$TABLE, 0, 'from_error_email', 'error@test.com'],
        ]);

        $this->assertSame('test@test.com', FOO\Notification::getFrom(false));
        $this->assertSame('error@test.com', FOO\Notification::getFrom(true));
    }
}
