<?php

class AlertLogTest extends DBTestCase {
    public function testGetDescription() {
        $alertlog = new FOO\AlertLog();

        $alertlog['action'] = FOO\AlertLog::A_NOTE;
        $this->assertSame('System added a note', $alertlog->getDescription());

        $alertlog['action'] = FOO\AlertLog::A_ESCALATE;
        $alertlog['a'] = 1;
        $this->assertSame('System escalated', $alertlog->getDescription());

        $alertlog['action'] = FOO\AlertLog::A_ASSIGN;
        $alertlog['a'] = 1;
        $this->assertSame('System assigned to System', $alertlog->getDescription());
        $alertlog['a'] = 0;
        $this->assertSame('System unassigned', $alertlog->getDescription());

        $alertlog['action'] = FOO\AlertLog::A_SWITCH;
        $alertlog['a'] = 0;
        $this->assertSame('System marked New', $alertlog->getDescription());
        $alertlog['a'] = 2;
        $alertlog['b'] = 0;
        $this->assertSame('System marked Resolved (Not an issue)', $alertlog->getDescription());
    }

    public function testFinderGetRecentForRollup() {
        TestHelper::populateDB([
            [FOO\Search::$TABLE, 1, 0, 'One', 'null', '', '', '', '', FOO\Search::$CATEGORIES['general'], '', FOO\Search::P_MED, FOO\Search::SCT_FREQ, 1, '', 1, true, 0, 0, 0, 0.0, FOO\Search::NT_ONDEMAND, FOO\Search::NF_FULL, '', 0, '', 0, 0, 0, 0, 0, 0, 0],
            [FOO\Search::$TABLE, 2, 0, 'Two', 'null', '', '', '', '', FOO\Search::$CATEGORIES['general'], '', FOO\Search::P_LOW, FOO\Search::SCT_FREQ, 1, '', 1, true, 0, 0, 0, 0.0, FOO\Search::NT_ONDEMAND, FOO\Search::NF_FULL, '', 0, '', 0, 0, 0, 0, 0, 0, 0],
            [FOO\Alert::$TABLE, 1, 0, 0, '', '', '', 0, 0, 1, 0, FOO\Alert::ST_NEW, 0, 0, 0, 0],
            [FOO\Alert::$TABLE, 2, 0, 0, '', '', '', 0, 0, 2, 0, FOO\Alert::ST_NEW, 0, 0, 0, 0],
            [FOO\Alert::$TABLE, 3, 0, 0, '', '', '', 0, 0, 1, 0, FOO\Alert::ST_NEW, 0, 0, 0, 0],
            [FOO\AlertLog::$TABLE, 1, 0, 1, 1, FOO\AlertLog::A_CREATE, '', 1, 0, 0, 2900, 0],
            [FOO\AlertLog::$TABLE, 2, 0, 1, 1, FOO\AlertLog::A_ESCALATE, '', 1, 0, 0, 3000, 0],
            [FOO\AlertLog::$TABLE, 3, 0, 2, 1, FOO\AlertLog::A_ESCALATE, '', 1, 0, 0, 0, 0],
            [FOO\AlertLog::$TABLE, 4, 0, 3, 1, FOO\AlertLog::A_ESCALATE, '', 1, 0, 0, 300, 0],
        ]);

        $alertlogs = FOO\AlertLogFinder::getRecent(61 * 60, FOO\Search::NT_ONDEMAND, 60 * 60);
        $this->assertCount(2, $alertlogs);
        $this->assertSame(2, $alertlogs[0]['id']);
        $this->assertSame(4, $alertlogs[1]['id']);
    }
}
