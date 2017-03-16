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

    public function isAccessible() {
        $scfg = Config::get('slack');
        return !is_null($scfg['webhook_url']);
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

        $site = SiteFinder::getCurrent();
        $alert_link = $alert->isNew() ? null:$site->urlFor(sprintf('/alert/%d', $alert['alert_id']));

        $message = implode(' ', [
            is_null($search) ? 'Unknown':sprintf('<%s|%s>', $site->urlFor(
                sprintf('/search/%d', $search['id']),
                $search['name']
            )),
            $alert->isNew() ? '[N/A]':sprintf('[<%s|Alert>]', $alert_link)
        ]);

        $message_data = [
            'channel' => $this->obj['data']['channel'],
            'username' => Util::getSiteName(),
            'icon_emoji' => ':warning:',
            'text' => '',
            'attachments' => [[
                'pretext' => $message,
                'fields' => $fields,
                'ts' => $alert['alert_date'],
            ]],
        ];
        list($message_data) = Hook::call('filter.slack.send', [$message_data]);

        self::createMessage($message_data);
    }

    /**
     * Send a message to Slack.
     * @param mixed[] $message_data Message data.
     */
    public static function createMessage($message_data) {
        $curl = new Curl;
        $curl->setHeader('Content-Type', 'application/json; charset=utf-8');
        $raw_data = $curl->post(
            $scfg['webhook_url'],
            json_encode($message_data)
        );

        if($curl->httpStatusCode != 200) {
            throw new TargetException(sprintf('Remote server returned %d: %s: %s', $curl->httpStatusCode, $curl->httpErrorMessage, $raw_data));
        }
    }
}
