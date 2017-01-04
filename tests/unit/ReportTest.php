<?php

class ReportTest extends TestCase {
    public function testGetSearches() {
        TestHelper::populateDB([
            [FOO\ReportTarget::$TABLE, 1, 0, 1, 1, 0, 0, 0, 0],
            [FOO\ReportTarget::$TABLE, 2, 0, 2, 1, 0, 0, 0, 0],
            [FOO\Search::$TABLE, 1, 0, 'One', 'null', '', '', '', '', '', FOO\Search::$CATEGORIES['general'], '', FOO\Search::P_LOW, FOO\Search::SCT_FREQ, 1, '', 1, true, 0, 0, 0, 0.0, FOO\Search::NT_ONDEMAND, FOO\Search::NF_FULL, '', 0, '', 0, 0, 0, 0, 0, 0, 0],
            [FOO\Search::$TABLE, 2, 0, 'Two', 'null', '', '', '', '', '', FOO\Search::$CATEGORIES['general'], '', FOO\Search::P_LOW, FOO\Search::SCT_FREQ, 1, '', 1, true, 0, 0, 0, 0.0, FOO\Search::NT_ONDEMAND, FOO\Search::NF_FULL, '', 0, '', 0, 0, 0, 0, 0, 0, 0],
        ]);
        $report = new FOO\Report();
        $report->setId(1);
        $report['type'] = FOO\Report::T_SEL;
        $searches = $report->getSearches();
        $this->assertCount(1, $searches);
        $this->assertSame(1, $searches[0]['id']);

        $report['type'] = FOO\Report::T_AA;
        $searches = $report->getSearches();
        $this->assertCount(2, $searches);
    }

    public function testShouldRun() {
        $report = new FOO\Report();
        $report['start_date'] = 0;
        $report['frequency'] = 1;

        $day = 24 * 60 * 60;
        $this->assertFalse($report->shouldRun($day - 10));
        $this->assertTrue($report->shouldRun($day));
    }
}
