<?php

namespace FOO;

class ES_Search extends Elasticsearch_Search {
    public static $TYPE = '_doc';

    public static function getSources() {
        $sources = [];
        foreach(parent::getSources() as $source) {
            if($source != 'alerts') {
                $sources[] = $source;
            }
        }
        return $sources;
    }
}
