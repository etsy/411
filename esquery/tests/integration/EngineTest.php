<?php

class EngineTest extends PHPUnit_Framework_TestCase {
    public function testCountQuery() {
        $query = 'type:cat';
        $settings = [
            'index' => 'test',
            'count' => true,
        ];
        $mock = $this->getMock('Elasticsearch\\Elasticsearch', ['search']);
        $mock->expects($this->once())->method('search')
            ->willReturn([
                'took' => 2,
                'timed_out' => false,
                '_shards' => [
                    'total' => 5,
                    'successful' => 5,
                    'failed' => 0,
                ],
                'hits' => [
                    'total' => 4,
                    'max_score' => 0,
                    'hits' => [],
                ],
            ]);
        $func = function($host) use($mock) {
            return $mock;
        };

        $engine = new ESQuery\Engine($query, $settings, $func);
        $expected = ['count' => 4];
        $this->assertSame($expected, $engine->execute());
    }

    public function testAggQuery() {
        $query = 'type:cat | agg:terms field:color';
        $settings = [
            'index' => 'test',
        ];
        $ret = [
            'took' => 2,
            'timed_out' => false,
            '_shards' => [
                'total' => 5,
                'successful' => 5,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 4,
                'max_score' => 0,
                'hits' => [],
            ],
            'aggregations' => [
                '$$$_color' => [
                    'doc_count_error_upper_bound' => 0,
                    'sum_other_doc_count' => 0,
                    'buckets' => [
                        ['key' => 'blue', 'doc_count' => 1],
                        ['key' => 'green', 'doc_count' => 1],
                        ['key' => 'orange', 'doc_count' => 1],
                        ['key' => 'red','doc_count' => 1],
                    ],
                ],
            ],
        ];

        $mock = $this->getMock('Elasticsearch\\Elasticsearch', ['search']);
        $mock->expects($this->once())->method('search')
             ->willReturn($ret);
        $func = function($host) use($mock) {
            return $mock;
        };

        $engine = new ESQuery\Engine($query, $settings, $func);
        $expected = [
            ['color' => 'blue', 'count' => 1],
            ['color' => 'green', 'count' => 1],
            ['color' => 'orange', 'count' => 1],
            ['color' => 'red', 'count' => 1],
        ];
        $this->assertSame($expected, $engine->execute());
    }

    public function testJoinQuery() {
        $query = 'type:cat | join source:name target:nickname';
        $settings = [
            'index' => 'test',
        ];
        $ret_a = [
            'took' => 2,
            'timed_out' => false,
            '_shards' => [
                'total' => 5,
                'successful' => 5,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 4,
                'max_score' => 0,
                'hits' => [
                    ['_source' => ['type' => 'cat', 'name' => 'alice']],
                    ['_source' => ['type' => 'cat', 'name' => 'bob']],
                    ['_source' => ['type' => 'cat', 'name' => 'jan']],
                    ['_source' => ['type' => 'cat', 'name' => 'mary']],
                ],
            ],
        ];
        $ret_b = [
            'took' => 2,
            'timed_out' => false,
            '_shards' => [
                'total' => 5,
                'successful' => 5,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 2,
                'max_score' => 0,
                'hits' => [
                    ['_source' => ['type' => 'cat', 'name' => 'alice', 'color' => 'blue']],
                    ['_source' => ['type' => 'cat', 'name' => 'bob', 'color' => 'green']],
                ],
            ],
        ];

        $mock = $this->getMock('Elasticsearch\\Elasticsearch', ['search', 'index']);
        $mock->expects($this->at(0))->method('search')
             ->willReturn($ret_a);
        $mock->expects($this->at(1))->method('index')
             ->willReturn(1);
        $mock->expects($this->at(2))->method('search')
             ->willReturn($ret_b);
        $func = function($host) use($mock) {
            return $mock;
        };

        $engine = new ESQuery\Engine($query, $settings, $func);
        $expected = [
            ['type' => 'cat', 'name' => 'alice', 'color' => 'blue'],
            ['type' => 'cat', 'name' => 'bob', 'color' => 'green'],
        ];
        $this->assertSame($expected, $engine->execute());
    }

    public function testTransQuery() {
        $query = 'type:cat | transaction field:name';
        $settings = [
            'index' => 'test',
        ];
        $ret = [
            'took' => 2,
            'timed_out' => false,
            '_shards' => [
                'total' => 5,
                'successful' => 5,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 4,
                'max_score' => 0,
                'hits' => [
                    ['_source' => ['type' => 'cat', 'name' => 'alice', 'color' => 'blue']],
                    ['_source' => ['type' => 'cat', 'name' => 'bob', 'color' => 'green']],
                    ['_source' => ['type' => 'cat', 'name' => 'alice', 'level' => 2]],
                    ['_source' => ['type' => 'cat', 'name' => 'bob', 'color' => 'orange', 'height' => 55]],
                ],
            ],
        ];

        $mock = $this->getMock('Elasticsearch\\Elasticsearch', ['search']);
        $mock->expects($this->once())->method('search')
             ->willReturn($ret);
        $func = function($host) use($mock) {
            return $mock;
        };

        $engine = new ESQuery\Engine($query, $settings, $func);
        $expected = [
            ['type' => 'cat', 'name' => 'alice', 'color' => 'blue', 'level' => 2],
            ['type' => 'cat', 'name' => 'bob', 'color' => 'orange', 'height' => 55],
        ];
        $this->assertSame($expected, $engine->execute());
    }
}
