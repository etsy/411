<?php

class CreateAndRunReport extends DBTestCase {
    public function testMain() {
        TestHelper::populateUsers();
        TestHelper::becomeUser();

        $data = [
            'name' => 'Test',
            'type' => 'null',
            'query_data' => [],
            'description' => 'Desc',
            'category' => 'general',
            'tags' => [],
            'priority' => FOO\Search::P_HIGH,
            'schedule_type' => FOO\Search::SCT_FREQ,
            'cron_expression' => '',
            'range' => 1,
            'enabled' => true,
        ];

        $c = new FOO\Searches_REST();
        $c->checkAuthorization();
        $ret = $c->POST([], $data);

        $data = [
            'name' => 'Test',
            'type' => FOO\Report::T_AA,
            'description' => 'Desc',
        ];

        $c = new FOO\Reports_REST();
        $c->checkAuthorization();
        $ret = $c->POST([], $data);

        ob_start();
        $c->GET([
            'action' => 'generate',
            'mode' => 'csv',
            'id' => $ret['data']['id']
        ]);

        $expected =
            "Title,Test\nDescription,Desc\nDate,\"April  7, 2016\"\nRange,1\n\nTest\nDesc\nDate,null\n" .
            '"Thu, 07 Apr 2016 03:33:20 +0000",,null' .
            "\n\n\n";

        $this->assertSame($expected, ob_get_clean());
    }
}
