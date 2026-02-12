<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Laravel\Facade as Blasp;

class EmptyInputTest extends TestCase
{
    public function test_empty_string_returns_no_profanity()
    {
        $result = Blasp::check('');

        $this->assertFalse($result->isOffensive());
        $this->assertEquals(0, $result->count());
        $this->assertEmpty($result->uniqueWords());
    }

    public function test_empty_string_returns_empty_source_and_clean_strings()
    {
        $result = Blasp::check('');

        $this->assertEquals('', $result->original());
        $this->assertEquals('', $result->clean());
    }

    public function test_null_returns_no_profanity()
    {
        $result = Blasp::check(null);

        $this->assertFalse($result->isOffensive());
        $this->assertEquals('', $result->original());
        $this->assertEquals('', $result->clean());
    }

    public function test_profanity_still_detected_after_empty_check()
    {
        Blasp::check('');
        $result = Blasp::check('shit');

        $this->assertTrue($result->isOffensive());
    }
}
