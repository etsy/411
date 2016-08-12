<?php

namespace FOO;

/**
 * Class MapValue_Filter
 * Maps matching keys on Alerts.
 * @package FOO
 */
class MapValue_Filter extends Filter {
    public static $TYPE = 'mapvalue';

    public static $DESC = 'Maps each value with the SEL expression <value_expr> if the key matches <key_regex>';

    protected static function generateDataSchema() {
        return [
            'key_regex' => [static::T_STR, null, ''],
            'value_expr' => [static::T_STR, null, ''],
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
            $el->parse($data['data']['value_expr'], ['key', 'value']);
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
        $expression = Util::get($data, 'value_expr', '');
        $keys = array_keys($alert['content']);

        $el = new ExpressionLanguage();

        foreach ($keys as $key) {
            $value = Util::get($alert['content'], $key, '');
            $match = preg_match("/$key_regex/", $key);
            if (!$match) {
                continue;
            }

            try {
                $new_value = $el->evaluate($expression, ['key' => $key, 'value' => $value]);
                $alert['content'][$key] = $new_value;
            } catch(\RuntimeException $e) {
                throw new FilterException($e->getMessage());
            }
        }

        return [$alert];
    }
}
