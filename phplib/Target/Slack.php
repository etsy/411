<?php

namespace FOO;

/**
 * Class Slack Target
 * Sends a message to Slack for each Alert that is generated.
 * @package FOO
 */
class Slack_Target extends Target {
    public static $TYPE = 'slack';

    public static $DESC = 'Sends Alerts off to a remote server via POST.';

    private $list = [];

    protected static function generateDataSchema() {
        return [
            'channel' => [static::T_STR, null, ''],
        ];
    }

    public function validateData(array $data) {
        parent::validateData($data);
    }

    /**
     * Send an Alert off to a Slack channel.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     */
    public function process(Alert $alert, $date) {
        $scfg = Config::get('slack');
        if(is_null($scfg['webhook_url'])) {
            throw new TargetException('Slack not configured');
        }

        $search = SearchFinder::getById($alert['search_id']);
        $fields = [];
        foreach($alert['content'] as $key=>$value) {
            $fields[] = [
                'title' => $key,
                'value' => $value,
                'short' => true
            ];
        }

        $host = Util::getHost();
        $alert_link = $alert->isNew() ? null:sprintf('https://%s/alert/%d', $host, $alert['id']);

        $message = implode(' ', [
            is_null($search) ? 'Unknown':sprintf('<https://%s/search/%d|%s>', $host, $search['id'], $search['name']),
            $alert->isNew() ? '[N/A]':sprintf('[<%s|Alert>]', $alert_link)
        ]);

        $curl = new \Curl\Curl;
        $curl->setHeader('Content-Type', 'application/json; charset=utf-8');
        $curl->post(
            $scfg['webhook_url'],
            json_encode([
                'channel' => $this->obj['data']['channel'],
                'username' => Util::getSiteName(),
                'icon_emoji' => ':warning:',
                'text' => '',
                'attachments' => [[
                    'pretext' => $message,
                    'fields' => $fields,
                    'ts' => $alert['alert_date'],
                ]],
            ])
        );

        if($curl->httpStatusCode != 200) {
            throw new TargetException(sprintf('Remote server returned %d', $curl->httpStatusCode));
        }
    }
}
