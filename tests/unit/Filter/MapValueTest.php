<?php

class MapValueFilterTest extends DBTestCase {
    public function testProcess() {
        $filter = new FOO\MapValue_Filter();
        $filter['data']['key_regex'] = 'z';
        $filter['data']['value_expr'] = 'value ~ "abc"';

        $alert = new FOO\Alert();
        $alert['content'] = [
            'z' => '_',
            'a' => '_',
        ];

        $alerts = $filter->process($alert, 0);
        $this->assertCount(1, $alerts);
        $this->assertSame([
            'z' => '_abc',
            'a' => '_',
        ], $alerts[0]['content']);
    }
}
