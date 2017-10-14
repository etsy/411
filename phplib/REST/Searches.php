<?php

namespace FOO;

/**
 * Class Searches_REST
 * REST endpoint for manipulating Searches.
 * @package FOO
 */
class Searches_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_SEARCH;
    const RANGE = 10;

    protected static $MODEL = 'Search';
    protected static $CREATABLE = [
        'name', 'source', 'type', 'query_data', 'description', 'category', 'tags',
        'priority', 'schedule_type', 'frequency', 'cron_expression', 'range',
        'notif_type', 'notif_format', 'notif_data', 'autoclose_threshold',
        'enabled', 'owner', 'assignee_type', 'assignee'
    ];
    protected static $QUERYABLE = [
        'type', 'source', 'tags', 'category', 'enabled', 'owner', 'assignee_type',
        'assignee'
    ];
    protected static $READABLE = null;
    protected static $UPDATEABLE = [
        'name', 'source', 'query_data', 'description', 'category', 'tags', 'priority',
        'schedule_type', 'frequency', 'cron_expression', 'notif_type',
        'notif_format', 'notif_data', 'autoclose_threshold', 'range',
        'enabled', 'owner', 'assignee_type', 'assignee', 'renderer_data'
    ];

    /** @var Search Cached Search object. */
    protected $old_model = null;

    protected function construct($data=null) {
        $type = Util::get($data, 'type', '');

        return Search::newSearch($type);
    }

    public function GET(array $get) {
        $action = Util::get($get, 'action');

        switch($action) {
            case 'stats';
                return self::format($this->stats($get));
            default:
                return self::format($this->read($get));
        }
    }

    public function POST(array $get, array $data) {
        $action = Util::get($get, 'action');

        switch($action) {
            case 'preview':
                return self::format($this->preview($get, $data));
            case 'test':
                return self::format($this->test($get, $data));
            case 'execute':
                return self::format($this->execute($get, $data));
            default:
                return self::format($this->create($get, $data));
        }
    }

    public function allowRead() {
        return Auth::isAuthenticated();
    }
    public function allowCreate() {
        return Auth::isAuthenticated();
    }
    public function allowUpdate() {
        return Auth::isAuthenticated();
    }
    public function allowDelete() {
        return Auth::isAuthenticated();
    }

    public function beforeStore($model, $data, $new, $delete) {
        if($delete) {
            return;
        }

        $description = Util::get($data, 'change_description', '');
        if(!$new && empty(trim($description))) {
            throw new ValidationException('No change description provided');
        }

        // Cache the old model.
        if(!$new) {
            $this->old_model = SearchFinder::getById($model['search_id']);
        }
    }

    public function afterStore($model, $data, $new, $delete) {
        $fields = ['tags', 'priority', 'category', 'owner'];

        // Trigger a job to delete all Alerts if the Search was deleted.
        if($delete) {
            $deljob = new Delete_Job();
            $deljob['target_id'] = $model[Search::$PKEY];
            $deljob['target_date'] = $_SERVER['REQUEST_TIME'];
            $deljob->store();
        // Trigger a job to update any Alerts if we've changed any denormalized fields.
        } elseif($this->old_model) {
            foreach($fields as $field) {
                if($model[$field] == $this->old_model[$field]) {
                    continue;
                }

                $syncjob = new Sync_Job();
                $syncjob['target_id'] = $model[Search::$PKEY];
                $syncjob['target_date'] = $_SERVER['REQUEST_TIME'];
                $syncjob->store();
                // We don't want to schedule a Sync for every field that's changed.
                break;
            }
        }

        $this->old_model = null;

        // Log change.
        $description = Util::get($data, 'change_description', '');
        if($new) {
            $description = 'Initial entry';
        }

        $log = new SearchLog();
        $log['user_id'] = Auth::getUserId();
        $log['search_id'] = $model[Search::$PKEY];
        $log['data'] = $model->toArray();
        $log['description'] = $description;

        $log->store();
    }

    public function preview($get, $data) {
        if(!$this->allowCreate()) {
            throw new ForbiddenException;
        }

        $id = Util::get($data, 'id');
        $type = Util::get($data, 'type', '');

        $schema = Search::getSchema();

        $search = SearchFinder::getById($id);
        if(!$search) {
            $search = Search::newSearch($type);
        }
        foreach($data as $k=>$v) {
            if(!Util::exists($schema, $k)) {
                continue;
            }
            $search[$k] = $v;
        }
        $search->validate();

        $alerts = [];
        $alertkeys = array_keys($data['renderer_data']);
        $vertical = false;
        for($i = 0; $i < 3; ++$i) {
            $alert = new Alert;
            $alert['alert_date'] = $_SERVER['REQUEST_TIME'];
            $alert['content'] = [];
            foreach($search['renderer_data'] as $k=>$v) {
                $alert['content'][$k] = 'test';
            }
            $alerts[] = $alert;
        }

        return self::format(Notification::render('alerts', [
            'search' => $search,
            'alerts' => Notification::renderAlerts($search, $alerts),
            'alertkeys' => $alertkeys,
            'content_only' => false,
            'vertical' => $vertical,
        ]));
    }

    public function test($get, $data) {
        if(!$this->allowCreate()) {
            throw new ForbiddenException;
        }

        $id = Util::get($data, 'id');
        $type = Util::get($data, 'type', '');

        $schema = Search::getSchema();

        $search = SearchFinder::getById($id);
        if(!$search) {
            $search = Search::newSearch($type);
        }
        foreach($data as $k=>$v) {
            if(!Util::exists($schema, $k)) {
                continue;
            }
            $search[$k] = $v;
        }
        $search->validate();

        $execution_time = (int) Util::get($data, 'execution_time', 0);
        if($execution_time == 0) {
            $execution_time = $_SERVER['REQUEST_TIME'];
        }

        $searchjob = new Search_Job();
        $searchjob['target_date'] = $execution_time;
        list($alerts, $errors, $ignorable) = $searchjob->_run(false, $search, true);
        $this->slog(SLog::AS_TEST, $search['id']);
        return self::format($alerts, is_null($alerts), $errors);
    }

    public function execute($get, $data) {
        if(!$this->allowCreate()) {
            throw new ForbiddenException;
        }

        $id = Util::get($data, 'id');

        $schema = Search::getSchema();

        $search = SearchFinder::getById($id);
        foreach($data as $k=>$v) {
            if(!Util::exists($schema, $k)) {
                continue;
            }
            $search[$k] = $v;
        }
        $search->validate();

        $execution_time = (int) Util::get($data, 'execution_time', 0);
        if($execution_time == 0) {
            $execution_time = $_SERVER['REQUEST_TIME'];
        }

        $searchjob = new Search_Job();
        $searchjob['target_date'] = $execution_time;
        list($alerts, $errors, $ignorable) = $searchjob->_run(true, $search, true);
        $this->slog(SLog::AS_EXECUTE, $search['id']);
        return self::format($alerts, is_null($alerts), $errors);
    }

    public function stats($data) {
        if(!$this->allowRead()) {
            throw new ForbiddenException;
        }

        $id = Util::get($data, 'id');
        $model = SearchFinder::getById($id);
        if(!$model) {
            throw new NotFoundException;
        }

        $client = new ESClient;
        $data = [];
        $stats = [];

        $data['historical_alerts'] = $client->getAlertActivityCounts(self::RANGE, $id);

        $stats['Flap rate'] = $model['flap_rate'];

        // Get count of total alerts.
        list($sql, $vals) = AlertFinder::generateQuery(
            ['state', 'COUNT(*) as count'],
            ['search_id' => $id],
        null, null, [], ['state']);

        $ret = DB::query(implode(' ', $sql), $vals);
        $active = [0, 0];
        foreach($ret as $row) {
            $i = $row['state'] != Alert::ST_RES ? 0:1;
            $active[$i] += $row['count'];
        }
        $stats['Total'] = array_sum($active);
        $stats['Active'] = $active[0];

        // Get count of escalated alerts.
        $ret = AlertFinder::countByQuery([
            'search_id' => $id,
            'escalated' => 1
        ]);
        $stats['Escalated'] = is_null($ret) ? 0:(int)$ret;

        // Get count of resolved alerts
        list($sql, $vals) = AlertFinder::generateQuery(
            ['resolution', 'COUNT(*) as count'],
            ['search_id' => $id, 'state' => Alert::ST_RES],
        null, null, [], ['resolution']);

        $ret = DB::query(implode(' ', $sql), $vals);
        $groups = [0, 0, 0];
        foreach($ret as $row) {
            $groups[$row['resolution']] += $row['count'];
        }
        $stats['Resolved: Not an issue'] = $groups[0];
        $stats['Resolved: Action taken'] = $groups[1];
        $stats['Resolved: Too old'] = $groups[2];

        // Get timestamp of most recent alert.
        list($sql, $vals) = AlertFinder::generateQuery(
            ['MAX(`alert_date`) as date'],
            ['search_id' => $id]
        );

        $ret = DB::query(implode(' ', $sql), $vals, DB::VAL);
        $stats['Last alert'] = is_null($ret) ? 'N/A':gmdate(DATE_RSS, $ret);

        $stats['Last execution'] = $model['last_execution_date'] == 0 ? 'N/A':gmdate(DATE_RSS, $model['last_execution_date']);
        $stats['Last successful execution'] = $model['last_success_date'] == 0 ? 'N/A':gmdate(DATE_RSS, $model['last_success_date']);

        $data['stats'] = $stats;

        return $data;
    }
};
