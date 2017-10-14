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

        $message = implode(' ', [
            is_null($search) ? 'Unknown':sprintf('<%s|%s>',
                self::escape($site->urlFor(sprintf('search/%d', $search['id']))),
                self::escape($search['name'])
            ),
            $alert->isNew() ? '[N/A]':sprintf('[<%s|Alert>]', self::escape($site->urlFor(
                sprintf('alert/%d', $alert['alert_id'])
            )))
        ]);

        $username = $scfg['username'] ? $scfg['username']:Util::getSiteName();
        $icon = $scfg['icon'] ? $scfg['icon']:':warning:';
        $message_data = [
            'channel' => $this->obj['data']['channel'],
            'username' => self::escape($username),
            'icon_emoji' => self::escape($icon),
            'text' => '',
            'attachments' => [[
                'pretext' => self::escape($message),
                'fields' => $fields,
                'ts' => $alert['alert_date'],
            ]],
        ];
        list($message_data) = Hook::call('filter.slack.send', [$message_data]);

        self::createMessage($message_data);
    }

    public static function escape($text) {
        return str_replace(
            ['&', '<', '>'],
            ['&amp;', '&lt;', '&gt;'],
            $text
        );
    }

    /**
     * Send a message to Slack.
     * @param mixed[] $message_data Message data.
     */
    public static function createMessage($message_data) {
        $scfg = Config::get('slack');
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
