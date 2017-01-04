<?php

namespace FOO {
class TestTypeModel extends TypeModel {
    public static $TYPES = ['OneTypeModel', 'TwoTypeModel'];
}

class OneTypeModel extends TestTypeModel {
    public static $TYPE = 'one';
}
class TwoTypeModel extends TestTypeModel {
    public static $TYPE = 'two';
    public function isAccessible() {
        return false;
    }
}
}

namespace {
class TypeModelTest extends TestCase {
    public function testNewObject() {
        $obj = FOO\TestTypeModel::newObject('one');
        $this->assertInstanceOf('FOO\\OneTypeModel', $obj);
    }

    /**
     * @expectedException OutOfBoundsException
     */
    public function testNewObjectInaccesibleClass() {
        $obj = FOO\TestTypeModel::newObject('two');
    }
}
}
