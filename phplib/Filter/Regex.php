<?php

namespace FOO;

/**
 * Class Regex_Filter
 * Eliminates duplicate Alerts. Runs a regex pattern on Alerts.
 * @package FOO
 */
class Regex_Filter extends Filter {
    public static $TYPE = 'regex';

    public static $DESC = 'Filters Alerts when the data stored under <key> matches <regex>. <include> determines what happens to matching Alerts. <key> can be * or a specific key name.';

    protected static function generateDataSchema() {
        return [
            'include' => [static::T_BOOL, null, true],
            'key' => [static::T_STR, null, '*'],
            'regex' => [static::T_STR, null, '']
        ];
    }

    public function validateData(array $data) {
        parent::validateData($data);

        $regex = $data['data']['regex'];
        if(@preg_match("/$regex/", null) === false) {
            throw new ValidationException('Invalid regex');
        }
    }

    /**
     * Return the Alert if the regex check passes.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] The Alert object.
     */
    public function process(Alert $alert, $date) {
        $data = $this->obj['data'];
        $include = Util::get($data, 'include', true);
        $keys = [Util::get($data, 'key', '*')];
        if ($keys[0] == '*') {
            $keys = array_keys($alert['content']);
        }
        $regex = Util::get($data, 'regex', '');

        foreach ($keys as $key) {
            $match = preg_match("/$regex/", Util::get($alert['content'], $key, ''));
            if (!$match) {
                continue;
            }
            // If there's a match, we know enough to return.
            if ($include) {
                return [$alert];
            } else {
                return [];
            }
        }

        // Reached the end without matching.
        if ($include) {
            return [];
        } else {
            return [$alert];
        }
    }
}
