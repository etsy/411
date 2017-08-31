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

        $contentMatchRegex = Util::get($data['query_data'], 'content_match');
        if(@preg_match("/$contentMatchRegex/", null) === false) {
            throw new ValidationException('Invalid regular expression for content match');
        }
    }

    protected function _execute($date, $constructed_qdata) {
        $curl = new Curl;
        $curl->get($constructed_qdata['url']);
        $this->obj['last_status'] = sprintf(
            'Code = %d, Message = %s',
            $curl->httpStatusCode,
            $curl->httpErrorMessage
        );

        if ($this->isStatusCodeValid($constructed_qdata['code'], $curl->httpStatusCode) &&
            $this->isContentValid($constructed_qdata['content_match'], $curl->rawResponse)) {
            return [];
        }

        $alert = new Alert;
        $alert['alert_date'] = $date;
        $alert['content'] = [
            'status' => $curl->httpStatusCode,
            'url' => $constructed_qdata['url'],
            'content length' => strlen($curl->rawResponse)
        ];

        return [$alert];
    }

    private function isStatusCodeValid($expected, $current)
    {
        return $expected === $current;
    }

    private function isContentValid($regex, $response)
    {
        if (empty($regex)) {
            return true;
        }

        return (bool)preg_match("/" . $regex . "/", $response);
    }
}

