<?php

namespace FOO;

/**
 * Class Model
 * The base Model class.
 * @package FOO
 */
class Model implements \JsonSerializable, \ArrayAccess {
    // Field types
    /** Boolean field type. */
    const T_BOOL = 0;
    /** Numeric (int/float) field type. */
    const T_NUM = 1;
    /** String field type. */
    const T_STR = 2;
    /** Enumeration field type. */
    const T_ENUM = 3;
    /** Array field type. */
    const T_ARR = 4;
    /** Object (associative array) field type. */
    const T_OBJ = 5;

    /** @var string The table name. */
    public static $TABLE = '';
    /** @var string The name of the primary key. */
    public static $PKEY = 'id';
    /** @var bool Whether to do hard deletes. */
    public static $DELETE = false;
    /** @var bool Whether this model is site-specific. */
    public static $PERSITE = true;

    /** @var array The fields. */
    protected $obj = [];
    /** @var bool Whether this model is new. */
    protected $new = true;
    /** @var bool Whether this model has been modified. */
    protected $dirty = false;
    /** @var int Initial update date. Used to enable strict model updates. */
    protected $initial_update_date = 0;

    /**
     * Retrieve the schema for this model.
     * @return array The model schema.
     */
    public static function getSchema() {
        static $schema = null;
        if(is_null($schema)) {
            $schema = static::generateSchema();
        }
        $schema = array_merge($schema, [
            'archived' => [self::T_BOOL, null, false],
            'create_date' => [self::T_NUM, null, 0],
            'update_date' => [self::T_NUM, null, 0],
        ]);

        return $schema;
    }

    /**
     * Generate the schema for this model.
     * A schema is an array of field definitions. Each field definition has the following
     * format: [field_name, field_type, field_param, field_default]
     *
     * Example schema: [
     *     'name' => [self::T_STR, null, ''],
     *     'age' => [self::T_NUM, null, 0],
     *     'sex' => [self::T_ENUM, ['?', 'm', 'f'], '?'],
     * ]
     * @return array The model schema.
     */
    protected static function generateSchema() {
        return [];
    }

    /**
     * Get default values for fields.
     * @return array The default values.
     */
    public static function getDefaults() {
        return array_map(function($x) {
            return $x[2];
        }, static::getSchema());
    }

    /**
     * Model constructor.
     * Initialize a new object. If an array of data is passed in, just read from that.
     * @param array $data The model attributes.
     */
    public function __construct(array $data=null) {
        if(is_null($data)) {
            $this->obj = static::getDefaults();
            $this->obj[static::$PKEY] = null;
            if(static::$PERSITE) {
                $this->obj['site_id'] = SiteFinder::getCurrentId();
            }
            $this->obj['create_date'] = (int) $_SERVER['REQUEST_TIME'];
            $this->obj['update_date'] = (int) $_SERVER['REQUEST_TIME'];
            $this->obj['archived'] = false;
        } else {
            $this->obj = $this->deserialize($data);
            $this->initial_update_date = Util::get($this->obj, 'update_date', 0);
            $this->new = false;
        }
    }

    /**
     * Save to the DB.
     * @param bool $strict Whether to enforce strict mode. Only applies to updates.
     * @return bool Whether the save was successful.
     */
    public function store($strict=false) {
        if($this->new) {
            return $this->create();
        } else {
            return $this->update($strict);
        }
    }

    /**
     * Called when store()ing a new model.
     * @return bool Whether the create was successful.
     * @throws DBException
     */
    protected function create() {
        $keys = array_keys($this->obj);
        $field_str = implode(',', DB::kPlaceholders($keys));
        $sql = sprintf('INSERT INTO `%s` (%s) VALUES %s', static::$TABLE, $field_str,
            DB::inPlaceholder(count($keys))
        );

        $this->validate();
        list(, $obj) = Hook::call('model.create', [get_called_class(), $this->obj]);
        $this->obj = $obj;
        $data = $this->serialize($this->obj);
        $data_arr = array_map(function($k) use ($data) { return $data[$k]; }, $keys);

        if(DB::query($sql, $data_arr, DB::CNT)) {
            // Set the pkey if it's not already set.
            if(is_null($this->obj[static::$PKEY])) {
                $this->obj[static::$PKEY] = DB::insertId();
            }
            $this->new = false;
            return true;
        }
        return false;
    }

