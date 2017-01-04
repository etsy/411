<?php

class CreateAndTestSearch extends TestCase {
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
            'enabled' => false,
        ];

        $c = new FOO\Searches_REST();
        $c->checkAuthorization();
        $data_ = $c->POST([], $data);

        $data__ = $c->test([], ['id' => $data_['data']['id']]);
    }
}
