<?php

namespace FOO;

/**
 * Class Admin_REST
 * REST endpoint for Admin functionality.
 * @package FOO
 */
class Admin_REST extends REST {
    const T_BOOL = 0;
    const T_INT = 1;
    const T_EMAIL = 2;
    const T_TZ = 3;
    const T_STR = 4;

    public static $FIELDS = [
        'cron_enabled' => self::T_BOOL,
        'worker_enabled' => self::T_BOOL,
        'summary_enabled' => self::T_BOOL,
        'error_email_enabled' => self::T_BOOL,
        'error_email_throttle' => self::T_INT,
        'from_email' => self::T_EMAIL,
        'from_error_email' => self::T_EMAIL,
        'default_email' => self::T_EMAIL,
        'announcement' => self::T_STR,
        'timezone' => self::T_TZ,
    ];

    public function checkAuthorization() {
        if(!Auth::isAdmin()) {
            throw new UnauthorizedException('Admin required');
        }
    }

    public function GET(array $get) {
        $cfg = new DBConfig();
        $ret = [];

        foreach(self::$FIELDS as $field=>$type) {
            $ret[$field] = $cfg[$field];
        }
        return self::format($ret);
    }

    public function POST(array $get, array $data) {
        $cfg = new DBConfig();

        foreach(self::$FIELDS as $field=>$type) {
            $val = Util::get($data, $field);

            switch($type) {
            case self::T_BOOL:
                $ok = is_bool($val);
                break;
            case self::T_INT:
                $ok = is_int($val);
                break;
            case self::T_EMAIL:
                $ok = filter_var($val, FILTER_VALIDATE_EMAIL);
                break;
            case self::T_TZ:
                $ok = in_array($val, timezone_identifiers_list());
                break;
            case self::T_STR:
                break;
            }

            if(!$ok) {
                throw new ValidationException(sprintf('Invalid value for: %s', $field));
            }
        }

        foreach(self::$FIELDS as $field=>$type) {
            $cfg[$field] = Util::get($data, $field);
        }

        return self::format();
    }
}
