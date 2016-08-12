<?php

namespace FOO;

/**
 * Class Search
 * An Search generates Alert objects which are then passed through the rest
 * of the pipeline.
 * @package FOO
 */
abstract class Search extends TypeModel {
    public static $TYPES = ['Null_Search', 'Logstash_Search', 'ThreatExchange_Search', 'Ping_Search', 'HTTP_Search', 'Alert_Search', 'Graphite_Search'];
    public static $TABLE = 'searches';
    public static $PKEY = 'search_id';

    // Categories.
    public static $CATEGORIES = [
        'general' => 'General',
        'server' => 'Server',
        'client' => 'Client',
        'security' => 'Security',
    ];

    // Threshold for flapping detection.
    const FLAP_THRES = 0.4;
    // How much weight to give to a change in Search state.
    const FLAP_WEIGHT = 0.2;

    // Priorities.
    /** Low priority. */
    const P_LOW = 0;
    /** Medium priority. */
    const P_MED = 1;
    /** High priority. */
    const P_HIGH = 2;
    /** @var Mapping of priorities to a user-friendly string. */
    public static $PRIORITIES = [
        self::P_LOW => 'Low',
        self::P_MED => 'Medium',
        self::P_HIGH => 'High',
    ];

    // Frequency types.
    /** Interval based frequency. */
    const SCT_FREQ = 0;
    /** Cron based frequency. */
    const SCT_CRON = 1;
    /** @var Mapping of frequency types to a user-friendly string. */
    public static $SCHEDULE_TYPES = [
        self::SCT_FREQ => 'Frequency',
        self::SCT_CRON => 'Cron'
    ];

    // Notification schedules types.
    /** No notifications. */
    const NT_NONE = 0;
    /** As Alerts come in. */
    const NT_ONDEMAND = 1;
    /** Hourly. */
    const NT_HOURLY = 2;
    /** Daily. */
    const NT_DAILY = 3;
    /** @var Mapping of notification schedules to a user-friendly string. */
    public static $NOTIF_TYPES = [
        self::NT_NONE => 'None',
        self::NT_ONDEMAND => 'On demand',
        self::NT_HOURLY => 'Hourly',
        self::NT_DAILY => 'Daily',
    ];

    // Notification formats.
    /** Full format. */
    const NF_FULL = 0;
    /** Alert content only format. */
    const NF_CONTENTONLY = 1;
    /** @var Mapping of formats to a user-friendly string. */
    public static $NOTIF_FORMATS = [
        self::NF_FULL => 'Full',
        self::NF_CONTENTONLY => 'Content only',
    ];

    protected static function generateSchema() {
        return [
            'name' => [self::T_STR, null, ''],
            'query_data' => [self::T_OBJ, null, []],
            'state_data' => [self::T_OBJ, null, []],
            'renderer_data' => [self::T_OBJ, null, []],
            'description' => [self::T_STR, null, ''],
            'category' => [self::T_ENUM, static::$CATEGORIES, ''],
            'tags' => [self::T_ARR, self::T_STR, []],
            'priority' => [self::T_ENUM, static::$PRIORITIES, self::P_LOW],
            'schedule_type' => [self::T_ENUM, static::$SCHEDULE_TYPES, self::SCT_FREQ],
            'frequency' => [self::T_NUM, null, 1],
            'cron_expression' => [self::T_STR, null, '* * * * *'],
            'range' => [self::T_NUM, null, 1],
            'enabled' => [self::T_BOOL, null, true],
            'assignee_type' => [self::T_ENUM, Assignee::$TYPES, Assignee::T_USER],
            'assignee' => [self::T_NUM, null, User::NONE],
            'owner' => [self::T_NUM, null, User::NONE],
            'flap_rate' => [self::T_NUM, null, 0],
            'notif_type' => [self::T_ENUM, static::$NOTIF_TYPES, self::NT_ONDEMAND],
            'notif_format' => [self::T_ENUM, static::$NOTIF_FORMATS, self::NF_FULL],
            'notif_data' => [self::T_ARR, null, []],
            'autoclose_threshold' => [self::T_NUM, null, 0],

            'last_status' => [self::T_STR, null, ''],

            'last_execution_date' => [self::T_NUM, null, 0],
            'last_success_date' => [self::T_NUM, null, 0],
            'last_failure_date' => [self::T_NUM, null, 0],
            'last_error_email_date' => [self::T_NUM, null, 0]
        ];
    }

