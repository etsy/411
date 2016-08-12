<?php

namespace FOO;

/**
 * Class Filters_REST
 * REST endpoint for manipulating Filters.
 * @package FOO
 */
class Filters_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_FILTER;

    protected static $MODEL = 'Filter';
    protected static $CREATABLE = [
        'search_id', 'type', 'position', 'lifetime', 'description', 'data'
    ];
    protected static $QUERYABLE = ['search_id'];
    protected static $READABLE = null;
    protected static $UPDATEABLE = [
        'position', 'lifetime', 'description', 'data'
    ];

    protected function construct($data=null) {
        $type = Util::get($data, 'type', '');

        $MODEL = 'FOO\\' . static::$MODEL;
        return $MODEL::newFilter($type);
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

    protected function checkSearch($get) {
        $id = Util::get($get, 'search_id');
        $model = SearchFinder::getById($id);
        return !is_null($model);
    }

    public function GET(array $get) {
        if(!$this->checkSearch($get)) {
            throw new NotFoundException;
        }
        return self::format($this->read($get));
    }
    public function POST(array $get, array $data) {
        if(!$this->checkSearch($get)) {
            throw new NotFoundException;
        }
        if(Util::get($get, 'action') == 'validate') {
            return $this->validate($get, $data);
        }
        return self::format($this->create($get, $data));
    }
    public function PUT(array $get, array $data) {
        if(!$this->checkSearch($get)) {
            throw new NotFoundException;
        }
        return self::format($this->update($get, $data));
    }
    public function DELETE(array $get, array $data) {
        if(!$this->checkSearch($get)) {
            throw new NotFoundException;
        }
        return self::format($this->_delete($get, $data));
    }

    public function validate(array $get, array $data) {
        $type = Util::get($data, 'type');

        $filter = Filter::newFilter($type);
        if(!is_null($filter)) {
            $filter['search_id'] = Util::get($data, 'search_id', 0);
            $filter['data'] = Util::get($data, 'data', []);
            $filter->validate();
        }
        return self::format();
    }
};
