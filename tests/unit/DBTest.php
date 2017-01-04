<?php

class DBTest extends TestCase {
    public function testInsertId() {
        $sql = sprintf('INSERT INTO `%s` VALUES (0, ?, ?)', FOO\DBMeta::$TABLE);
        FOO\DB::query($sql, ['a', 'b']);
        $id = FOO\DB::insertId();
        FOO\DB::query($sql, ['c', 'd']);
        $id_ = FOO\DB::insertId();

        $this->assertGreaterThan($id, $id_);
    }

    public function testInPlaceholder() {
        $this->assertSame('(NULL)', FOO\DB::inPlaceholder(0));
        $this->assertSame('(?)', FOO\DB::inPlaceholder(1));
    }

    public function testKPlaceholder() {
        $this->assertSame('`x`', FOO\DB::kPlaceholder('x'));
        $this->assertSame('`y`.`x`', FOO\DB::kPlaceholder('x', 'y'));
    }

    public function testKVPlaceholder() {
        $this->assertSame('`x` = ?', FOO\DB::kvPlaceholder('x'));
        $this->assertSame('`y`.`x` = ?', FOO\DB::kvPlaceholder('x', 'y'));
    }

    public function testKVPlaceholders() {
        $this->assertSame(['`x` = ?', '`y` = ?'], FOO\DB::kvPlaceholders(['x', 'y']));
        $this->assertSame([], FOO\DB::kvPlaceholders([]));
    }
}
