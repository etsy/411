<?php

namespace FOO;

/**
 * Class ECL_Search
 * Executes an ECL query.
 * @package FOO
 */
class ECL_Search extends Search {
    // Search type. Specify a unique string.
    public static $TYPE = 'ecl';

    protected function _getLink(Alert $alert) {
        return null;
    }

    public function generateLink($query, $start, $end) {
        return null;
    }

    public function isTimeBased() {
        return true;
    }

    public function isWorking($date) {
        return true;
    }

    protected function constructQuery() {
        return Util::get($this->obj['query_data'], 'query');
    }

    protected function _execute($date, $constructed_qdata) {
        // If our last_success_date is within 10 seconds of the start time, use that
        // as the start time.
        $from = $date - ($this->obj['range'] * 60);
        if(abs($this->obj['last_success_date'] - $from) < 10) {
            $from = $this->obj['last_success_date'];
        }
        $settings = [
            'from' => $from,
            'to' => $date,
            'size' => (floor($this->obj['range'] / 1440) + 1) * 500,
        ];

        $table = new \ECL\SymbolTable;
        $table['to'] = $date;
        $table['from'] = $from;

        $es_builder = new Builder;
        $es_builder->setSources(Config::get('elasticsearch'));
        $es_builder->setSettings($settings);
        $parser = new \ECL\Parser;
        $parser->setESBuilder($es_builder);

        $statementlist = $parser->parse($constructed_qdata, $table);
        $sch = new \ECL\Scheduler;
        $results = $sch->process($statementlist);

        $date_fields = $es_builder->getDateFields();

        $alerts = [];
        foreach($results as $result) {
            foreach($result->getAll() as $entry) {
                $alert = new Alert;
                $alert_date = $date;

                $has_date_field = false;
                foreach($date_fields as $date_field) {
                    if(array_key_exists($date_field, $entry)) {
                        $has_date_field = true;
                        break;
                    }
                }

                if ($has_date_field) {
                    // Extract the date field.
                    if(ctype_digit($entry[$date_field])) {
                        $alert_date = (int) $entry[$date_field];
                    } else {
                        $alert_date = strtotime($entry[$date_field]);
                    }
                    unset($entry[$date_field]);
                }
                $alert['alert_date'] = $alert_date;
                $alert['content'] = $entry;
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }
}

class Builder extends \ECL\Command\Elasticsearch\Builder {
    private $date_fields = [];

    public function build($source, array $query=[], $agg=null, array $settings=[]) {
        $cfg = Util::get($this->sources, $source);
        $date_field = Util::get($cfg, 'date_field');

        if(!is_null($date_field)) {
            $this->date_fields[] = $date_field;
            $this->date_fields = array_unique($this->date_fields);
        }

        return parent::build($source, $query, $agg, $settings);
    }

    public function getDateFields() {
        return $this->date_fields;
    }
}
