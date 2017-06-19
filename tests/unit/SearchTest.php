<?php

class SearchTest extends TestCase {
    public function testShouldRun() {
        $search = new FOO\Null_Search();
        $search['schedule_type'] = FOO\Search::SCT_FREQ;
        $search['frequency'] = 2;
        $this->assertFalse($search->shouldRun(10));
        $this->assertTrue($search->shouldRun(2 * 60));

        $this->assertFalse($search->shouldRun(61, true));
        $this->assertTrue($search->shouldRun(2 * 60, true));

        $search['schedule_type'] = FOO\Search::SCT_CRON;
        $search['cron_expression'] = '0 0 * * *';
        $this->assertTrue($search->shouldRun(24 * 60 * 60));
        $this->assertFalse($search->shouldRun(60));
    }

    public function testGetLink() {
        $search = new FOO\Null_Search();
        $alert = new FOO\Alert();

        $this->assertSame(null, $search->getLink($alert));

        $search['query_data']['source_expr'] = '"link"';
        $this->assertSame('link', $search->getLink($alert));
    }

    public function testExecute() {
        $search = new FOO\Null_Search();
        $search->setId(1);
        $search['assignee_type'] = FOO\Assignee::T_USER;
        $search['assignee'] = 1;

        $alerts = $search->execute(0);
        $alert_data = $alerts[0]->toArray([
            'search_id', 'assignee_type', 'assignee', 'content_hash'
        ]);
        unset($alert_data['id']);
        unset($alert_data['archived']);
        unset($alert_data['create_date']);
        unset($alert_data['update_date']);
        $this->assertSame(
            [
                'search_id' => 1,
                'assignee_type' => FOO\Assignee::T_USER,
                'assignee' => 1,
                'content_hash' => $search->getContentHash($alerts[0])
            ],
            $alert_data
        );
    }
    public function testGetContentHash() {
        $search = new FOO\Null_Search();
        $alert = new FOO\Alert();
        $alert_ = new FOO\Alert();
        $this->assertSame($search->getContentHash($alert), $search->getContentHash($alert_));

        $alert_['content']['a'] = 'b';
        $this->assertNotSame($search->getContentHash($alert), $search->getContentHash($alert_));
    }
    public function testGetFilters() {
        TestHelper::populateDB([
            [FOO\Filter::$TABLE, 1, 0, 1, 'null', 1, 0, '', '', 0, 0, 0],
            [FOO\Filter::$TABLE, 2, 0, 2, 'null', 2, 0, '', '', 0, 0, 0],
        ]);

        $search = new FOO\Null_Search();
        $search->setId(1);

        $filters = $search->getFilters();
        $this->assertCount(1, $filters);
        $this->assertSame(1, $filters[0][FOO\Filter::$PKEY]);
    }
    public function testGetTargets() {
        TestHelper::populateDB([
            [FOO\Target::$TABLE, 1, 0, 1, 'null', 0, '', '', 0, 0, 0],
        ]);

        $search = new FOO\Null_Search();
        $search->setId(1);

        $targets = $search->getTargets();
        $this->assertCount(1, $targets);
        $this->assertSame(1, $targets[1][FOO\Target::$PKEY]);
    }
}
