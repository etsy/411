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

        $alert_url = $site->urlFor('/');
        if(!$alert->isNew()) {
            $alert_url = $site->urlFor(sprintf('alert/%d', $alert['alert_id']));
        }

        foreach($alert['content'] as $key=>$value) {
            $desc[$key] = $value;
        }

        $search = SearchFinder::getById($alert['search_id']);

        $priority_map = [
            Search::P_LOW => 'info',
            Search::P_MED => 'warning',
            Search::P_HIGH => 'error',
        ];

        $event_data = [
            'client' => $site['name'],
            'client_url' => $alert_url,
            'event_action' => 'trigger',
            'routing_key' => $this->obj['data']['service_key'],

            'payload' => [
                'summary' => $search['name'],
                'source' => $search::$TYPE,
                'severity' => $priority_map[$search['priority']],
                'timestamp' => date('c', $alert['alert_date']),
                'custom_details' => $desc,
            ],
        ];
        list($event_data) = Hook::call('target.pagerduty.send', [$event_data]);

        $incident_key = self::createEvent($event_data);
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
            'https://events.pagerduty.com/v2/enqueue',
            json_encode($event_data)
        );

        if($curl->httpStatusCode != 202) {
            throw new TargetException(sprintf('Remote server returned %d: %s: %s', $curl->httpStatusCode, $curl->httpErrorMessage, json_encode($raw_data)));
        }

        return $raw_data['dedup_key'];
    }
}
