<?php

class SiteTest extends TestCase {
    public function setUp() {
        parent::setUp();
    }

    public function testFinderGetCurrentId() {
        $this->assertSame(FOO\Site::NONE, FOO\SiteFinder::getCurrentId());

        $site = $this->getMockObject('FOO\Site', ['id' => 1]);
        FOO\SiteFinder::setSite($site);
        $this->assertSame(1, FOO\SiteFinder::getCurrentId());
    }
}
