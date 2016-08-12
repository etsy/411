<?php

namespace FOO;

/**
 * Class SLists_REST
 * REST endpoint for manipulating SLists.
 * @package FOO
 */
class SLists_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_SLIST;

    protected static $MODEL = 'SList';
    protected static $CREATABLE = [
        'type', 'url', 'name'
    ];
    protected static $QUERYABLE = [];
    protected static $READABLE = null;
    protected static $UPDATEABLE = [
        'type', 'url', 'name'
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

    public function GET(array $get) {
        $action = Util::get($get, 'action');

        switch($action) {
            case 'info':
                return self::format($this->getInfo($get));
            default:
                return self::format($this->read($get));
        }
    }

    private function getInfo($get) {
        $id = Util::get($get, 'id', 0);
        $list = SListFinder::getById($id);
        if(is_null($list)) {
            throw new NotFoundException;
        }

        $timer = new Timer();
        $timer->start();
        $data = $list->getData();
        $timer->stop();

        $count = 0;
        $valid = is_array($data);
        if($valid) {
            $count = count($data);
        }

        return [
            'valid' => $valid,
            'count' => $count,
            'taken' => $timer->taken()
        ];
    }
};
