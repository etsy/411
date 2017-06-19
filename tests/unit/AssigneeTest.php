<?php

class AssigneeTest extends TestCase {
    public function testGet() {
        $this->assertSame('System', FOO\Assignee::getName(0, 0));

        TestHelper::populateDB([
            [FOO\User::$TABLE, 1, 0, 'user', 'User', '', 'test@test.com', 'UTC', false, '', '', 0, 0, 0],
            [FOO\Group::$TABLE, 2, 0, FOO\Group::T_ALL, 0, 'Group', 0, 0, 0],
            [FOO\GroupTarget::$TABLE, 1, 0, 2, FOO\GroupTarget::T_USER, 1, '', 0, 0, 0],
            [FOO\GroupTarget::$TABLE, 2, 0, 2, FOO\GroupTarget::T_EMAIL, 1, 'test@example.com', 0, 0, 0],
        ]);

        $this->assertSame('User', FOO\Assignee::getName(FOO\Assignee::T_USER, 1));
        $this->assertSame('Group', FOO\Assignee::getName(FOO\Assignee::T_GROUP, 2));

        $this->assertSame(['test@test.com'], FOO\Assignee::getEmails(FOO\Assignee::T_USER, 1));
        $this->assertSame(['test@test.com', 'test@example.com'], FOO\Assignee::getEmails(FOO\Assignee::T_GROUP, 2));

    }
}
