<?php

class GroupTargetTest extends DBTestCase {
    public function testGetEmail() {
        TestHelper::populateDB([
            [FOO\User::$TABLE, 1, 0, 'user', 'User', '', 'user@test.com', false, '', 0, 0, 0]
        ]);

        $grouptarget = new FOO\GroupTarget();

        $grouptarget['type'] = FOO\GroupTarget::T_USER;
        $grouptarget['user_id'] = 1;
        $this->assertSame('user@test.com', $grouptarget->getEmail());

        $grouptarget['type'] = FOO\GroupTarget::T_EMAIL;
        $grouptarget['data'] = 'user@test.com';
        $this->assertSame('user@test.com', $grouptarget->getEmail());
    }

    public function testFinderGetByGroup() {
        TestHelper::populateDB([
            [FOO\GroupTarget::$TABLE, 1, 0, 1, FOO\GroupTarget::T_EMAIL, 1, 'test@example.com', 0, 0, 0],
            [FOO\GroupTarget::$TABLE, 2, 0, 2, FOO\GroupTarget::T_EMAIL, 1, 'best@example.com', 0, 0, 0],
        ]);

        $grouptargets = FOO\GroupTargetFinder::getByGroup(1);
        $this->assertCount(1, $grouptargets);
        $this->assertSame(1, $grouptargets[0]['id']);
    }
}
