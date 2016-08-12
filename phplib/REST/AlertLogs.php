<?php

namespace FOO;

/**
 * Class AlertLogs_REST
 * REST endpoint for manipulating AlertLogs.
 * @package FOO
 */
class AlertLogs_REST extends Models_REST {
    const SLOG_ENABLED = false;

    protected static $MODEL = 'AlertLog';
    protected static $CREATABLE = [];
    protected static $QUERYABLE = ['alert_id'];
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
