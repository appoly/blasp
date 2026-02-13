<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Facades\Blasp;

class EdgeCaseTest extends TestCase
{
    public function test_fuckme_not_detected_across_word_boundaries()
    {
        $result = Blasp::allLanguages()->check('fuck merde scheiße mierda');

        $this->assertTrue($result->hasProfanity());
        $this->assertNotContains('fuckme', $result->getUniqueProfanitiesFound());

        $found = $result->getUniqueProfanitiesFound();
        $this->assertContains('fuck', $found);
        $this->assertContains('merde', $found);
        $this->assertContains('scheiße', $found);
        $this->assertContains('mierda', $found);
    }

    public function test_removed_compound_profanities_not_detected()
    {
        $result = Blasp::check('fuck me hard');
        $this->assertTrue($result->hasProfanity());
        $this->assertNotContains('fuckme', $result->getUniqueProfanitiesFound());
        $this->assertNotContains('fuckmehard', $result->getUniqueProfanitiesFound());
        $this->assertNotContains('fuckher', $result->getUniqueProfanitiesFound());

        $this->assertContains('fuck', $result->getUniqueProfanitiesFound());
    }

    public function test_legitimate_compound_profanities_still_work()
    {
        $result = Blasp::check('fuckyou you fuckhead');
        $this->assertTrue($result->hasProfanity());
        $this->assertContains('fuckyou', $result->getUniqueProfanitiesFound());
        $this->assertContains('fuckhead', $result->getUniqueProfanitiesFound());
    }
}
