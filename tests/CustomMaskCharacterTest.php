<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Laravel\Facade as Blasp;

class CustomMaskCharacterTest extends TestCase
{
    public function test_default_mask_character_is_asterisk()
    {
        $result = Blasp::check('This is fucking awesome');
        $this->assertEquals('This is ******* awesome', $result->clean());
    }

    public function test_custom_mask_character_with_hash()
    {
        $result = Blasp::mask('#')->check('This is fucking awesome');
        $this->assertEquals('This is ####### awesome', $result->clean());
    }

    public function test_custom_mask_character_with_dash()
    {
        $result = Blasp::mask('-')->check('This shit is bad');
        $this->assertEquals('This ---- is bad', $result->clean());
    }

    public function test_custom_mask_character_with_underscore()
    {
        $result = Blasp::mask('_')->check('What the hell');
        $this->assertEquals('What the ____', $result->clean());
    }

    public function test_custom_mask_character_with_unicode()
    {
        $result = Blasp::mask('●')->check('This is damn good');
        $this->assertEquals('This is ●●●● good', $result->clean());
    }

    public function test_custom_mask_character_only_uses_first_character()
    {
        $result = Blasp::mask('###')->check('This is fucking awesome');
        $this->assertEquals('This is ####### awesome', $result->clean());
    }

    public function test_mask_character_can_be_chained_with_language()
    {
        $result = Blasp::spanish()->mask('@')->check('Esto es mierda');
        $this->assertEquals('Esto es @@@@@@', $result->clean());
    }

    public function test_mask_character_works_with_multiple_profanities()
    {
        $result = Blasp::mask('!')->check('fuck this shit damn');
        $this->assertEquals('!!!! this !!!! !!!!', $result->clean());
        $this->assertEquals(3, $result->count());
    }

    public function test_mask_character_with_block_list()
    {
        $result = Blasp::mask('#')->block('test')->check('This is a test');
        $this->assertEquals('This is a ####', $result->clean());
    }

    public function test_different_mask_characters_can_be_used_independently()
    {
        $resultHash = Blasp::mask('#')->check('This is shit');
        $resultDash = Blasp::mask('-')->check('This is shit');

        $this->assertEquals('This is ####', $resultHash->clean());
        $this->assertEquals('This is ----', $resultDash->clean());
    }
}
