<?php

namespace FOO;

/**
 * Class Models_REST
 * Base class for REST endpoints that handle Models.
 * @package FOO
 */
class Models_REST extends REST {
    /** @var string The type of model this endpoint manages. */
    protected static $MODEL = '';
    /** @var string[] An array of fields that can be set on creation. */
    protected static $CREATABLE = [];
    /** @var string[] An array of fields that can queried on. */
    protected static $QUERYABLE = [];
    /** @var string[]|null An array of fields that can retrieved. */
    protected static $READABLE = null;
    /** @var string[] An array of fields that can be updated. */
    protected static $UPDATEABLE = [];

    /** Namespace for log lines. */
    const LOG_NAMESPACE = '411_REST';
    /** Type of model for the purpose of logging. */
    const SLOG_TYPE = 0;
    /** Whether logging is enabled for this type of model. */
    const SLOG_ENABLED = true;

    public function allowCreate() { return false; }
    public function allowRead() { return false; }
    public function allowUpdate() { return false; }
    public function allowDelete() { return false; }

    /**
     * Hook before store or delete is called on a model.
     * @param Model $model The model
     * @param array $data Input data
     * @param boolean $new Whether this model is new
     * @param boolean $delete Whether this model is being deleted
     */
    public function beforeStore($model, $data, $new, $delete) {}
    /**
     * Hook after store or delete is called on a model.
     * @param Model $model The model
     * @param array $data Input data
     * @param boolean $new Whether this model is new
     * @param boolean $delete Whether this model is being deleted
     */
    public function afterStore($model, $data, $new, $delete) {}
    /**
     * Hook after all processing is done on a model(s).
     */
    public function finalizeStore() {}

    /**
     * Filter fields of a model and return the filtered data.
     * @param Model $model The model
     * @param string[] $readable A list of attributes to filter.
     * @return array Filtered data
     */
    public function filterFields(Model $model, array $readable) {
        return $model->toArray($readable);
    }

    /**
     * Constructs a new Model with data.
     * @param array $data Unused.
     * @return Model The new Model.
     */
    protected function construct($data=null) {
        $MODEL = 'FOO\\' . static::$MODEL;
        return new $MODEL();
    }

    /**
     * Create a new model.
     * @param array $get URL params
     * @param array $data Request body params.
     * @return mixed The new object
     */
    public function create($get, $data, $set_fields=[]) {
        if(!$this->allowCreate()) {
            throw new ForbiddenException;
        }

        $model = $this->construct($data);
        $fields = array_merge(static::$CREATABLE, array_keys($set_fields));
        $filtered = $this->filterByKeys($fields, [$set_fields, $data]);
        foreach($filtered as $k=>$v) {
            $model[$k] = $v;
        }

        $this->beforeStore($model, $data, true, false);
        if(!$model->store()) {
            throw new InternalErrorException('Error on store');
        }
        $this->afterStore($model, $data, true, false);
        Logger::info(sprintf('Created %s', static::$MODEL), ['id' => $model['id']], self::LOG_NAMESPACE);
        $this->slog(SLog::A_CREATE, $model['id']);

        $this->finalizeStore();
        return $this->filterFields($model, static::$READABLE);
    }

    /**
     * Get models.
     * @param array $get URL params
     * @param array $data Request body params.
     * @return Model[] The list of matching Models.
     */
    public function read($data) {
        if(!$this->allowRead()) {
            throw new ForbiddenException;
        }

        $FINDER = 'FOO\\' . static::$MODEL . 'Finder';
        $id_query = Util::exists($data, 'id');
        if($id_query) {
            $query['id'] = $data['id'];
        } else {
            $fields = array_merge(static::$QUERYABLE, ['time', 'create_date', 'update_date']);
            $query = $this->filterByKeys($fields, [$data]);
        }
        $count = Util::get($data, 'count');
        $offset = Util::get($data, 'offset');
        $reverse = Util::get($data, 'reverse');

        $ret = array_map(
            function($x) { return $this->filterFields($x, static::$READABLE); },
            $FINDER::getByQuery($query, $count, $offset, [], $reverse)
        );

        if($id_query && !is_array($data['id'])) {
            if(!count($ret)) {
                throw new NotFoundException;
            }
            $ret = $ret[0];
        }
        return $ret;
    }

