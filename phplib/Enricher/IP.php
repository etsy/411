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
        $curl = new Curl;
        $raw_data = $curl->get(sprintf('https://freegeoip.net/json/%s', $data));
        if($curl->httpStatusCode != 200) {
            throw new EnricherException('Error retrieving data');
        }

        return $raw_data;
    }

    public static function processHTML($data) {
        $enc_data = urlencode($data);
        return sprintf('<a href="https://freegeoip.net/?q=%s">%s</a>', $enc_data, $enc_data);
    }
}
