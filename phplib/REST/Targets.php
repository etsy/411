<?php

namespace FOO;

/**
 * Class Targets_REST
 * REST endpoint for manipulating Targets.
 * @package FOO
 */
class Targets_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_TARGET;

    protected static $MODEL = 'Target';
    protected static $CREATABLE = [
        'search_id', 'type', 'lifetime', 'description', 'data'
    ];
    protected static $QUERYABLE = ['search_id'];
    protected static $READABLE = null;
    protected static $UPDATEABLE = [
        'lifetime', 'description', 'data'
    ];

    protected function construct($data=null) {
        $type = Util::get($data, 'type', '');

        return Target::newTarget($type);
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

        $target = Target::newTarget($type);
        if(!is_null($target)) {
            $target['search_id'] = Util::get($data, 'search_id', 0);
            $target['data'] = Util::get($data, 'data', []);
            $target->validate();
        }
        return self::format();
    }
};
