<?php

namespace FOO;

/**
 * Class Alerts_REST
 * REST endpoint for manipulating Alerts.
 * @package FOO
 */
class Alerts_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_ALERT;

    public static $MODEL = 'Alert';
    public static $CREATABLE = [];
    public static $QUERYABLE = [
        'alert_date', 'assignee_type', 'assignee', 'search_id', 'state', 'escalated', 'from', 'to'
    ];
    public static $READABLE = null;
    public static $UPDATEABLE = [
        'renderer_data'
    ];

    /** @var ESClient Client wrapper for handling batch requests */
    private $client;

    public function __construct() {
        $this->client = new ESClient;
    }

    public function allowRead() {
        return Auth::isAuthenticated();
    }
    public function allowCreate() {
        return false;
    }
    public function allowUpdate() {
        return Auth::isAuthenticated();
    }
    public function allowDelete() {
        return Auth::isAuthenticated();
    }

    /**
     * Override authorization check.
     * The push endpoint can be accessed without auth, so we do that whitelisting here.
     */
    public function checkAuthorization() {
        if($_GET[''
        if(!Auth::isAuthenticated()) {
            throw new UnauthorizedException('Authentication required');
        }
    }

    public function beforeStore($model, $data, $new, $delete) {
        if(!$delete) {
            return;
        }

        $this->client->delete($model);
    }
    public function afterStore($model, $data, $new, $delete) {
        if($delete) {
            return;
        }

        $this->client->update($model);
    }
    public function finalizeStore() {
        $this->client->finalize();
    }

    public function GET(array $get) {
        $action = Util::get($get, 'action');

        switch($action) {
            case 'ids':
                return self::format($this->getIds($get));
            case 'link':
                return self::format($this->getLink($get));
            case 'bootstrap':
                return self::format($this->bootstrapAlerts($get));
            case 'query':
                return self::format($this->queryAlerts($get));
            default:
                return self::format($this->read($get));
        }
    }

    public function POST(array $get, array $data) {
        $action = Util::get($get, 'action');

        switch($action) {
            case 'send':
                return self::format($this->sendAlerts($data));
            case 'whitelist':
                return self::format($this->whitelistAlerts($data));
            case 'push':
                return self::format($this->push($get, $data));
            default:
                return self::format($this->create($get, $data));
        }
    }

    public function PUT(array $get, array $data) {
        $action = Util::get($get, 'action');

        switch($action) {
            case 'escalate':
                return self::format($this->setAlertEscalation($data));
            case 'switch':
                return self::format($this->setAlertState($data));
            case 'assign':
                return self::format($this->setAlertAssignee($data));
            case 'note':
                return self::format($this->addAlertNote($data));
            default:
                return self::format($this->update($get, $data));
        }
    }

    public function getIds($get) {
        if(!$this->allowRead()) {
            throw new ForbiddenException;
        }

        $query = Util::get($get, 'query', '');
        $from = Util::get($get, 'from');
        $to = Util::get($get, 'to');

        return $this->client->getIds($query, $from, $to);
    }

    public function bootstrapAlerts($get) {
        if(!$this->allowRead()) {
            throw new ForbiddenException;
        }

        $query = Util::get($get, 'query', '');
        $from = Util::get($get, 'from');
        $to = Util::get($get, 'to');

        return $this->client->bootstrap($query, $from, $to);
    }

    public function queryAlerts($get) {
        if(!$this->allowRead()) {
            throw new ForbiddenException;
        }

        $query = Util::get($get, 'query', '');
        $from = Util::get($get, 'from');
        $to = Util::get($get, 'to');
        $offset = Util::get($get, 'offset');
        $count = Util::get($get, 'count');

        return $this->client->getAlerts($query, $from, $to, $offset, $count);
    }

    public function getLink($get) {
        if(!$this->allowRead()) {
            throw new ForbiddenException;
        }

        $id = Util::get($get, 'id');
        $alert = AlertFinder::getById($id);
        $search = SearchFinder::getById($alert['search_id']);

        return ['link' => $search->getLink($alert)];
    }

    public function whitelistAlerts($data) {
        if(!$this->allowUpdate()) {
            throw new ForbiddenException;
        }

        $lifetime = (int)Util::get($data, 'lifetime');
        $description = (string)Util::get($data, 'description', '');
        $ids = (array)Util::get($data, 'id');

        $ret = ['count' => 0];
        $hashes = [];
        foreach($ids as $id) {
            $alert = AlertFinder::getById($id);
            if(is_null($alert)) {
                continue;
            }
            $search_id = $alert['search_id'];

            if(!Util::exists($hashes, $search_id)) {
                $hashes[$search_id] = [];
            }
            $hashes[$search_id][$alert['content_hash']] = null;
            ++$ret['count'];
        }

        foreach($hashes as $search_id=>$list) {
            $filter = new Hash_Filter();
            $filter['search_id'] = $search_id;
            $filter['lifetime'] = $lifetime;
            $filter['description'] = $description;
            $filter['data']['list'] = array_keys($list);
            $filter->store();
        }

        return $ret;
    }

    public function sendAlerts($data) {
        if(!$this->allowUpdate()) {
            throw new ForbiddenException;
        }

        $target_data = Util::get($data, 'target');
        $type = Util::get($target_data, 'type');
        $ids = (array)Util::get($data, 'id');

        $ret = ['count' => 0];
        $target = Target::newTarget($type);
        $target['data'] = (array)Util::get($target_data, 'data');
        $target->validate();

        $errors = [];
        foreach($ids as $id) {
            $alert = AlertFinder::getById($id);
            if(!is_null($alert)) {
                try {
                    $target->process($alert, $_SERVER['REQUEST_TIME']);
                    $ret['count'] += 1;
                } catch(TargetException $e) {
                    $errors[] = sprintf('Target %s: %s', $target['type'], $e->getMessage());
                }
            }
        }
        try {
            $target->finalize($_SERVER['REQUEST_TIME']);
        } catch(TargetException $e) {
            $errors[] = sprintf('Target %s: %s', $target['type'], $e->getMessage());
        }

        return self::format($ret, !count($errors), $errors);
    }

    public function addAlertNote($data) {
        return $this->setAlertFields(
            $data,
            [],
            AlertLog::A_NOTE, SLOG::AA_NOTE
        );
    }

    public function setAlertEscalation($data) {
        return $this->setAlertFields(
            $data,
            ['escalated'],
            AlertLog::A_ESCALATE, SLog::AA_ESCALATE,
            (int) Util::get($data, 'escalated')
        );
    }

    public function setAlertState($data) {
        $fields = ['state'];
        if(Util::get($data, 'state', null) === 2) {
            $fields[] = 'resolution';
        }

        return $this->setAlertFields(
            $data,
            $fields,
            AlertLog::A_SWITCH, SLog::AA_SWITCH,
            (int) Util::get($data, 'state'), (int) Util::get($data, 'resolution')
        );
    }

    public function setAlertAssignee($data) {
        return $this->setAlertFields(
            $data,
            ['assignee_type', 'assignee'],
            AlertLog::A_ASSIGN, SLog::AA_ASSIGN,
            (int) Util::get($data, 'assignee_type'), (int) Util::get($data, 'assignee')
        );
    }

    private function setAlertFields($data, $fields, $alog_action, $slog_action, $a=0, $b=0) {
        if(!$this->allowUpdate()) {
            throw new ForbiddenException;
        }

        // All fields are required.
        foreach($fields as $field) {
            if(is_null(Util::get($data, $field))) {
                throw new InternalErrorException('Required field missing');
            }
        }

        $ids = (array) Util::get($data, 'id');
        $ret = ['count' => 0];
        $log = null;

        // Apply changes to the Alerts, and keep track of each Alert.
        $alert_groups = [[], []];
        foreach($ids as $id) {
            $alert = AlertFinder::getById($id);
            if(is_null($alert)) {
                continue;
            }
            foreach($fields as $field) {
                $alert[$field] = Util::get($data, $field);
            }
            $alert->store();

            // Generate a log entry for this change.
            $log = new AlertLog();
            $log['user_id'] = Auth::getUserId();
            $log['alert_id'] = $alert['id'];
            $log['note'] = Util::get($data, 'note', '');
            $log['action'] = $alog_action;
            $log['a'] = $a;
            $log['b'] = $b;
            $log->store();

            $this->slog($slog_action, $alert['id'], $a, $b);

            $this->afterStore($alert, [], false, false);

            $ret['count'] += 1;
            $assignee_type = $alert['assignee_type'];
            $assignee = $alert['assignee'];
            if(!Util::exists($alert_groups[$assignee_type], $assignee)) {
                $alert_groups[$assignee_type][$assignee] = [];
            }
            $alert_groups[$assignee_type][$assignee][] = $alert;
        }

        $this->finalizeStore();

        // Fire off notifications for each batch of Alerts that were updated.
        $searches = [];
        foreach($alert_groups as $assignee_type=>$alert_group) {
            foreach($alert_group as $assignee=>$alerts) {
                // Filter out Alerts that shouldn't generate a notification.
                $filtered_alerts = [];
                foreach($alerts as $alert) {
                    $search_id = $alert['search_id'];
                    if(!Util::exists($searches, $search_id)) {
                        $searches[$search_id] = SearchFinder::getById($search_id, true);
                    }
                    $search = $searches[$search_id];

                    // Only add to the list if escalated or the Search has on-demand notifs.
                    if($alert['escalated'] || $search['notif_type'] >= Search::NT_ONDEMAND) {
                        $filtered_alerts[] = $alert;
                    }
                }

                if(count($filtered_alerts)) {
                    $to = Assignee::getEmails($assignee_type, $assignee);
                    Notification::sendAlertActionEmail($to, $log, $searches, $filtered_alerts);
                }
            }
        }

        return $ret;
    }

    public function push($get, $data) {
        $search_id = Util::get($get, 'search_id', 0);
        $search = SearchFinder::getById($search_id);

        // Verify that we have a Push_Search and the key is correct.
        if(
            is_null($search) || $search['type'] != Push_Search::$TYPE || !$search['enabled'] ||
            Util::get($search['query_data'], 'key', '') != Util::get($get, 'key', '')
        ) {
            throw new ForbiddenException;
        }

        $search->setResults($data);

        $searchjob = new Search_Job();
        $searchjob['target_date'] = $_SERVER['REQUEST_TIME'];
        list($alerts, $errors) = $searchjob->_run(true, $search);
        $this->slog(SLog::AS_EXECUTE, $search['id']);
        return self::format($alerts, is_null($alerts), $errors);
    }
}
