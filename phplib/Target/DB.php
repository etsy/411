<?php

namespace FOO;

/**
 * Class DB_Target
 * Persists Alert objects to the database & ES. This is enabled by default on all searches but can be disabled by overriding
 * the getTargets method.
 * @package FOO
 */
class DB_Target extends Target {
    public static $TYPE = 'db';

    public static $DESC = 'Saves an Alert to the database & ES';

    /** @var ESClient Client wrapper for handling batch requests */
    private $client;

    public function __construct(array $data=null) {
        parent::__construct($data);

        $this->client = new ESClient;
    }

    /**
     * Persists the Alert object to the database. This amount to calling
     * store on the Model.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     */
    public function process(Alert $alert, $date) {
        $alert->store();

        // Log a creation entry.
        $log = new AlertLog();
        $log['alert_id'] = $alert['id'];
        $log['action'] = AlertLog::A_CREATE;
        $log->store();

        $this->client->update($alert);
    }

    /**
     * Send any remaining Alerts to ES.
     * @param int $date The current date.
     * @throws TargetException
     */
    public function finalize($date) {
        $this->client->finalize();
    }
}
