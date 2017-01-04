<?php

class NonceTest extends TestCase {
    public function testCheck() {
        $nnc = FOO\Nonce::get();
        $this->assertTrue(FOO\Nonce::check($nnc));
        $this->assertFalse(FOO\Nonce::check('WRONG'));
    }
}
