<?php

namespace FOO;

/**
 * Class DB
 * Database wrapper.
 * @package FOO
 */
class DB {
    // Return types.
    /** Return # of rows updated. */
    const CNT = 0;
    /** Return a single value. */
    const VAL = 1;
    /** Return a single row. */
    const ROW = 2;
    /** Return all values. */
    const COL = 3;
    /** Return all rows. */
    const ALL = 4;

    /** @var \PDO Database handle. */
    public static $dbh = null;

    /**
     * Initializes the class.
     */
    public static function init() {
        if(self::$dbh) {
            return;
        }

        $dbcfg = Config::get('db');
        self::connect($dbcfg['dsn'], $dbcfg['user'], $dbcfg['pass']);
    }

    /**
     * Connect to the database.
     * @param string $dsn Database dsn.
     * @param string $usr Database username.
     * @param string $pwd Database password.
     */
    public static function connect($dsn, $usr, $pwd) {
        self::$dbh = new \PDO($dsn, $usr, $pwd, [
//            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
        Hook::call('db.connect');
    }

    /**
     * Disconnect from the database.
     */
    public static function disconnect() {
        self::$dbh = null;
        Hook::call('db.disconnect');
    }

    public static function getType() {
      return self::$dbh->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }
    /**
     * Keep the connection alive.
     */
    public static function keepAlive() {}

    /**
     * Query the database.
     * @param string $query The query to execute.
     * @param mixed[] $params The parameters to pass in.
     * @param int $ret_type The type of data to return.
     * @return mixed Data from the DB.
     * @throws DBException
     */
    public static function query($query, array $params=[], $ret_type=self::ALL) {
        self::keepAlive();
        list($query) = Hook::call('db.query', [$query]);

        $stmt = self::$dbh->prepare($query);
        if(!$stmt) {
            throw new DBException('Query failed: ' . $stmt->errorInfo()[2]);
        }

        $i = 1;
        foreach($params as $param) {
            if(is_int($param)) {
                $type = \PDO::PARAM_INT;
            } else if(is_bool($param)) {
                $type = \PDO::PARAM_BOOL;
            } else if(is_null($param)) {
                $type = \PDO::PARAM_NULL;
            } else {
                $type = \PDO::PARAM_STR;
            }
            $stmt->bindValue($i, $param, $type);
            $i += 1;
        }
        if(!$stmt->execute()) {
            throw new DBException('Query failed: ' . $stmt->errorInfo()[2]);
        }

        Logger::dbg($query, $params);

        $ret = null;
        switch($ret_type) {
            case self::CNT:
                $ret = $stmt->rowCount();
                break;
            case self::VAL:
                $ret = $stmt->fetchColumn();
                break;
            case self::ROW:
                $ret = $stmt->fetch(\PDO::FETCH_ASSOC);
                break;
            case self::COL:
                $ret = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
                break;
            case self::ALL:
                $ret = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                break;
        }
        $stmt->closeCursor();
        if($ret === false) {
            $ret = null;
        }

        return $ret;
    }

    /**
     * Get the ID of the last inserted row.
     * @return int An ID.
     */
    public static function insertId() {
        return (int) self::$dbh->lastInsertId();
    }

    /**
     * Helper function to generate the (?, ?, ..., ?) string necessary for parameterized queries with an IN clause.
     * @param int $n The number of values.
     * @return string Placeholder fragment for the IN clause.
     */
    public static function inPlaceholder($n) {
        return '(' . ($n ? implode(',', array_fill(0, $n, '?')):'NULL') . ')';
    }

    /**
     * Generate a placeholder of the form "`FIELD`" for parameterized queries. If a table name is passed in, this
     * will generate "`TABLE`.`FIELD`".
     * @param string $n The key name.
     * @param string $t The table name.
     * @return string A placeholder fragment.
     */
    public static function kPlaceholder($n, $t=null) {
        return is_null($t) ?
            sprintf('`%s`', $n):sprintf('`%s`.`%s`', $t, $n);
    }

    /**
     * Same as kPlaceholder, but accepts an array.
     * @param mixed[] $arr The array of values.
     * @param string $t The table name.
     * @return string A placeholder fragment.
     */
    public static function kPlaceholders($arr, $t=null) {
        $ret = [];
        foreach($arr as $val) {
            $ret[] = self::kPlaceholder($val, $t);
        }
        return $ret;
    }

    /**
     * Generate a placeholder of the form "`FIELD` = ?" for parameterized queries. If a table name is passed in, this
     * will generate "`TABLE`.`FIELD` = ?".
     * @param string $n The key name.
     * @param string $t The table name.
     * @return string A placeholder fragment.
     */
    public static function kvPlaceholder($n, $t=null) {
        return sprintf('%s = ?', self::kPlaceholder($n, $t));
    }

    /**
     * Same as kvPlaceholder, but accepts an array.
     * @param mixed[] $arr The array of values.
     * @param string $t The table name.
     * @return string[] An array of placeholder fragments.
     */
    public static function kvPlaceholders($arr, $t=null) {
        $ret = [];
        foreach($arr as $val) {
            $ret[] = self::kvPlaceholder($val, $t);
        }
        return $ret;
    }
}

/**
 * Class DBException
 * Base DB exception class.
 * @package FOO
 */
class DBException extends \Exception {}

/**
 * Class ValidationException
 * Exception while validating a Model attribute.
 * @package FOO
 */
class ValidationException extends DBException {}
