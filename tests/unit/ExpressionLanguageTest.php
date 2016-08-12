<?php

class ExpressionLanguageTest extends PHPUnit_Framework_TestCase {
    const NUM_BYTES = 16;

    public function testEvaluate() {
        $el = new FOO\ExpressionLanguage();

        $ret =  $el->evaluate('trim(substr(x, strlen(x) - 3, strlen(x)))', [
            'x' => 'magical! x '
        ]);
        $this->assertSame('x', $ret);
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testCompile() {
        $el = new FOO\ExpressionLanguage();

        $ret =  $el->compile('trim()');
    }
}
