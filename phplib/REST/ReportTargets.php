<?php

namespace FOO;

/**
 * Class ReportTargets_REST
 * REST endpoint for manipulating ReportTargets.
 * @package FOO
 */
class ReportTargets_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_REPORTTARGET;

    protected static $MODEL = 'ReportTarget';
    protected static $CREATABLE = [
        'report_id', 'search_id', 'position'
    ];
    protected static $QUERYABLE = [
        'report_id'
    ];
    protected static $READABLE = null;
    protected static $UPDATEABLE = [
        'position'
    ];

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

    protected function checkReport($get) {
        $id = Util::get($get, 'report_id');
        $model = ReportFinder::getById($id);
        return !is_null($model);
    }

    public function GET(array $get) {
        if(!$this->checkReport($get)) {
            throw new NotFoundException;
        }
        return self::format($this->read($get));
    }
    public function POST(array $get, array $data) {
        if(!$this->checkReport($get)) {
            throw new NotFoundException;
        }
        return self::format($this->create($get, $data));
    }
    public function PUT(array $get, array $data) {
        if(!$this->checkReport($get)) {
            throw new NotFoundException;
        }
        return self::format($this->update($get, $data));
    }
    public function DELETE(array $get, array $data) {
        if(!$this->checkReport($get)) {
            throw new NotFoundException;
        }
        return self::format($this->_delete($get, $data));
    }
};
