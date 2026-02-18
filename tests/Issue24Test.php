<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Facades\Blasp;

class Issue24Test extends TestCase
{
    public function test_etre_not_flagged_as_profanity()
    {
        $result = Blasp::check('Le cadre pourrait être un peu mieux');
        $this->assertFalse($result->isOffensive(), 'être should not be flagged. Found: ' . implode(', ', $result->uniqueWords()));
    }

    public function test_are_accent_not_flagged()
    {
        $result = Blasp::check('aré');
        $this->assertFalse($result->isOffensive(), 'aré should not be flagged. Found: ' . implode(', ', $result->uniqueWords()));
    }

    public function test_tete_not_flagged()
    {
        $result = Blasp::check('tête tete');
        $this->assertFalse($result->isOffensive(), 'tête should not be flagged. Found: ' . implode(', ', $result->uniqueWords()));
    }

    public function test_actual_profanity_still_detected()
    {
        $result = Blasp::check('shit');
        $this->assertTrue($result->isOffensive(), 'Actual profanity should still be detected after unicode fix');
    }
}
