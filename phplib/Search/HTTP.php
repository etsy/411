<?php

namespace FOO;

/**
 * Class HTTP_Search
 * Check that an HTTP endpoint returns an expected response code.
 * @package FOO
 */
class HTTP_Search extends Search {
    public static $TYPE = 'http';

    private $httpClient;

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
        $curl = $this->getHttpClient();
        $curl->get($constructed_qdata['url']);
        $this->obj['last_status'] = sprintf(
            'Code = %d, Message = %s',
            $curl->httpStatusCode,
            $curl->httpErrorMessage
        );

        if ($this->isStatusCodeValid($constructed_qdata, $curl) &&
            $this->isContentValid($constructed_qdata, $curl)
        ) {
            return [];
        }

        $alert = new Alert;
        $alert['alert_date'] = $date;
        $alert['content'] = [
            'status' => $curl->httpStatusCode,
            'url' => $constructed_qdata['url'],
            'content_length' => strlen($curl->rawResponse)
        ];

        return [$alert];
    }

    private function isStatusCodeValid($expectedData, $curl)
    {
        return $expectedData['code'] === $curl->httpStatusCode;
    }

    private function isContentValid($expectedData, $curl)
    {
        if (!isset($expectedData['content_match']) && !empty($expectedData['content_match'])) {
            return true;
        }

        return preg_match("/" . $expectedData['content_match'] . "/", $curl->rawResponse) === 1;
    }

    private function getHttpClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Curl;
        }

        return $this->httpClient;
    }

    public function setHttpClient(Curl $httpClient)
    {
        $this->httpClient = $httpClient;
    }
}

