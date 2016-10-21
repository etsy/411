<?php

namespace FOO;

/**
 * Class Curl
 * Http request wrapper
 * @package FOO
 */
class Curl extends \Curl\Curl {
    public function __construct($base_url = null) {
        parent::__construct($base_url);

        $this->setJsonDecoder(true);
        $this->setUserAgent(sprintf('411/%s (+https://github.com/etsy/411)', VERSION));
    }
}
