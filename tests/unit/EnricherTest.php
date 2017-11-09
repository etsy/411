<?php

class EnricherTest extends TestCase {
    public function testGetEnricher() {
        $this->assertSame('FOO\\IP_Enricher', FOO\Enricher::getEnricher('ip'));
        $this->assertSame('FOO\\Null_Enricher', FOO\Enricher::getEnricher(null));
    }
}
