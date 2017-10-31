<?php

namespace FOO;

/**
 * Class ThreatExchange_Search
 * Runs a query against the Facebook ThreatExchange API.
 * @package FOO
 */
class ThreatExchange_Search extends Search {
    public static $TYPE = 'threatexchange';
    public static $CONFIG_KEY = 'threatexchange';

    const THREATEXCHANGE_QUERY_URL = "https://graph.facebook.com";
    const MALWARE_URL = "/malware_analyses";
    const MALWARE = "malware";
    const THREATS_URL = "/threat_indicators";
    const THREATS = "threats";

    public function isWorking($date) {
        $curl = new Curl;
        $raw_data = $curl->get('https://www.facebook.com/feeds/api_status.php');
        if($curl->httpStatusCode != 200 || !is_array($raw_data)) {
            return false;
        }
        return (bool) $raw_data['current']['health'];
    }

    public function isAccessible() {
        $tecfg = $this->getConfig();
        return !is_null($tecfg['api_token']);
    }

    protected function constructQuery() {
        $query = Util::get($this->obj['query_data'], 'query', '');
        $range = Util::get($this->obj['query_data'], 'range', 10);
        $type = Util::get($this->obj['query_data'], 'type', '');

        $data = [
            'query' => strtolower($query),
            'range' => $range,
            'id' => false,
        ];
        if (preg_match('/^(\d+)/', $query, $matches)) {
            $data['url'] = self::THREATEXCHANGE_QUERY_URL . "/" . $matches[0];
            $data['id'] = true;
        } else if ($type == self::MALWARE) {
            $data['url'] = self::THREATEXCHANGE_QUERY_URL . self::MALWARE_URL;
        } else if ($type == self::THREATS) {
            $data['url'] = self::THREATEXCHANGE_QUERY_URL . self::THREATS_URL;
        }
        return [];
    }

    protected function _execute($date, $constructed_qdata) {
        $tecfg = $this->getConfig();
        if(is_null($tecfg['api_token']) || is_null($tecfg['api_secret'])) {
            throw new SearchException('Threatexchange not configured');
        }

        $curl = new Curl;
        $since = $date - ($constructed_qdata['range'] * 60);
        $raw_data = $curl->get($constructed_qdata, [
           'access_token' => $tecfg['api_token'] . "|" . $tecfg['api_secret'],
           'text'         => $constructed_qdata['query'],
           'limit'        => '1000',
           'since'        => $since,
           'until'        => $date,
        ]);

        if($curl->httpStatusCode != 200) {
            throw new SearchException(sprintf('Remote server returned %d: %s: %s', $curl->httpStatusCode, $curl->httpErrorMessage, $raw_data));
        }

        $alerts = [];
        if ($constructed_qdata['id']) {
            $alert = new Alert();
            $alert['alert_date'] = $date;
            $alert['content'] = $raw_data;
            $alerts[] = $alert;
        } else {
            foreach ($raw_data['data'] as $result) {
                $alert = new Alert();
                $alert['alert_date'] = $date;
                $alert['content'] = (array) $result;
                $alerts[] = $alert;
            }
        }
        return $alerts;
    }
}
