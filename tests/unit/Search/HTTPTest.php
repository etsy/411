<?php

use FOO\Alert;
use FOO\Curl;
use FOO\HTTP_Search;

class HTTP_SearchTest extends TestCase {
    public function testShouldGenerateAnAlertForInvalidStatusCode()
    {
        $httpClient = $this->getHttpClientMock();
        $httpClient->httpStatusCode = 500;
        $httpClient->rawResponse = 'Internal Server Error';

        $search = new HTTP_Search();

        $search->setId(1);
        $search->setHttpClient($httpClient);

        $search['query_data'] = [
            'url' => 'http://unittest.com/alerts',
            'code' => 200
        ];

        $expectedAlert = new Alert();
        $expectedAlert['content'] = [
            'status' => 500,
            'url' => 'http://unittest.com/alerts',
            'content_length' => 21
        ];

        $alert = $search->execute(0)[0];

        $this->assertEquals($expectedAlert['alert_date'], $alert['alert_date']);
        $this->assertEquals($expectedAlert['content'], $alert['content']);
    }

    public function testShouldGenerateAnAlertForInvalidContentMatch()
    {
        $httpClient = $this->getHttpClientMock();
        $httpClient->httpStatusCode = 200;
        $httpClient->rawResponse = 'Content doesn\'t match.';

        $search = new HTTP_Search();

        $search->setId(1);
        $search->setHttpClient($httpClient);

        $search['query_data'] = [
            'url' => 'http://unittest.com/alerts',
            'code' => 200,
            'content_match' => 'Content match.'
        ];

        $expectedAlert = new Alert();
        $expectedAlert['content'] = [
            'status' => 200,
            'url' => 'http://unittest.com/alerts',
            'content_length' => 22
        ];

        $alert = $search->execute(0)[0];

        $this->assertEquals($expectedAlert['alert_date'], $alert['alert_date']);
        $this->assertEquals($expectedAlert['content'], $alert['content']);
    }

    public function testShouldNotGenerateAnAlert()
    {
        $httpClient = $this->getHttpClientMock();
        $httpClient->httpStatusCode = 200;
        $httpClient->rawResponse = 'Content match.';

        $search = new HTTP_Search();

        $search->setId(1);
        $search->setHttpClient($httpClient);

        $search['query_data'] = [
            'url' => 'http://unittest.com/alerts',
            'code' => 200,
            'content_match' => 'Content match.'
        ];

        $this->assertEmpty($search->execute(0));
    }

    private function getHttpClientMock()
    {
        return $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
