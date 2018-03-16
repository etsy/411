<?php

namespace FOO;

/**
 * Class Enricher_Filter
 * Adaptor to allow executing Enrichers on Alerts during the filtering stage.
 * @package FOO
 */
class Enricher_Filter extends Filter {
    public static $TYPE = 'enricher';

    public static $DESC = 'Execute an enricher on the Alert.';

    public static function generateDataSchema() {
        $type_map = [];
        foreach(Enricher::$TYPES as $type) {
            $name = explode('_', $type)[0];
            $type_map[$type::$TYPE] = $name;
        }
        return [
            'key' => [static::T_STR, null, '*'],
            'type' => [static::T_ENUM, $type_map, '']
        ];
    }

    /**
     * Apply a filter.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] The Alert object.
     */
    public function process(Alert $alert, $date) {
        $key = $this->obj['data']['key'];
        $type = $this->obj['data']['type'];

        if(Util::exists($alert['content'], $key)) {
            $enricher = Enricher::getEnricher($type);
            $val = $enricher::process($alert['content'][$key]);
            if(is_object($val)) {
                $val = json_encode($val);
            }
            $alert['content'][$key] = $val;
        }

        return [$alert];
    }
}
