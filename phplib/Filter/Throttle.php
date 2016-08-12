<?php

namespace FOO;

/**
 * Class Throttle_Filter
 * Throttles the amount of alerts from a Search.
 * @package FOO
 */
class Throttle_Filter extends Filter {
    public static $TYPE = 'throttle';

    public static $DESC = 'Throttles Alerts to <count> for the last <range> minutes';

    private $counts = [];

    protected static function generateDataSchema() {
        return [
            'range' => [static::T_NUM, null, 0],
            'count' => [static::T_NUM, null, 0]
        ];
    }
    /**
     * Return the Alert if there have been no recent Alerts like it.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] The Alert object.
     */
    public function process(Alert $alert, $date) {
        $ret = [];

        $search_id = $alert['search_id'];
        if(!Util::exists($this->counts, $search_id)) {
            $this->counts[$search_id] = AlertFinder::getRecentSearchCount(
                $alert['search_id'],
                $date - ($this->obj['data']['range'] * 60)
            );
        } else {
            ++$this->counts[$search_id];
        }

        if($this->counts[$search_id] < $this->obj['data']['count']) {
            $ret[] = $alert;
        }
        return $ret;
    }
}
