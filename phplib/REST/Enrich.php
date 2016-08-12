<?php

namespace FOO;

/**
 * Class Enrich_REST
 * REST endpoint for retrieving enrichment data.
 * @package FOO
 */
class Enrich_REST extends REST {
    public function POST(array $get, array $data) {
        $errors = [];

        // Loop over each object we need to process.
        foreach($data as &$obj) {
            // Loop over every key we need to process.
            foreach($obj as $k=>$vdata) {
                list($enrichers, $v) = $vdata;
                // Loop over every enricher we need to run.
                foreach($enrichers as $type) {
                    $ENRICHER = Enricher::getEnricher($type);
                    if(is_null($ENRICHER)) {
                        $errors[] = sprintf('Invalid enricher: %s', $type);
                        continue;
                    }
                    try {
                        $v = $ENRICHER::process($v);
                    } catch(EnricherException $e) {
                        $errors[] = sprintf("Enricher %s, Field %s: %s", $type, $k, $e->getMessage());
                    }
                }
                $obj[$k] = $v;
            }
        }

        return self::format($data, !count($errors), $errors);
    }
};
