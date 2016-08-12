<?php

class ExpressionFilterTest extends DBTestCase {
    public function testProcess() {
        $filter = new FOO\Expression_Filter();
        $filter['data']['include'] = true;
        $filter['data']['expr'] = 'content["x"] == "x"';
        $alert = new FOO\Alert();
        $alert['content']['x'] = 'y';

        $this->assertSame([], $filter->process($alert, 0));

        $alert['content']['x'] = 'x';
        $this->assertSame([$alert], $filter->process($alert, 0));
    }
}
