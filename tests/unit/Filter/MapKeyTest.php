<?php

class MapKeyFilterTest extends TestCase {
    public function testProcess() {
        $filter = new FOO\MapKey_Filter();
        $filter['data']['key_regex'] = 'x';
        $filter['data']['key_expr'] = 'key ~ "yz"';

        $alert = new FOO\Alert();
        $alert['content'] = [
            'x' => 'y',
            'z' => '_',
        ];

        $alerts = $filter->process($alert, 0);
        $this->assertCount(1, $alerts);
        $this->assertSame([
            'z' => '_',
            'xyz' => 'y',
        ], $alerts[0]['content']);
    }
}
