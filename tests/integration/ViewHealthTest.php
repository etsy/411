<?php

class ViewHealth extends TestCase {
    /**
     * @expectedException FOO\UnauthorizedException
     */
    public function testNoUser() {
        $c = new FOO\Health_REST();
        $c->checkAuthorization();
        $c->GET([]);
    }
    public function testMain() {
        TestHelper::populateUsers();
        TestHelper::becomeUser();

        TestHelper::populateDB([
            [FOO\DBMeta::$TABLE, 0, 'last_cron_date', 0],
            [FOO\DBConfig::$TABLE, 0, 'cron_enabled', true],
        ]);

        $c = new FOO\Health_REST();
        $c->checkAuthorization();
        $data = $c->GET([])['data'];

        $this->assertSame(0, $data['last_cron_date']);
        $this->assertSame(true, $data['cron_enabled']);
        $this->assertSame(array_keys(FOO\Search::getTypes()), array_keys($data['search_health']));
        $this->assertCount(5, $data['searchjob_health']);
    }
}
