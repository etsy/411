<?php

namespace FOO;

/**
 * PagerDuty Target Class
 * Creates an incident for an Alert.
 */
class PagerDuty_Target extends Target {
    public static $TYPE = 'pagerduty';

    public static $DESC = 'Create an incident for this alert.';

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
        $contexts = [];
        if(!$alert->isNew()) {
            $contexts[] = [
                'type' => 'link',
                'href' => sprintf('https://%s/alert/%d', $site['host'], $alert['alert_id']),
                'text' => '411 Alert'
            ];
        }
        foreach($alert['content'] as $key=>$value) {
            $desc[$key] = $value;
        }

        $search = SearchFinder::getById($alert['search_id']);

        $incident_key = self::createEvent(
            [
                'service_key' => $this->obj['data']['service_key'],
                'event_type' => 'trigger',
                'client' => '411',
                'description' => sprintf('[%s] %s', $site['name'], $search['name']),
                'details' => json_encode($desc),
                'contexts' => $contexts,
            ]
        );
    }

    /**
     * Create a PagerDuty Event.
     * @param mixed[] $event_data Event data.
     * @return string|null The incident key or null.
     */
    public static function createEvent($event_data) {
        $curl = new Curl;
        $curl->setHeader('Content-type', 'application/json');
        $raw_data = $curl->post(
            'https://events.pagerduty.com/generic/2010-04-15/create_event.json',
            json_encode($event_data)
        );

        if($curl->httpStatusCode != 200) {
            throw new TargetException(sprintf('Remote server returned %d: %s: %s', $curl->httpStatusCode, $curl->httpErrorMessage, $raw_data));
        }

        return $raw_data['incident_key'];
    }
}