    /**
     * Called when store()ing an existing model.
     * @param bool $strict Whether to enforce strict mode - Only update the model if no other changes have been made (If the update_date has stayed the same since we grabbed the model).
     * @return bool Whether the update was successful.
     * @throws DBException
     */
    protected function update($strict=false) {
        $keys = array_keys($this->obj);
        $field_str = implode(',', DB::kvPlaceholders($keys));
        $where = [DB::kvPlaceholder(static::$PKEY)];
        $vals = [$this->obj[static::$PKEY]];
        if($strict) {
            $where[] = DB::kvPlaceholder('update_date');
            $vals[] = $this->initial_update_date;
        }
        $where_str = implode(' AND ', $where);

        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', static::$TABLE, $field_str, $where_str);

        $this->validate();
        list(, $obj) = Hook::call('model.update', [get_called_class(), $this->obj]);
        $this->obj = $obj;
        $data = $this->serialize($this->obj);
        $data_arr = array_map(function($k) use ($data) { return $data[$k]; }, $keys);

        $id = Util::get($data, static::$PKEY);
        $ret = (bool)DB::query($sql, array_merge($data_arr, $vals), DB::CNT);
        if($ret) {
            $this->initial_update_date = Util::get($this->obj, 'update_date');
        }
        return $ret;
    }

    /**
     * Called on data to be saved to the DB. Used to serialize any data structures in the model.
     * @param array $data The unserialized data.
     * @return array The serialized data.
     */
    protected function serialize(array $data) {
        $data['archived'] = (bool)$data['archived'];
        return $data;
    }

    /**
     * Called on data returned from the DB/from the constructor. Used to deserialize any data structures in the model.
     * @param array $data The serialized data.
     * @return array The unserialized data.
     */
    protected function deserialize(array $data) {
        $data['archived'] = (bool)$data['archived'];
        return $data;
    }

    /**
     * Verify that the model is valid. Throw a ValidationException on failure.
     * @throws ValidationException
     */
    public function validate() {
        $this->validateData($this->obj);
    }

    /**
     * Verify that the passed in data is valid. Throw a ValidationException on failure.
     * @param array $data The model data.
     * @throws ValidationException
     */
    public function validateData(array $data) {
        $schema = static::getSchema();
        foreach($schema as $field=>$settings) {
            $this->validateField($field, $settings, $data[$field]);
        }
    }

    /**
     * Verify a single field is valid. Throw a ValidationException on failure.
     * @param string $field The field name.
     * @param mixed $settings The field type.
     * @param mixed $value The field value.
     * @throws ValidationException
     */
    protected function validateField($field, $settings, $value) {
        $ok = false;
        switch($settings[0]) {
            case static::T_BOOL:
                $ok = is_bool($value);
                break;
            case static::T_NUM:
                $ok = is_numeric($value);
                break;
            case static::T_STR:
                $ok = is_string($value);
                break;
            case static::T_ENUM:
                $ok = array_key_exists($value, $settings[1]);
                break;
            case static::T_ARR:
                $ok = is_array($value);
                if($ok && !is_null($settings[1])) {
                    foreach($value as $entry) {
                        $this->validateField($field, [$settings[1]], $entry);
                    }
                }
                break;
            case static::T_OBJ:
                $ok = is_array($value);
                break;
            default:
                throw new ValidationException(sprintf('Unknown type for %s', $field));
        }

        if(!$ok) {
            throw new ValidationException(sprintf('Invalid %s', $field));
        }
    }

