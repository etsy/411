<?php

namespace FOO;

/**
 * Class Reports_REST
 * REST endpoint for manipulating Reports.
 * @package FOO
 */
class Reports_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_REPORT;

    protected static $MODEL = 'Report';
    protected static $CREATABLE = [
        'type', 'name', 'description', 'frequency', 'range', 'assignee_type', 'assignee', 'enabled', 'start_date'
    ];
    protected static $QUERYABLE = [];

    protected static $READABLE = null;
    protected static $UPDATEABLE = [
        'type', 'name', 'description', 'frequency', 'range', 'assignee_type', 'assignee', 'enabled', 'start_date'
    ];

    public function GET(array $get) {
        $action = Util::get($get, 'action');

        switch($action) {
            case 'generate':
                return self::format($this->generate($get, []));
            default:
                return self::format($this->read($get));
        }
    }

    public function POST(array $get, array $data) {
        $action = Util::get($get, 'action');

        switch($action) {
            case 'generate':
                return self::format($this->generate($get, $data));
            default:
                return self::format($this->create($get, $data));
        }
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

    public function generate($get, $data) {
        if(!$this->allowCreate()) {
            throw new ForbiddenException;
        }

        $id = Util::get($get, 'id');
        $mode = Util::get($get, 'mode');

        $MODEL = 'FOO\\' . static::$MODEL;
        $FINDER = $MODEL . 'Finder';
        $schema = $MODEL::getSchema();
        $report = $FINDER::getById($id);
        foreach($data as $k=>$v) {
            if(!Util::exists($schema, $k)) {
                continue;
            }
            $report[$k] = $v;
        }
        // We might've received www-form-urlencoded data, which has no nice way to pass bools. Force
        // enabled to false to pass validation.
        $report['enabled'] = false;
        $report->validate();

        $exp = $mode == 'csv' ? new CSV_Exporter():new PDF_Exporter();
        list($content, $errors) = $exp->generate($report, $_SERVER['REQUEST_TIME']);
        $this->slog(SLog::AR_EXECUTE, $report['id']);
        if(count($errors)) {
            return self::format(null, false, $errors);
        }

        if(!Util::isTesting()) {
            header('Content-Type: ' . $exp->mimeType());
        }
        print $content;
        if(!Util::isTesting()) {
            exit(0);
        }
    }
};
