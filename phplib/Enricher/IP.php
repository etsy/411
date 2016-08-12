<?php

namespace FOO;

/**
 * IP Enricher
 * Return GeoIP information on an IP address.
 * @package FOO
 */
class IP_Enricher extends Enricher {
    public static $TYPE = 'ip';

    public static function process($data) {
        $curl = new \Curl\Curl;
        $ret = $curl->get(sprintf('https://freegeoip.net/json/%s', $data));
        if($curl->httpStatusCode != 200) {
            throw new EnricherException('Error retrieving data');
        }

        return $ret;
    }
}