    /**
     * Update a model or a list of models.
     * This endpoint allows you to do batch updates on models. (Create, Update, Delete)
     * @param array $get URL params
     * @param array $data Request body params.
     * @return array An empty array.
     */
    public function update($get, $data, $set_fields=[]) {
        if(!$this->allowUpdate()) {
            throw new ForbiddenException;
        }
        $id = Util::get($get, 'id');

        $MODEL = 'FOO\\' . static::$MODEL;
        $FINDER = 'FOO\\' . static::$MODEL . 'Finder';
        $single = false;

        // If an id is set, ensure we're setting that model.
        if($id) {
            $single = true;
            $data['id'] = $id;
            $data = [$data];
        } else if(Util::exists($data, 'models')) {
            $data = $data['models'];
        }

        $ret = [];
        $errors = [];

        $created_ids = [];
        $updated_ids = [];
        $deleted_ids = [];
        foreach($data as $obj) {
            $id = Util::get($obj, 'id');
            $cid = Util::get($obj, 'cid');

            $new = $id === null && $cid !== null;
            // New model.
            if($new) {
                $model_ret = $obj;
                try {
                    $model_ret = $this->create($obj, $obj);
                    $model_ret['cid'] = $cid;
                    $created_ids[] = $model_ret['id'];
                } catch(InternalErrorException $e) {
                    $model_ret['archived'] = true;
                    Logger::except($e);
                } catch(ValidationException $e) {
                    $errors[] = $e->getMessage();
                    $model_ret['archived'] = true;
                    Logger::except($e);
                }
                $ret[] = $model_ret;
                continue;
            }

            // Existing model.
            $model = null;
            if($id !== null) {
                $model = $FINDER::getById($id);
            }
            if(is_null($model)) {
                if($single) {
                    throw new NotFoundException;
                } else {
                    continue;
                }
            }

            $fields = array_merge(static::$UPDATEABLE, array_keys($set_fields));
            $ret_fields = [$MODEL::$PKEY];
            $changed_fields = $this->filterByKeys($fields, [$set_fields, $obj]);
            foreach($changed_fields as $k=>$v) {
                $ret_fields[] = $k;
                $model[$k] = $v;
            }

            // Check if we want to delete this model. This will not work in $single mode.
            $delete = !$single && Util::get($obj, '_delete');

            $this->beforeStore($model, $data[0], $new, $delete);
            if($delete) {
                if(!$model->delete() && $single) {
                    throw new InternalErrorException('Error on delete');
                }
                $deleted_ids[] = $model['id'];
                $this->slog(SLog::A_DELETE, $model['id']);
            } else {
                $store_ret = false;
                try {
                    $store_ret = $model->store();
                    $this->slog(SLog::A_UPDATE, $model['id']);
                    $updated_ids[] = $model['id'];
                } catch(ValidationException $e) {
                    $errors[] = $e->getMessage();
                    Logger::except($e);
                }
                if(!$store_ret && $single) {
                    // Commented out to expose ValidationError to the user.
//                    throw new InternalErrorException('Error on update');
                }
            }
            $this->afterStore($model, $data[0], $new, $delete);
            $ret[] = $this->filterFields($model, $single ? static::$READABLE:$ret_fields);
            Logger::info(sprintf('Modified %s', static::$MODEL), ['id' => $model['id']], self::LOG_NAMESPACE);
        }

        $this->finalizeStore();
        return self::format(
            $single ? $ret[0]:$ret,
            !count($errors),
            $errors
        );
    }

    /**
     * Delete a model.
     * @param array $get URL params
     * @param array $data Request body params.
     * @return array An empty array.
     */
    public function _delete($get, $data) {
        if(!$this->allowDelete()) {
            throw new ForbiddenException;
        }
        $id = Util::get($get, 'id');

        $FINDER = 'FOO\\' . static::$MODEL . 'Finder';
        $model = $FINDER::getById($id);
        if(is_null($model)) {
            throw new NotFoundException;
        }

        $this->beforeStore($model, $data, false, true);
        if(!$model->delete()) {
            throw new InternalErrorException('Error on delete');
        }
        $this->afterStore($model, $data, false, true);

        Logger::info(sprintf('Modified %s', static::$MODEL), ['id' => $model['id']], self::LOG_NAMESPACE);
        $this->slog(SLog::A_DELETE, $model['id']);

        $this->finalizeStore();
        return [];
    }

    public function GET(array $get) {
        return self::format($this->read($get));
    }
    public function POST(array $get, array $data) {
        return self::format($this->create($get, $data));
    }
    public function PUT(array $get, array $data) {
        return self::format($this->update($get, $data));
    }
    public function DELETE(array $get, array $data) {
        return self::format($this->_delete($get, $data));
    }

    protected function slog($action, $target=0, $a=0, $b=0) {
        if(static::SLOG_ENABLED) {
            SLog::entry(static::SLOG_TYPE, (int) $action, (int) $target, Auth::getUserId(), (int) $a, (int) $b);
        }
    }

    // Filter a series of associative arrays by keys. Earlier arrays take precidence over later ones.
    protected function filterByKeys($keys, $srcs) {
        $ret = [];
        foreach($keys as $key) {
            foreach($srcs as $src) {
                if(Util::exists($src, $key)) {
                    $ret[$key] = $src[$key];
                    break;
                }
            }
        }

        return $ret;
    }
};
