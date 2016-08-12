<?php

namespace FOO;

/**
 * Class Jobs_REST
 * REST endpoint for manipulating Jobs.
 * @package FOO
 */
class Jobs_REST extends Models_REST {
    const SLOG_ENABLED = false;

    protected static $MODEL = 'Job';
    protected static $CREATABLE = [];
    protected static $QUERYABLE = ['type', 'target_id'];
    protected static $READABLE = null;
    protected static $UPDATEABLE = [];

    public function allowRead() {
        return Auth::isAuthenticated();
    }
    public function allowCreate() {
        return false;
    }
    public function allowUpdate() {
        return false;
    }
    public function allowDelete() {
        return false;
    }

    public function GET(array $get) {
        $get['count'] = 30;
        $get['offset'] = 0;
        return self::format($this->read($get));
    }
};
