<?php

class TestCase extends PHPUnit_Framework_TestCase {
    protected $requestTime;
    protected $config;

    public function setUp() {
        $GLOBALS['TESTING'] = true;

        $this->requestTime = $_SERVER['REQUEST_TIME'];
        $this->config = FOO\Config::getData();

        FOO\Config::init([]);
        $_SERVER['REQUEST_TIME'] = 1460000000;

        TestHelper::setupDB();
    }
    public function tearDown() {
        TestHelper::teardownDB();

        $_SERVER['REQUEST_TIME'] = $this->requestTime;
        FOO\Config::init($this->config);

        $this->config = null;
        $this->requestTime = null;

        $GLOBALS['TESTING'] = false;
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
