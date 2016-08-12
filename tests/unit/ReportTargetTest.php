<?php

class ReportTargetTest extends DBTestCase {
    public function testFinderGetByGroup() {
        TestHelper::populateDB([
            [FOO\ReportTarget::$TABLE, 1, 0, 1, 1, 0, 0, 0, 0],
            [FOO\ReportTarget::$TABLE, 2, 0, 2, 1, 0, 0, 0, 0],
        ]);

        $reporttargets = FOO\ReportTargetFinder::getByReport(1);
        $this->assertCount(1, $reporttargets);
        $this->assertSame(1, $reporttargets[0]['id']);
    }
}
