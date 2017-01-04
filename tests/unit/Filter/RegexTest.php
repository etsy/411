<?php

class RegexFilterTest extends TestCase {
    public function testProcess() {
        $filter = new FOO\Regex_Filter();
        $filter['data']['include'] = true;
        $filter['data']['key'] = 'x';
        $filter['data']['regex'] = 'x';
        $alert = new FOO\Alert();
        $alert['content']['x'] = 'y';

        $this->assertSame([], $filter->process($alert, 0));

        $alert['content']['x'] = 'x';
        $this->assertSame([$alert], $filter->process($alert, 0));
    }
}
