<?php

class TimerTest extends TestCase {
    public function testTaken() {
        $timer = new FOO\Timer();
        $timer->start();
        $timer->stop();
        $this->assertGreaterThan(0, $timer->taken());
    }

    public function testReset() {
        $timer = new FOO\Timer();
        $timer->start();
        $timer->reset();
        $this->assertSame(0.0, $timer->taken());
    }
}
