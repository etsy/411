<?php

class AlertTest extends DBTestCase {
    public function testFinderGenerateWhere() {
        $expected = [
            ['`site_id` = ?', '`archived` = ?', '`alert_date` > ?', '`alert_date` < ?'],
            [0, 0, 0, 1]
        ];
        $this->assertSame($expected, FOO\AlertFinder::generateWhere(['from' => 0, 'to' => 1, 'x' => 2]));
    }

    public function testFinderGetActiveCounts() {
        TestHelper::populateDB([
            [FOO\Alert::$TABLE, 1, 0, 0, '', '', '', 0, 0, 1, 1, FOO\Alert::ST_RES, FOO\Alert::RES_ACT, 0, 0, 0],
            [FOO\Alert::$TABLE, 2, 0, 0, '', '', '', 0, 0, 1, 0, FOO\Alert::ST_INPROG, 0, 0, 0, 0],
            [FOO\Alert::$TABLE, 3, 0, 0, '', '', '', 0, 0, 1, 0, FOO\Alert::ST_NEW, 0, 0, 0, 0],
        ]);

        $this->assertEquals([1, 1], FOO\AlertFinder::getActiveCounts());
    }

    public function testFinderGetRecentSearchHashCount() {
        TestHelper::populateDB([
            [FOO\Alert::$TABLE, 1, 0, 0, '', 'xxx', '', 0, 0, 1, 1, FOO\Alert::ST_RES, FOO\Alert::RES_ACT, 0, 10, 0],
            [FOO\Alert::$TABLE, 2, 0, 0, '', 'xxx', '', 0, 0, 2, 0, FOO\Alert::ST_INPROG, 0, 0, 10, 0],
        ]);

        $this->assertEquals(1, FOO\AlertFinder::getRecentSearchHashCount(1, 'xxx', 0));
    }

    public function testFinderGetRecentSearchCount() {
        TestHelper::populateDB([
            [FOO\Alert::$TABLE, 1, 0, 0, '', '', '', 0, 0, 1, 1, FOO\Alert::ST_INPROG, 0, 0, 0, 0],
            [FOO\Alert::$TABLE, 2, 0, 0, '', '', '', 0, 0, 1, 1, FOO\Alert::ST_INPROG, 0, 0, 10, 0],
            [FOO\Alert::$TABLE, 3, 0, 0, '', '', '', 0, 0, 2, 0, FOO\Alert::ST_INPROG, 0, 0, 10, 0],
        ]);

        $this->assertEquals(1, FOO\AlertFinder::getRecentSearchCount(1, 0));
    }
}
