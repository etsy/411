<?php

namespace FOO {
class TestModel extends Model {
    public static $TABLE = 'tests';
    public static $PKEY = 'test_id';
    public static function generateSchema() {
        return [
            'bool' => [self::T_BOOL, null, false],
            'num' => [self::T_NUM, null, 0],
            'str' => [self::T_STR, null, ''],
            'enum' => [self::T_ENUM, [1, 2], 1],
            'arr' => [self::T_ARR, null, []],
            'obj' => [self::T_OBJ, null, []],
        ];
    }
    protected function serialize(array $data) {
        $data['bool'] = (bool)$data['bool'];
        $data['arr'] = json_encode($data['arr']);
        $data['obj'] = json_encode((object)$data['obj']);
        return parent::serialize($data);
    }

    protected function deserialize(array $data) {
        $data['bool'] = (bool)$data['bool'];
        $data['arr'] = json_decode($data['arr']);
        $data['obj'] = json_decode($data['obj'], true);
        return parent::deserialize($data);
    }
}

class TestModelFinder extends ModelFinder {
    public static $MODEL = 'TestModel';
}
}

namespace {
class ModelTest extends DBTestCase {
    public function setUp() {
        parent::setUp();
        FOO\DB::query('
            CREATE TABLE `tests` (
                `test_id` INTEGER PRIMARY KEY,
                `site_id` INTEGER NOT NULL,
                `bool` UNSIGNED INTEGER NOT NULL,
                `num` UNSIGNED INTEGER NOT NULL,
                `str` VARCHAR(255) NOT NULL,
                `enum` UNSIGNED INTEGER NOT NULL,
                `arr` TEXT NOT NULL,
                `obj` TEXT NOT NULL,

                `archived` UNSIGNED INTEGER NOT NULL,
                `create_date` UNSIGNED INTEGER NOT NULL,
                `update_date` UNSIGNED INTEGER NOT NULL
            )
        ');
    }

    public function testStore() {
        $model = new FOO\TestModel();
        $this->assertTrue($model->store());

        $model_ = FOO\TestModelFinder::getById($model['id']);
        $this->assertTrue($model_->store());
    }

    public function testGetDefaults() {
        $expected = [
            'bool' => false,
            'num' => 0,
            'str' => '',
            'enum' => 1,
            'arr' => [],
            'obj' => [],
            'archived' => false,
            'create_date' => 0,
            'update_date' => 0,
        ];
        $this->assertSame($expected, FOO\TestModel::getDefaults());
    }

    public function testValidateData() {
        $model = new FOO\TestModel();

        $model['bool'] = true;
        $model['num'] = 2;
        $model['str'] = 'stuff';
        $model['enum'] = 2;
        $model['arr'] = [1, 2];
        $model['obj'] = ['a' => 'b'];
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testValidateDataUnexpectedKey() {
        $model = new FOO\TestModel();

        $model['b'] = true;
        $model->validate();
    }

    /**
     * @expectedException FOO\ValidationException
     */
    public function testValidateDataBadEnum() {
        $model = new FOO\TestModel();

        $model['enum'] = 99;
        $model->validate();
    }

    /**
     * @expectedException FOO\ValidationException
     */
    public function testValidateDataBadArr() {
        $model = new FOO\TestModel();

        $model['arr'] = 'str';
        $model->validate();
    }

    /**
     * @expectedException FOO\ValidationException
     */
    public function testValidateDataBadObj() {
        $model = new FOO\TestModel();

        $model['obj'] = 1;
        $model->validate();
    }

    public function testIsNew() {
        $model = new FOO\TestModel();
        $this->assertTrue($model->isNew());
    }

    public function testToArray() {
        $model = new FOO\TestModel();
        $data = $model->toArray(['bool']);

        $this->assertSame(['bool', 'id', 'archived', 'create_date', 'update_date'], array_keys($data));
    }

    public function testFinderHydrateModels() {
        $raw = [
            [
                'id' => '1',
                'bool' => '1',
                'num' => '2',
                'str' => 'stuff',
                'enum' => '2',
                'arr' => '[1, 2]',
                'obj' => '{"a": "b"}',
                'create_date' => '0',
                'update_date' => '0',
                'archived' => '0',
            ]
        ];

        $expected = [
            'id' => 1,
            'bool' => 1,
            'num' => 2,
            'str' => 'stuff',
            'enum' => 2,
            'arr' => [1, 2],
            'obj' => (object) ['a' => 'b'],
            'create_date' => 0,
            'update_date' => 0,
            'archived' => 0,
        ];
        $objs = FOO\TestModelFinder::hydrateModels($raw);
        $this->assertCount(1, $objs);
        $this->assertEquals($expected, $objs[0]->toArray());
    }

    public function testFinderGenerateWhere() {
        $finder = 'FOO\\TestModelFinder';

        $where = ['`site_id` = ?', '`archived` = ?'];
        $vals = [0, 0];
        $this->assertSame([$where, $vals], TestHelper::invokeMethod($finder, 'generateWhere', [[]]));

        $where = ['`site_id` = ?', '(`create_date` > ? OR `update_date` > ?)'];
        $vals = [0, 10, 10];
        $this->assertSame([$where, $vals], TestHelper::invokeMethod($finder, 'generateWhere', [['time' => 10]]));

        $where = ['`test_id` IN (?)', '`site_id` = ?', '`archived` = ?'];
        $vals = [1, 0, 0];
        $this->assertSame([$where, $vals], TestHelper::invokeMethod($finder, 'generateWhere', [['id' => 1]]));
    }
}
}
