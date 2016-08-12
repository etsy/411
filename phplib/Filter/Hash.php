<?php

namespace FOO;

/**
 * Class Hash_Filter
 * Eliminates duplicate Alerts matching a list of hashes.
 * @package FOO
 */
class Hash_Filter extends Filter {
    public static $TYPE = 'hash';

    public static $DESC = 'Remove Alerts that match a hash in <list>.';

    protected static function generateDataSchema() {
        return [
            'list' => [static::T_ARR, static::T_STR, []]
        ];
    }

    /**
     * Return the Alert if it doesn't match any of the hashes.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] The Alert object.
     */
    public function process(Alert $alert, $date) {
        foreach($this->obj['data']['list'] as $hash) {
            if($alert['content_hash'] == $hash) {
                return [];
            }
        }
        return [$alert];
    }
}
