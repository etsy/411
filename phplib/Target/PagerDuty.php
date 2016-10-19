<?php

namespace FOO;

/**
 * PagerDuty Target Class
 * Creates an event for an Alert.
 */
class PagerDuty_Target extends Target {
    public static $TYPE = 'pagerduty';

    public static $DESC = 'Create a incident for this alert and assign to <assignee>.';

    protected static function generateDataSchema() {
        return [
            'service_key' => [static::T_STR, null, '']
        ];
    }

    /**
     * Create event.
     *
     * @param Alert An Alert object
     * @param int $date The current date.
     */
    public function process(Alert $alert, $date) {
        $site = SiteFinder::getCurrent();
        $desc = [
          'date' => sprintf('%s', gmdate(DATE_RSS, $alert['alert_date'])),

        ];

        // Don't show the link if this event isn't persisted.
        if(!$alert->isNew()) {
            $desc['link_to_alert'] = sprintf('https://%s/alert/%d', $site['host'], $alert['alert_id']);
        }
        foreach($alert['content'] as $key=>$value) {
            $desc[$key] = $value;
        }

        $search = SearchFinder::getById($alert['search_id']);

        $ret = self::createEvent(
            [
                'service_key' => $this->obj['data']['service_key'],
                'event_type' => 'trigger',
                'client' => '411',
                'description' => sprintf('[%s] %s', $site['name'], $search['name']),
                'details' => json_encode($desc),
            ]
        );
        if(!$ret) {
            throw new TargetException(sprintf('Failed to send Event:%d to PagerDuty', $alert['alert_id']));
        }
    }

    /**
     * Create a PagerDuty Event.
     * @param mixed[] $event_data Event data.
     * @return string|null The incident key or null.
     */
    public static function createEvent($event_data) {
        $curl = new \Curl\Curl;
        $curl->setHeader('Content-type', 'application/json');
        $ret = $curl->post(
            'https://events.pagerduty.com/generic/2010-04-15/create_event.json',
            json_encode($event_data)
        );

        if($curl->httpStatusCode < 200 || $curl->httpStatusCode >= 300) {
            return null;
        }

        return $ret->incident_key;
    }
}
