<?php

namespace FOO {
class TestElement extends Element {
    public static $TYPE = 'x';
    public static $TYPES = [TestElement::class];
    public static function generateDataSchema() {
        return [
            'a' => [self::T_BOOL, null, false],
        ];
    }
    public function process(Alert $alert, $date) {}
    public function finalize($date) {}
}
}

namespace {
class ElementTest extends TestCase {
    public function testSerialize() {
        $element = new FOO\TestElement();

        $data = ['data' => [], 'archived' => false];
        $expected = ['data' => '{}', 'archived' => false];
        $this->assertSame($expected, TestHelper::invokeMethod($element, 'serialize', [$data]));
    }

    public function testDeserialize() {
        $element = new FOO\TestElement();

        $data = ['data' => '{}', 'archived' => false];
        $expected = ['data' => [], 'archived' => false];
        $this->assertSame($expected, TestHelper::invokeMethod($element, 'deserialize', [$data]));
    }

    public function testValidateData() {
        $element = new FOO\TestElement();

        $element['data']['a'] = true;
        $element['lifetime'] = 0;
        $element->validate();
    }

    /**
     * @expectedException FOO\ValidationException
     */
    public function testValidateDataUnexpectedKey() {
        $element = new FOO\TestElement();

        $element['data']['b'] = true;
        $element['lifetime'] = 0;
        $element->validate();
    }

    /**
     * @expectedException FOO\ValidationException
     */
    public function testValidateDataWrongType() {
        $element = new FOO\TestElement();

        $element['data']['b'] = 'true';
        $element['lifetime'] = 0;
        $element->validate();
    }

    public function testFinderGetBySearch() {
        TestHelper::populateDB([
            [FOO\Filter::$TABLE, 1, 0, 1, 'null', 1, 0, '', '', 0, 0, 0],
            [FOO\Filter::$TABLE, 2, 0, 2, 'null', 1, 0, '', '', 0, 0, 0],
        ]);

        $filters = FOO\FilterFinder::getBySearch(1);
        $this->assertCount(1, $filters);
        $this->assertSame(1, $filters[0]['id']);
    }

    public function testFinderReap() {
        TestHelper::populateDB([
            [FOO\Filter::$TABLE, 1, 0, 1, 'null', 1, 1, '', '', 0, 0, 0],
        ]);

        FOO\FilterFinder::reap(60);
        $this->assertSame([], FOO\FilterFinder::getAll());
    }
}
}
