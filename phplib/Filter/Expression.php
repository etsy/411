<?php

namespace FOO;

/**
 * Class Expression_Filter
 * Filters Alerts on an Expression.
 * @package FOO
 */
class Expression_Filter extends Filter {
    public static $TYPE = 'expression';
    public static $DESC = 'Filter alerts that match the SEL expression <expr>. <include> determines what happens to matching Alerts.';

    protected static function generateDataSchema() {
        return [
            'include' => [static::T_BOOL, null, true],
            'expr' => [static::T_STR, null, '']
        ];
    }

    public function validateData(array $data) {
        parent::validateData($data);

        try {
            $el = new ExpressionLanguage();
            $el->parse($data['data']['expr'], ['content']);
        } catch(\Symfony\Component\ExpressionLanguage\SyntaxError $e) {
            throw new ValidationException($e->getMessage());
        }
    }

    /**
     * Filter Alerts on an expression.
     * Expressions should formatted similarly to this: content['user_id'] > 1
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] The Alert object.
     * @throws FilterException
     */
    public function process(Alert $alert, $date) {
        $data = $this->obj['data'];
        $include = $data['include'];
        $expression = $data['expr'];

        try {
            $el = new ExpressionLanguage();
            $res = (bool) $el->evaluate($expression, ['content' => $alert['content']]);
        } catch(\RuntimeException $e) {
            throw new FilterException($e->getMessage());
        }

        if ($include === $res) {
            return [$alert];
        } else {
            return [];
        }
    }
}

