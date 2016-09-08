<?php

namespace FOO;

/**
 * Class Searches_REST
 * REST endpoint for manipulating Searches.
 * @package FOO
 */
class Searches_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_SEARCH;

    protected static $MODEL = 'Search';
    protected static $CREATABLE = [
        'name', 'type', 'query_data', 'description', 'category', 'tags',
        'priority', 'schedule_type', 'frequency', 'cron_expression', 'range',
        'notif_type', 'notif_format', 'notif_data', 'autoclose_threshold',
        'enabled', 'owner', 'assignee_type', 'assignee'
    ];
    protected static $QUERYABLE = [
        'type', 'tags', 'category', 'enabled', 'owner', 'assignee_type',
        'assignee'
    ];
    protected static $READABLE = null;
    protected static $UPDATEABLE = [
        'name', 'query_data', 'description', 'category', 'tags', 'priority',
        'schedule_type', 'frequency', 'cron_expression', 'notif_type',
        'notif_format', 'notif_data', 'autoclose_threshold', 'range',
        'enabled', 'owner', 'assignee_type', 'assignee', 'renderer_data'
    ];

    /** @var Search Cached Search object. */
    protected $old_model = null;

    protected function construct($data=null) {
        $type = Util::get($data, 'type', '');

        $MODEL = 'FOO\\' . static::$MODEL;
        return $MODEL::newSearch($type);
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
        $MODEL = 'FOO\\' . static::$MODEL;

        $fields = ['tags', 'priority', 'category', 'owner'];

        // Trigger a job to update any Alerts if we've changed any denormalized fields.
        if($this->old_model) {
            foreach($fields as $field) {
                if($model[$field] == $this->old_model[$field]) {
                    continue;
                }

                $syncjob = new Sync_Job();
                $syncjob['target_id'] = $model[$MODEL::$PKEY];
                $syncjob['target_date'] = $_SERVER['REQUEST_TIME'];
                $syncjob->store();
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
        $log['search_id'] = $model[$MODEL::$PKEY];
        $log['data'] = $model->toArray();
        $log['description'] = $description;

        $log->store();
    }

    public function test($get, $data) {
        if(!$this->allowCreate()) {
            throw new ForbiddenException;
        }

        $id = Util::get($data, 'id');
        $type = Util::get($data, 'type', '');

        $MODEL = 'FOO\\' . static::$MODEL;
        $FINDER = $MODEL . 'Finder';
        $schema = $MODEL::getSchema();

        $search = $FINDER::getById($id);
        if(!$search) {
            $search = $MODEL::newSearch($type);
        }
        foreach($data as $k=>$v) {
            if(!Util::exists($schema, $k)) {
                continue;
            }
            $search[$k] = $v;
        }
        $search->validate();

        $searchjob = new Search_Job();
        $searchjob['target_date'] = $_SERVER['REQUEST_TIME'];
        list($alerts, $errors) = $searchjob->_run(false, $search);
        $this->slog(SLog::AS_TEST, $search['id']);
        return self::format($alerts, is_null($alerts), $errors);
    }

    public function execute($get, $data) {
        if(!$this->allowCreate()) {
            throw new ForbiddenException;
        }

        $id = Util::get($data, 'id');

        $MODEL = 'FOO\\' . static::$MODEL;
        $FINDER = $MODEL . 'Finder';
        $schema = $MODEL::getSchema();

        $search = $FINDER::getById($id);
        foreach($data as $k=>$v) {
            if(!Util::exists($schema, $k)) {
                continue;
            }
            $search[$k] = $v;
        }
        $search->validate();

        $searchjob = new Search_Job();
        $searchjob['target_date'] = $_SERVER['REQUEST_TIME'];
        list($alerts, $errors) = $searchjob->_run(true, $search);
        $this->slog(SLog::AS_EXECUTE, $search['id']);
        return self::format($alerts, is_null($alerts), $errors);
    }

    public function stats($data) {
        if(!$this->allowRead()) {
            throw new ForbiddenException;
        }

        $id = Util::get($data, 'id');
        $FINDER = 'FOO\\' . static::$MODEL . 'Finder';
        $model = $FINDER::getById($id);
        if(!$model) {
            throw new NotFoundException;
        }

        $data = [];

        $data['Flap rate'] = $model['flap_rate'];

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
        $data['Total'] = array_sum($active);
        $data['Active'] = $active[0];

        // Get count of escalated alerts.
        $ret = AlertFinder::countByQuery([
            'search_id' => $id,
            'escalated' => 1
        ]);
        $data['Escalated'] = is_null($ret) ? 0:(int)$ret;

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
        $data['Resolved: Not an issue'] = $groups[0];
        $data['Resolved: Action taken'] = $groups[1];
        $data['Resolved: Too old'] = $groups[2];

        // Get timestamp of most recent alert.
        list($sql, $vals) = AlertFinder::generateQuery(
            ['MAX(`alert_date`) as date'],
            ['search_id' => $id]
        );

        $ret = DB::query(implode(' ', $sql), $vals, DB::VAL);
        $data['Last alert'] = is_null($ret) ? 'N/A':gmdate(DATE_RSS, $ret);

        $data['Last execution'] = $model['last_execution_date'] == 0 ? 'N/A':gmdate(DATE_RSS, $model['last_execution_date']);
        $data['Last successful execution'] = $model['last_success_date'] == 0 ? 'N/A':gmdate(DATE_RSS, $model['last_success_date']);

        return $data;
    }
};
