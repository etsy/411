<?php

namespace FOO;

/**
 * Class Groups_REST
 * REST endpoint for manipulating Groups.
 * @package FOO
 */
class Groups_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_GROUP;

    protected static $MODEL = 'Group';
    protected static $CREATABLE = [
        'type', 'state', 'name'
    ];
    protected static $QUERYABLE = [];
    protected static $READABLE = null;
    protected static $UPDATEABLE = [
        'type', 'state', 'name'
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
};
