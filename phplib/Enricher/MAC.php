<?php

namespace FOO;

class MAC_Enricher extends Enricher {
    public static $TYPE = 'mac';

    public static function process($data) {
        $curl = new Curl;
        $raw_data = $curl->get(sprintf('https://macvendors.co/api/%s', $data));
        if($curl->httpStatusCode != 200) {
            throw new EnricherException('Error retrieving data');
        }

        return $raw_data['result'];
    }

    public static function processHTML($data) {
        $enc_data = urlencode($data);
        return sprintf('<a href="https://macvendors.co/results/%s">%s</a>', $enc_data, $enc_data);
    }
}