    /**
     * Creates a new Search of the appropriate type.
     * @param string $type The type of the Search.
     * @param array $data The attributes for the Search.
     * @return Search The new Search.
     */
    public static function newSearch($type, $data=null) {
        return self::newObject($type, $data);
    }

    protected function serialize(array $data) {
        $data['query_data'] = json_encode((object)$data['query_data']);
        $data['state_data'] = json_encode((object)$data['state_data']);
        $data['renderer_data'] = json_encode((object)$data['renderer_data']);
        $data['notif_data'] = json_encode((object)$data['notif_data']);
        $data['tags'] = implode(',', array_filter(array_map('trim', $data['tags']), 'strlen'));
        $data['enabled'] = (bool)$data['enabled'];
        return parent::serialize($data);
    }

    protected function deserialize(array $data) {
        $data['query_data'] = json_decode($data['query_data'], true);
        $data['state_data'] = json_decode($data['state_data'], true);
        $data['renderer_data'] = json_decode($data['renderer_data'], true);
        $data['notif_data'] = json_decode($data['notif_data'], true);
        $data['tags'] = array_filter(array_map('trim', explode(',', $data['tags'])), 'strlen');
        $data['enabled'] = (bool)$data['enabled'];
        return parent::deserialize($data);
    }

    public function validateData(array $data) {
        parent::validateData($data);

        if(strlen(trim($data['name'])) == 0) {
            throw new ValidationException('Invalid name');
        }
        switch($data['schedule_type']) {
            case self::SCT_FREQ:
                if($data['frequency'] < 1) {
                    throw new ValidationException('Invalid frequency');
                }
                break;
            case self::SCT_CRON:
                try {
                    \Cron\CronExpression::factory($data['cron_expression']);
                } catch(\InvalidArgumentException $e) {
                    throw new ValidationException('Invalid cron expression');
                }
                break;
        }
        if($data['range'] < 1) {
            throw new ValidationException('Invalid range');
        }

        $source_expr = trim(Util::get($data['query_data'], 'source_expr', ''));
        if(strlen($source_expr) > 0) {
            try {
                $el = $this->getSELInstance();
                $el->parse($source_expr, ['search', 'content', 'start', 'end']);
            } catch(\Symfony\Component\ExpressionLanguage\SyntaxError $e) {
                throw new ValidationException($e->getMessage());
            }
        }

        foreach($data['tags'] as $tag) {
            if(preg_match('/^\w+$/', $tag) === false) {
                throw new ValidationException(sprintf('Invalid tag: %s', $tag));
            }
        }
    }

    /**
     * Retrieves data from lists. If a given list doesn't exist, this method will return an empty list.
     * @param string[] $names The array of list names.
     * @return array A mapping of list names to its contents.
     */
    protected function getListData($names) {
        $names = array_unique($names);
        $lists = SListFinder::getByQuery(['name' => $names]);
        $ret = [];

        foreach($lists as $list) {
            $ret[$list['name']] = $list->getData();
        }
        foreach($names as $name) {
            if(!Util::exists($ret, $name)) {
                $ret[$name] = [];
            }
        }
        return $ret;
    }

    /**
     * Construct the query string with any data (Lists, etc) inserted.
     * @return mixed Query data.
     */
    abstract protected function constructQuery();

    /**
     * Wraps _execute to automatically populate several values in alerts. You probably shouldn't override this method.
     * @param int $date The current date.
     * @return Alert[] An array of Alert instances.
     */
    public function execute($date) {
        $ret = [];
        $constructed_qdata = $this->constructQuery();
        foreach($this->_execute($date, $constructed_qdata) as $alert) {
            $alert['search_id'] = $this['id'];
            $alert['assignee_type'] = $this->obj['assignee_type'];
            $alert['assignee'] = $this->obj['assignee'];
            $alert['content_hash'] = $this->getContentHash($alert);
            $ret[] = $alert;
        }
        return $ret;
    }

    /**
     * Executes the search.
     * @param int $date The current date.
     * @param array $constructed_qdata The input data for the Search.
     * @return Alert[] An array of Alert instances.
     */
    abstract protected function _execute($date, $constructed_qdata);

    /**
     * Determines if the Search is currently working. Queries the Search source to see if it's up.
     * @param int $date The current date.
     * @return bool Whether the Search is currently working.
     */
    public function isWorking($date) {
        return true;
    }

    /**
     * Determines if the Search is time based. Time based Searches are automatically retried when they fail.
     * @return bool Whether the Search is time based.
     */
    public function isTimeBased() {
        return false;
    }

