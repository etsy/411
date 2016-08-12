<?php

class DBTestCase extends PHPUnit_Framework_TestCase {
    protected $requestTime;

    public function setUp() {
        $this->requestTime = $_SERVER['REQUEST_TIME'];
        $_SERVER['REQUEST_TIME'] = 1460000000;
        TestHelper::setupDB();
    }
    public function tearDown() {
        TestHelper::teardownDB();
        $_SERVER['REQUEST_TIME'] = $this->requestTime;
        $this->requestTime = null;
    }

    public function getMockObject($type, $arr) {
        $obj = $this->getMock($type, array('offsetGet'));
        $obj->expects($this->any())
            ->method('offsetGet')
            ->will($this->returnCallback(function($key) use ($arr) {
                return $arr[$key];
            }));
        return $obj;
    }
}
