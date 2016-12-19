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

        $this->setJsonDecoder(function($response) {
            $data = json_decode($response, true);
            if($data !== null) {
                $response = $data;
            }

            return $response;
        });
        $this->setUserAgent(sprintf('411/%s (+https://github.com/etsy/411)', VERSION));
    }

    /**
     * @suppress PhanTypeMismatchArgument
     */
    public function post($url, $data) {
        return parent::post($url, $data);
    }
}
