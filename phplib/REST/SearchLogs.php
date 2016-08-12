<?php

namespace FOO;

/**
 * Class SearchLogs_REST
 * REST endpoint for manipulating SearchLogs.
 * @package FOO
 */
class SearchLogs_REST extends Models_REST {
    const SLOG_ENABLED = false;

    protected static $MODEL = 'SearchLog';
    protected static $CREATABLE = [];
    protected static $QUERYABLE = ['search_id'];
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
};
