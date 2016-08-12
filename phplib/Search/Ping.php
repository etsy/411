<?php

namespace FOO;

/**
 * Class Ping_Search
 * Check that a host on the network is reachable.
 * @package FOO
 */
class Ping_Search extends Search {
    public static $TYPE = 'ping';

    protected function constructQuery() {
        return $this->obj['query_data'];
    }

    public function isWorking($date) {
        return file_exists('/bin/ping');
    }

    public function validateData(array $data) {
        parent::validateData($data);

        if(gethostbynamel(Util::get($data['query_data'], 'host')) === false) {
            throw new ValidationException('Invalid host');
        }
    }

    protected function _execute($date, $constructed_qdata) {
        $host = Util::get($constructed_qdata, 'host');
        $output = exec('/bin/ping -w 1 -c 1 ' . escapeshellarg($host), $rdata, $rcode);
        $this->obj['last_status'] = $output;

        if($rcode == 0) {
            return [];
        }

        $alert = new Alert;
        $alert['alert_date'] = $date;
        $alert['content'] = ['host' => $host];

        return [$alert];
    }
}
