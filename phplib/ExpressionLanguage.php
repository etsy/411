<?php

namespace FOO;

use \Symfony\Component\ExpressionLanguage as SEL;

/**
 * Class ExpressionLanguage
 * Base expression language class with commonly used functions enabled.
 * @package FOO
 */
class ExpressionLanguage extends SEL\ExpressionLanguage {
    /** @var Whitelisted functions available in ExpressionLanguage. */
    private static $FUNCTIONS = ['explode', 'implode', 'trim', 'substr', 'str_replace', 'strlen', 'json_encode', 'json_decode'];

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct();

        foreach(self::$FUNCTIONS as $function) {
            $this->register($function, [__CLASS__, 'compileStub'], $this->evaluateWrapper($function));
        }
    }

    /**
     * Wraps a function so that it's correctly called by an SEL expression.
     * @param string|string[] $function The function name.
     */
    public static function evaluateWrapper($function) {
        return function() use($function) {
            $args = func_get_args();
            array_shift($args);
            return call_user_func_array($function, $args);
        };
    }

    public static function compileStub() {
        throw new \BadMethodCallException();
    }
}