    /**
     * Determines if this search should run at a given timestamp.
     * @param int $date The timestamp to test.
     * @param bool $backfill Whether we're attempting to backfill this point in time.
     * @return bool Whether to execute the search.
     */
    public function shouldRun($date, $backfill=false) {
        $due = false;
        $job = JobFinder::getLastByQuery(['type' => Search_Job::$TYPE, 'target_id' => $this->obj[static::$PKEY]]);

        $last_scheduled_date = is_null($job) ? 0:$job['target_date'];
        $delta = $date - $last_scheduled_date + 5;

        switch($this->obj['schedule_type']) {
            case self::SCT_FREQ:
                if($backfill) {
                    $due = ($date / 60) % $this->obj['frequency'] == 0;
                } else {
                    $due = $delta >= $this->obj['frequency'] * 60;
                }
                break;
            case self::SCT_CRON:
                $schedule = \Cron\CronExpression::factory($this->obj['cron_expression']);
                $due =
                    $schedule->isDue(new \DateTime("@$date")) &&
                    $delta >= 10;
                break;
        }
        return $due;
    }

    /**
     * Wraps _getLink to use a default value if nothing is returned.
     * @param Alert $alert The Alert object.
     * @return string|null An URL with additional data about this Alert.
     */
    public function getLink(Alert $alert) {
        $source_expr = trim(Util::get($this->obj['query_data'], 'source_expr'));

        $ret = null;
        if(strlen($source_expr) > 0) {
            try {
                $el = $this->getSELInstance();
                $ret = (string) $el->evaluate($source_expr, [
                    'search' => $this->toArray(),
                    'content' => $alert['content'],
                    'start' => $alert['alert_date'] - $this->obj['range'] * 60,
                    'end' => $alert['alert_date']
                ]);
            } catch(\Exception $e) {}
        }

        if(is_null($ret)) {
            $ret = $this->_getLink($alert);
        }

        return $ret;
    }

    /**
     * Return a link for a given search result.
     * @param Alert $alert The Alert object.
     * @return string|null An URL with additional data about this Alert.
     */
    protected function _getLink(Alert $alert) {
        return null;
    }

    /**
     * Retrieve an instance of SEL.
     * @return ExpressionLanguage The SEL instance.
     */
    protected function getSELInstance() {
        static $el = null;

        if(is_null($el)) {
            $stub = function() {};
            $func = function() {
                $args = func_get_args();
                array_shift($args);
                return call_user_func_array([$this, 'generateLink'], $args);
            };
            $el = new ExpressionLanguage();
            $el->register('link', [ExpressionLanguage::class, 'compileStub'], $func);
        }
        return $el;
    }

    /**
     * Hashes the contents of an Alert and returns the hash.
     * @param Alert $alert The Alert object.
     * @return string A hash of the contents.
     */
    public function getContentHash(Alert $alert) {
        return hash('sha256', json_encode((object)$alert['content']));
    }

    /**
     * Return the set of Filters for this Search.
     * @return Filter[] An array of Filter objects.
     */
    public function getFilters() {
        return Hook::call('search.filters', [FilterFinder::getBySearch($this->obj[static::$PKEY])])[0];
    }

    /**
     * Return the set of Targets for this Search.
     * @return Target[] An array of Target objects.
     */
    public function getTargets() {
        $targets = TargetFinder::getBySearch($this->obj[static::$PKEY]);
        array_unshift($targets, new DB_Target);
        return Hook::call('search.targets', [$targets])[0];
    }

    /**
     * Get emails associated with this Search.
     * @return string[] A list of emails.
     */
    public function getEmails() {
        $emails = Assignee::getEmails($this->obj['assignee_type'], $this->obj['assignee']);
        $owner = UserFinder::getById($this->obj['owner']);
        if(!is_null($owner)) {
            $emails[] = $owner['email'];
        }
        return array_unique($emails);
    }
}

/**
 * Class SearchFinder
 * Finder for Searches.
 * @package FOO
 * @method static Search getById(int $id, bool $archived=false)
 * @method static Search[] getAll()
 * @method static Search[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null);
 * @method static Search[] hydrateModels($objs)
 */
class SearchFinder extends TypeModelFinder {
    public static $MODEL = 'Search';
}

/**
 * Class SearchException
 * Thrown where there is an exception running the Search.
 * @package FOO
 */
class SearchException extends \Exception {}
