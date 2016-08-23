<?php

class GroupTest extends DBTestCase {
    public function testGetDescription() {
        TestHelper::populateDB([
            [FOO\GroupTarget::$TABLE, 1, 0, 1, FOO\GroupTarget::T_EMAIL, 1, 'test@example.com', 0, 0, 0],
            [FOO\GroupTarget::$TABLE, 2, 0, 1, FOO\GroupTarget::T_EMAIL, 1, 'best@example.com', 0, 0, 0],
        ]);

        $group = new FOO\Group();
        $group->setId(1);
        $group['type'] = FOO\Group::T_ROT;
        $this->assertSame(['test@example.com'], $group->getEmails(true, false));
        $this->assertSame(['best@example.com'], $group->getEmails(true, false));

        $this->assertSame(['test@example.com', 'best@example.com'], $group->getEmails(true, true));
    }
}
