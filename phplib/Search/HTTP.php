<?php

namespace FOO;

/**
 * Class HTTP_Search
 * Check that an HTTP endpoint returns an expected response code.
 * @package FOO
 */
class HTTP_Search extends Search {
    public static $TYPE = 'http';

    protected function constructQuery() {
        return $this->obj['query_data'];
    }

    public function validateData(array $data) {
        parent::validateData($data);

        $code = Util::get($data['query_data'], 'code');
        if(!is_int($code) || $code < 0) {
            throw new ValidationException('Invalid status code');
        }

        if(!filter_var(Util::get($data['query_data'], 'url'), FILTER_VALIDATE_URL)) {
            throw new ValidationException('Invalid url');
        }
    }

    protected function _execute($date, $constructed_qdata) {
        $curl = new \Curl\Curl;
        $curl->get($constructed_qdata['url']);
        $this->obj['last_status'] = sprintf('Response code = %d', $curl->httpStatusCode);

        if($curl->httpStatusCode === $constructed_qdata['code']) {
            return [];
        }

        $alert = new Alert;
        $alert['alert_date'] = $date;
        $alert['content'] = ['status' => $curl->httpStatusCode, 'url' => $constructed_qdata['url']];

        return [$alert];
    }
}

