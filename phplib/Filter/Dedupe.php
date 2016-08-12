<?php

namespace FOO;

/**
 * Class Dedupe_Filter
 * Eliminates duplicate Alerts. If there has been a recent alert with the same content
 * hash, delete the current one.
 * @package FOO
 */
class Dedupe_Filter extends Filter {
    public static $TYPE = 'dedupe';

    public static $DESC = 'Remove Alerts that are duplicates of Alerts from the last <range> minutes';

    private $hash_counts = [];

    protected static function generateDataSchema() {
        return [
            'range' => [static::T_NUM, null, 0]
        ];
    }

    /**
     * Return the Alert if there have been no recent Alerts like it.
     *
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] The Alert object.
     */
    public function process(Alert $alert, $date) {
        $ret = [];

        $search_id = $alert['search_id'];
        $content_hash = $alert['content_hash'];
        if(!Util::exists($this->hash_counts, $search_id)) {
            $this->hash_counts[$search_id] = [];
        }
        if(!Util::exists($this->hash_counts[$search_id], $content_hash)) {
            $this->hash_counts[$search_id][$content_hash] = AlertFinder::getRecentSearchHashCount(
                $alert['search_id'],
                $alert['content_hash'],
                $date - ($this->obj['data']['range'] * 60)
            );
        } else {
            ++$this->hash_counts[$search_id][$content_hash];
        }

        if($this->hash_counts[$search_id][$content_hash] == 0) {
            $ret[] = $alert;
        }
        return $ret;
    }
}
