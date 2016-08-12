<?php

namespace FOO;

/**
 * Class GroupTargets_REST
 * REST endpoint for manipulating GroupTargets.
 * @package FOO
 */
class GroupTargets_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_GROUPTARGET;

    protected static $MODEL = 'GroupTarget';
    protected static $CREATABLE = [
        'type', 'group_id', 'user_id', 'data'
    ];
    protected static $QUERYABLE = [
        'group_id'
    ];
    protected static $READABLE = null;
    protected static $UPDATEABLE = [];

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

    protected function checkGroup($get) {
        $id = Util::get($get, 'group_id');
        $model = GroupFinder::getById($id);
        return !is_null($model);
    }

    public function GET(array $get) {
        if(!$this->checkGroup($get)) {
            throw new NotFoundException;
        }
        return self::format($this->read($get));
    }
    public function POST(array $get, array $data) {
        if(!$this->checkGroup($get)) {
            throw new NotFoundException;
        }
        return self::format($this->create($get, $data));
    }
    public function PUT(array $get, array $data) {
        if(!$this->checkGroup($get)) {
            throw new NotFoundException;
        }
        return self::format($this->update($get, $data));
    }
    public function DELETE(array $get, array $data) {
        if(!$this->checkGroup($get)) {
            throw new NotFoundException;
        }
        return self::format($this->_delete($get, $data));
    }
};
