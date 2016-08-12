<?php

namespace FOO;

/**
 * Class MapKey_Filter
 * Maps matching keys on Alerts.
 * @package FOO
 */
class MapKey_Filter extends Filter {
    public static $TYPE = 'mapkey';

    public static $DESC = 'Maps each key that matches <key_regex> with the SEL expression <key_expr>.';

    protected static function generateDataSchema() {
        return [
            'key_regex' => [static::T_STR, null, ''],
            'key_expr' => [static::T_STR, null, ''],
        ];
    }

    public function validateData(array $data) {
        parent::validateData($data);

        $regex = $data['data']['key_regex'];
        if(@preg_match("/$regex/", null) === false) {
            throw new ValidationException('Invalid regex');
        }

        try {
            $el = new ExpressionLanguage();
            $el->parse($data['data']['key_expr'], ['key', 'value']);
        } catch(\Symfony\Component\ExpressionLanguage\SyntaxError $e) {
            throw new ValidationException($e->getMessage());
        }
    }

    /**
     * Maps any keys that match the regex.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] The Alert object.
     */
    public function process(Alert $alert, $date) {
        $data = $this->obj['data'];
        $key_regex = Util::get($data, 'key_regex', '');
        $expression = Util::get($data, 'key_expr', '');
        $keys = array_keys($alert['content']);

        $el = new ExpressionLanguage();

        foreach ($keys as $key) {
            $value = Util::get($alert['content'], $key, '');
            $match = preg_match("/$key_regex/", $key);
            if (!$match) {
                continue;
            }

            try {
                $new_key = $el->evaluate($expression, ['key' => $key, 'value' => $value]);
                unset($alert['content'][$key]);
                $alert['content'][$new_key] = $value;
            } catch(\RuntimeException $e) {
                throw new FilterException($e->getMessage());
            }
        }

        return [$alert];
    }
}