    /**
     * Delete the model. Will do a soft deleting by setting archived to 1 unless if $hard is set.
     * @param bool $hard Whether to do a hard delete.
     * @return bool Whether the delete was successful.
     * @throws DBException
     */
    public function delete($hard=false) {
        if($this->new) {
            return false;
        }

        $hard = static::$DELETE || $hard;
        if($hard) {
            $sql = sprintf('DELETE FROM `%s` WHERE `%s` = ?', static::$TABLE, static::$PKEY);
        } else {
            $sql = sprintf('UPDATE `%s` SET `archived` = 1 WHERE `%s` = ?', static::$TABLE, static::$PKEY);
        }

        list(, $obj) = Hook::call('model.delete', [get_called_class(), $this->obj]);
        $this->obj = $obj;
        $ret = DB::query($sql, [$this->obj[static::$PKEY]], DB::CNT);
        if($ret) {
            $this->new = true;
            $this->obj['archived'] = true;
            if($hard) {
                unset($this->obj[static::$PKEY]);
            }
        }

        return (bool)$ret;
    }

    /**
     * Return whether this model has been saved to the DB.
     * @return bool Whether the model is new.
     */
    public function isNew() {
        return $this->new;
    }

    /**
     * Serialize this model into an array. Pass an array of keys to return.
     * @param string[] $keys The list of keys to extract.
     * @return array The contents of the model as an array.
     */
    public function toArray(array $keys=null) {
        $ret = [];
        if(is_null($keys)) {
            $keys = array_keys($this->obj);
        } else {
            $keys = array_merge($keys, [static::$PKEY, 'archived', 'create_date', 'update_date']);
        }
        $schema = static::getSchema();
        foreach($keys as $key) {
            $nkey = $key === static::$PKEY ? 'id':$key;
            $val = $this->obj[$key];
            if(array_key_exists($key, $schema) && $schema[$key][0] === static::T_OBJ) {
                $val = (object)$val;
            }
            $ret[$nkey] = $val;
        }

        return $ret;
    }

    /**
     * ArrayAccess interface
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key) {
        return array_key_exists($key, $this->obj);
    }

    /**
     * ArrayAccess interface
     * @param mixed $key
     * @return mixed
     */
    public function &offsetGet($key) {
        if($key == 'id') {
            $key = static::$PKEY;
        }
        if(!array_key_exists($key, $this->obj)) {
            throw new \UnexpectedValueException(sprintf('Invalid key: %s', $key));
        }
        return $this->obj[$key];
    }

    /**
     * ArrayAccess interface
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value) {
        $schema = static::getSchema();
        if(array_key_exists($key, $this->obj)) {
            switch($schema[$key][0]) {
                case static::T_BOOL:
                    if(!is_bool($value)) {
                        $value = (bool) $value;
                    }
                    break;
                case static::T_NUM:
                    if(!is_int($value) && !is_float($value)) {
                        if(ctype_digit($value)) {
                            $value = (int) $value;
                        } else {
                            $value = (float) $value;
                        }
                    }
                    break;
                case static::T_STR:
                    if(!is_string($value)) {
                        $value = (string) $value;
                    }
                    break;
            }
            $this->obj[$key] = $value;
            $this->dirty = true;

            if($key != 'update_date') {
                $this->obj['update_date'] = $_SERVER['REQUEST_TIME'];
            }
        } else {
            throw new \UnexpectedValueException(sprintf('Invalid key: %s', $key));
        }
    }

    /**
     * ArrayAccess interface
     * @param mixed $key
     * @throws \BadMethodCallException
     */
    public function offsetUnset($key) {
        throw new \BadMethodCallException;
    }

    /**
     * JsonSerializable interface
     * @return array
     */
    public function jsonSerialize() {
        return $this->toArray();
    }
}

/**
 * Class ModelFinder
 * Base Finder for Models.
 * @package FOO
 */
class ModelFinder {
    public static $MODEL = '';

    /** Descending sort order. */
    const O_DESC = 0;
    /** Ascending sort order. */
    const O_ASC = 1;

    /** Greater than comparison. */
    const C_GT = 'gt';
    /** Less than comparison. */
    const C_LT = 'lt';
    /** Greater than/equal to comparison. */
    const C_GTE = 'gte';
    /** Less than/equal to comparison. */
    const C_LTE = 'lte';
    /** Not equal to comparison. */
    const C_NEQ = 'neq';

