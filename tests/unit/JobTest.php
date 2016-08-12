<?php

namespace FOO {
class TestJob extends Job {
    public static $TYPE = 'test';

    public function run() {}
}
}

namespace {
class JobTest extends DBTestCase {

    public function testFinderFail() {
        TestHelper::populateDB([
            [FOO\Job::$TABLE, 1, 0, 1, FOO\Search_Job::$TYPE, FOO\Job::ST_RUN, 100, 0, 0, 0, 0, 0, 0],
            [FOO\Job::$TABLE, 2, 0, 1, FOO\Search_Job::$TYPE, FOO\Job::ST_RUN, 100, 0, 0, 0, 0, 0, 10000],
            [FOO\Job::$TABLE, 3, 0, 1, FOO\Search_Job::$TYPE, FOO\Job::ST_SUCC, 100, 0, 0, 0, 0, 0, 0],
        ]);

        FOO\JobFinder::fail(10000);
        $jobs = FOO\JobFinder::getPendingIds();
        $this->assertCount(1, $jobs);
        $this->assertSame(1, (int) $jobs[0]);
    }

    public function testFinderGetPendingIds() {
        TestHelper::populateDB([
            [FOO\Job::$TABLE, 1, 0, FOO\Search_Job::$TYPE, 1, FOO\Job::ST_PEND, 100, 0, 0, 0, 0, 0, 0],
            [FOO\Job::$TABLE, 2, 0, FOO\Search_Job::$TYPE, 1, FOO\Job::ST_SUCC, 100, 0, 0, 0, 0, 0, 0],
        ]);

        $ids = FOO\JobFinder::getPendingIds();
        $this->assertCount(1, $ids);
        $this->assertSame(1, (int) $ids[0]);
    }

    public function testFinderGetAndLock() {
        TestHelper::populateDB([
            [FOO\Job::$TABLE, 1, 0, FOO\Search_Job::$TYPE, 1, FOO\Job::ST_PEND, 100, 0, 0, 0, 0, 0, 0],
            [FOO\Job::$TABLE, 2, 0, FOO\Search_Job::$TYPE, 1, FOO\Job::ST_SUCC, 100, 0, 0, 0, 0, 0, 0],
        ]);

        $this->assertNull(FOO\JobFinder::getAndLock(2, 0));
        $job = FOO\JobFinder::getAndLock(1, 0);
        $this->assertNotNull($job);
        $this->assertSame(1, $job['id']);
    }

    public function testFinderGetCounts() {
        TestHelper::populateDB([
            [FOO\Job::$TABLE, 1, 0, 1, FOO\Search_Job::$TYPE, FOO\Job::ST_PEND, 100, 0, 0, 0, 0, 0, 0],
            [FOO\Job::$TABLE, 2, 0, 1, FOO\Search_Job::$TYPE, FOO\Job::ST_SUCC, 100, 0, 0, 0, 0, 0, 0],
            [FOO\Job::$TABLE, 3, 0, 1, FOO\Search_Job::$TYPE, FOO\Job::ST_SUCC, 100, 0, 0, 0, 0, 0, 0],
        ]);

        $this->assertSame([1, 2, 0, 0, 0], FOO\JobFinder::getCounts());
    }

    public function testFinderGetLastByQuery() {
        TestHelper::populateDB([
            [FOO\Job::$TABLE, 1, 0, 'fake', 1, FOO\Job::ST_PEND, 100, 0, 0, 0, 0, 0, 0],
            [FOO\Job::$TABLE, 2, 0, FOO\Search_Job::$TYPE, 1, FOO\Job::ST_PEND, 100, 0, 0, 0, 0, 0, 0],
            [FOO\Job::$TABLE, 3, 0, FOO\Search_Job::$TYPE, 1, FOO\Job::ST_SUCC, 100, 0, 1000, 0, 0, 0, 0],
        ]);

        $job = FOO\JobFinder::getLastByQuery(['type' => FOO\Search_Job::$TYPE, 'target_id' => 1]);
        $this->assertNotNull($job);
        $this->assertSame(3, $job['id']);
        $this->assertNull(FOO\JobFinder::getLastByQuery(['type' => FOO\Search_Job::$TYPE, 'target_id' => 3]));
    }
}
}
