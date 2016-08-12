<?php

class Test_REST extends FOO\REST {}

class RESTTest extends DBTestCase {
    public function testFormat() {
        $rest = new Test_REST();

        $data = ['data' => null, 'success' => true, 'message' => '', 'authenticated' => false];
        $this->assertSame($data, $rest->format(null));
        $this->assertSame($data, $rest->format($data));
    }
}
