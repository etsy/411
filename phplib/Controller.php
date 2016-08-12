<?php

namespace FOO;

/**
 * Class Controller
 * For any endpoints that don't belong under the REST Controller.
 * No authentication is done!
 * @package FOO
 */
abstract class Controller {
    /**
     * Route a request to the appropriate logic.
     */
    abstract public function route();

    /**
     * Render the input as JSON and terminate the script.
     * @param array $data The input data.
     */
    public function renderJSON($data) {
        header('Content-Type: application/json charset=utf-8');

        print json_encode($data);
        exit(0);
    }
}
