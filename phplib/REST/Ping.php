<?php

namespace FOO;

/**
 * Class Ping_Controller
 * @package FOO
 */
class Ping_REST extends REST {
    /**
     * Outputs all runtime data necessary to bootstrap the frontend.
     */
    public function GET(array $get) {
        return $this->format('pong');
    }
}