    /** @var Mapping of comparions to operators. */
    private static $C_MAP = [
        self::C_GT => '>',
        self::C_LT => '<',
        self::C_GTE => '>=',
        self::C_LTE => '<=',
        self::C_NEQ => '!=',
    ];

    /**
     * Fetch a single model by id.
     * @param int $id The model id.
     * @param bool $archived Whether to match archived models.
     * @return Model|null The model.
     */
    public static function getById($id, $archived=false) {
        $MODEL = 'FOO\\' . static::$MODEL;
        $query = [$MODEL::$PKEY => $id];
        if($archived) {
            $query['archived'] = [0, 1];
        }
        $models = static::getByQuery($query);
        if(!count($models)) {
            return null;
        }
        return $models[0];
    }

    /**
     * Fetch all models.
     * @return Model[] An array of models.
     */
    public static function getAll() {
        return static::getByQuery();
    }

    /**
     * Fetch a group of models based on a query.
     * @param array $query The query parameters.
     * @param int|null $count The maximum number of results.
     * @param int|null $offset The results offset.
     * @param array $sort An array of columns to sort by.
     * @param bool|null $reverse Whether to reverse the order of the result set.
     * @return Model[] An array of Models.
     * @throws DBException
     */
    public static function getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null) {
        list($sql, $vals) = static::generateQuery(['*'], $query, $count, $offset, $sort, [], $reverse);

        return static::hydrateModels(DB::query(implode(' ', $sql), $vals));
    }

    /**
     * Fetch a count of models based on a query.
     * @param array $query The query parameters.
     * @param int|null $count The maximum number of results.
     * @param int|null $offset The results offset.
     * @param array $sort An array of columns to sort by.
     * @param bool|null $reverse Whether to reverse the order of the result set.
     * @return int A count.
     * @throws DBException
     */
    public static function countByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null) {
        list($sql, $vals) = static::generateQuery(['COUNT(*) as count'], $query, $count, $offset, $sort, [], $reverse);

        return (int) DB::query(implode(' ', $sql), $vals, DB::VAL);
    }

    /**
     * Generate a query.
     * @param string[] The list of fields to return.
     * @param array $query The query parameters.
     * @param int|null $count The maximum number of results.
     * @param int|null $offset The results offset.
     * @param array $sort An array of columns to sort by.
     * @param array $group An array of columns to group by.
     * @param bool|null $reverse Whether to reverse the order of the result set.
     * @return int A count.
     * @throws DBException
     */
    public static function generateQuery(array $fields, array $query=[], $count=null, $offset=null, $sort=[], $group=[], $reverse=null) {
        $MODEL = 'FOO\\' . static::$MODEL;
        $sql = [];
        $sql[] = sprintf('SELECT %s FROM `%s`', implode(', ', $fields), $MODEL::$TABLE);

        list($where, $vals) = static::generateWhere($query);
        if(count($where)) {
            $sql[] = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = array_merge($sql, static::generateClauses($count, $offset, $sort, $group, $reverse));
        return [$sql, $vals];
    }

    /**
     * Hydrate database rows into objects.
     * @param array $objs The array of DB rows.
     * @return Model[] An array of Models.
     */
    public static function hydrateModels($objs) {
        $models = [];
        foreach($objs as $obj) {
            foreach(array_keys($obj) as $key) {
                // If a value is numeric, cast it into an int.
                $val = $obj[$key];
                if(is_numeric($val)) {
                    // If we can't represent the value as an int, cast to a float instead.
                    if(((int) $val) != $val || !ctype_digit($val)) {
                        $obj[$key] = (float) $val;
                    } else {
                        $obj[$key] = (int) $val;
                    }
                }
            }
            $models[] = static::construct($obj);
        }
        return $models;
    }

    /**
     * Generate the where clause for the query.
     * @param array $query The list of query clauses.
     * @return string[] A list of SQL fragments.
     */
    protected static function generateWhere($query) {
        $MODEL = 'FOO\\' . static::$MODEL;

        $clauses = [];
        $vals = [];
        $schema = $MODEL::getSchema();
        foreach($query as $key=>$value) {
            if($key == 'id') {
                $key = $MODEL::$PKEY;
            }
            if(!array_key_exists($key, $schema) && $key != $MODEL::$PKEY) {
                continue;
            }

            list($conds, $values) = self::generateClause($key, $value);
            $clauses = array_merge($clauses, $conds);
            $vals = array_merge($vals, $values);
        }

        if($MODEL::$PERSITE) {
            $clauses[] = DB::kvPlaceholder('site_id');
            $vals[] = SiteFinder::getCurrentId();
        }

        // Only get objects that have been touched since the last request
        if(array_key_exists('time', $query) && $query['time']) {
            $clauses[] = '(`create_date` > ? OR `update_date` > ?)';
            $vals[] = (int) $query['time'];
            $vals[] = (int) $query['time'];
        } else if(!array_key_exists('archived', $query)) {
            // Unless specified otherwise, we only want active models.
            $clauses[] = DB::kvPlaceholder('archived');
            $vals[] = 0;
        }

        return [$clauses, $vals];
    }

    /**
     * Generate conditions for a single row.
     * @param string $key The row name.
     * @param mixed $value The value to find.
     * @return string[] A list of SQL fragments.
     */
    protected static function generateClause($key, $value) {
        $val_cmp = true;
        $conds = [];
        $vals = [];

        // Only check for comparsion operators if value is an array.
        if(is_array($value)) {
            foreach(self::$C_MAP as $cmp=>$op) {
                if(array_key_exists($cmp, $value)) {
                    $val_cmp = false;
                    $conds[] = sprintf('`%s` %s ?', $key, $op);
                    $vals[] = $value[$cmp];
                }
            }
        }

        // If it wasn't a comparison operator, we got a list of values.
        if($val_cmp) {
            $values = (array) $value;
            $conds[] = sprintf('`%s` IN %s', $key, DB::inPlaceholder(count($values)));
            $vals = array_merge($vals, $values);
        }

        return [$conds, $vals];
    }

    /**
     * Generate extra clauses for the query.
     * @param int|null $count The maximum number of results.
     * @param int|null $offset The results offset.
     * @param array $sort An array of columns to sort by.
     * @param array $group An array of columns to group by.
     * @param bool|null $reverse Whether to reverse the order of the result set.
     * @return string[] A list of SQL fragments.
     */
    protected static function generateClauses($count=null, $offset=null, $sort=[], $group=[], $reverse=null) {
        $MODEL = 'FOO\\' . static::$MODEL;

        $clauses = [];
        $order = [];

        // Allow querying for the newest results.
        if($reverse) {
            $sort[] = [$MODEL::$PKEY, static::O_DESC];
        }
        foreach($sort as $col) {
            switch($col[1]) {
            case static::O_DESC:
                $order[] = sprintf('%s DESC', DB::kPlaceholder($col[0]));
                break;
            case static::O_ASC:
                $order[] = sprintf('%s ASC', DB::kPlaceholder($col[0]));
                break;
            }
        }

        if(count($group)) {
            $clauses[] = sprintf('GROUP BY %s', implode(', ', DB::kPlaceholders($group, $MODEL::$TABLE)));
        }

        if(count($order)) {
            $clauses[] = sprintf('ORDER BY %s', implode(', ', $order));
        }

        if(!is_null($count) || !is_null($offset)) {
            $clauses[] = sprintf('LIMIT %d', is_null($count) ? PHP_INT_MAX:$count);
        }
        if(!is_null($offset)) {
            $clauses[] = sprintf('OFFSET %d', $offset);
        }

        return $clauses;
    }

    /**
     * Construct a model object. Broken into its own function to allow subclasses to implement additional logic.
     * @param array $obj The model attributes.
     * @return Model The model.
     */
    protected static function construct(array $obj) {
        $MODEL = 'FOO\\' . static::$MODEL;
        return new $MODEL($obj);
    }
}
