<?php

class ChangeAdminSettings extends TestCase {
    /**
     * @expectedException FOO\UnauthorizedException
     */
    public function testUser() {
        TestHelper::populateUsers();
        TestHelper::becomeUser();

        $c = new FOO\Admin_REST();
        $c->checkAuthorization();
        $c->GET([]);
    }

    public function testAdmin() {
        TestHelper::populateUsers();
        TestHelper::becomeAdmin();

        $data = [
            'cron_enabled' => true,
            'worker_enabled' => true,
            'summary_enabled' => true,
            'error_email_enabled' => true,
            'error_email_throttle' => 600,
            'from_email' => 'test@test.com',
            'from_error_email' => 'error@test.com',
            'default_email' => 'test@example.com',
        ];

        $c = new FOO\Admin_REST();
        $c->checkAuthorization();
        $c->POST([], $data);

        $data_ = $c->GET([])['data'];
        $this->assertEquals($data, $data_);
    }
}
