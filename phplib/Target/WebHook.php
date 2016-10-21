<?php

namespace FOO;

/**
 * Class WebHook_Target
 * Send off Alerts to an external service via POST. The remote service receives a JSON blob with an array of Alert
 * objects.
 * @package FOO
 */
class WebHook_Target extends Target {
    public static $TYPE = 'webhook';

    public static $DESC = 'Sends Alerts off to a remote server via POST.';

    /** @var Alert[]|array[] The list of data to send. */
    private $list = [];

    protected static function generateDataSchema() {
        return [
            'url' => [static::T_STR, null, ''],
            'content_only' => [static::T_BOOL, null, false],
        ];
    }

    public function validateData(array $data) {
        parent::validateData($data);

        if(!filter_var($data['data']['url'], FILTER_VALIDATE_URL)) {
            throw new ValidationException('Invalid url');
        }
    }

    /**
     * Buffer Alerts and send them off to the Webhook as necessary.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     */
    public function process(Alert $alert, $date) {
        $this->list[] = $this->obj['data']['content_only'] ? $alert['content']:$alert;

        if(count($this->list) >= 100) {
            $this->send();
        }
    }

    /**
     * Send any remaining Alerts to the Webhook.
     * @param int $date The current date.
     * @throws TargetException
     */
    public function finalize($date) {
        $this->send();
    }

    /**
     * Send Alerts to the Webhook.
     * @throws TargetException
     */
    private function send() {
        // Only POST if we have at least 1 Alert to send.
        if(!count($this->list)) {
            return;
        }

        $curl = new Curl;
        $curl->setHeader('Content-Type', 'application/json; charset=utf-8');
        $raw_data = $curl->post($this->obj['data']['url'], json_encode($this->list));
        if($curl->httpStatusCode != 200) {
            throw new TargetException(sprintf('Remote server returned %d: %s: %s', $curl->httpStatusCode, $curl->httpErrorMessage, $raw_data));
        }
        $this->list = [];
    }
}
