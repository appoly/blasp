<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Facades\Blasp;
use Blaspsoft\Blasp\Enums\Severity;

class SeverityMapTest extends TestCase
{
    // --- Spanish ---

    public function test_spanish_mild_words_filtered_by_moderate_severity(): void
    {
        // 'tonto' is mild — should be ignored when filtering at Moderate
        $result = Blasp::in('spanish')->withSeverity(Severity::Moderate)->check('eres tonto');
        $this->assertFalse($result->isOffensive(), 'Mild word "tonto" should be ignored at Moderate severity');
    }

    public function test_spanish_moderate_words_caught_at_moderate_severity(): void
    {
        // 'cabrón' is moderate — should be caught when filtering at Moderate
        $result = Blasp::in('spanish')->withSeverity(Severity::Moderate)->check('eres cabrón');
        $this->assertTrue($result->isOffensive(), 'Moderate word "cabrón" should be caught at Moderate severity');
    }

    public function test_spanish_moderate_words_filtered_by_high_severity(): void
    {
        // 'gilipollas' is moderate — should be ignored when filtering at High
        $result = Blasp::in('spanish')->withSeverity(Severity::High)->check('eres gilipollas');
        $this->assertFalse($result->isOffensive(), 'Moderate word "gilipollas" should be ignored at High severity');
    }

    public function test_spanish_default_high_words_caught(): void
    {
        // 'mierda' is not in severity map — defaults to High
        $result = Blasp::in('spanish')->withSeverity(Severity::High)->check('esto es mierda');
        $this->assertTrue($result->isOffensive(), 'Default High word "mierda" should be caught at High severity');
    }

    public function test_spanish_extreme_words_caught_at_extreme(): void
    {
        $result = Blasp::in('spanish')->withSeverity(Severity::Extreme)->check('maricón');
        $this->assertTrue($result->isOffensive(), 'Extreme word "maricón" should be caught at Extreme severity');
    }

    public function test_spanish_high_words_filtered_by_extreme(): void
    {
        // 'mierda' defaults to High — should be ignored at Extreme
        $result = Blasp::in('spanish')->withSeverity(Severity::Extreme)->check('mierda');
        $this->assertFalse($result->isOffensive(), 'High word "mierda" should be ignored at Extreme severity');
    }

    // --- French ---

    public function test_french_mild_words_filtered_by_moderate_severity(): void
    {
        // 'idiot' is mild — should be ignored at Moderate
        $result = Blasp::in('french')->withSeverity(Severity::Moderate)->check('quel idiot');
        $this->assertFalse($result->isOffensive(), 'Mild word "idiot" should be ignored at Moderate severity');
    }

    public function test_french_moderate_words_caught_at_moderate_severity(): void
    {
        // 'connard' is moderate — should be caught at Moderate
        $result = Blasp::in('french')->withSeverity(Severity::Moderate)->check('espèce de connard');
        $this->assertTrue($result->isOffensive(), 'Moderate word "connard" should be caught at Moderate severity');
    }

    public function test_french_moderate_words_filtered_by_high_severity(): void
    {
        // 'salaud' is moderate — should be ignored at High
        $result = Blasp::in('french')->withSeverity(Severity::High)->check('quel salaud');
        $this->assertFalse($result->isOffensive(), 'Moderate word "salaud" should be ignored at High severity');
    }

    public function test_french_default_high_words_caught(): void
    {
        // 'merde' is not in severity map — defaults to High
        $result = Blasp::in('french')->withSeverity(Severity::High)->check('merde alors');
        $this->assertTrue($result->isOffensive(), 'Default High word "merde" should be caught at High severity');
    }

    public function test_french_extreme_words_caught_at_extreme(): void
    {
        $result = Blasp::in('french')->withSeverity(Severity::Extreme)->check('sale pédé');
        $this->assertTrue($result->isOffensive(), 'Extreme word "pédé" should be caught at Extreme severity');
    }

    public function test_french_high_words_filtered_by_extreme(): void
    {
        // 'merde' defaults to High — should be ignored at Extreme
        $result = Blasp::in('french')->withSeverity(Severity::Extreme)->check('merde');
        $this->assertFalse($result->isOffensive(), 'High word "merde" should be ignored at Extreme severity');
    }

    // --- German ---

    public function test_german_mild_words_filtered_by_moderate_severity(): void
    {
        // 'mist' is mild — should be ignored at Moderate
        $result = Blasp::in('german')->withSeverity(Severity::Moderate)->check('so ein mist');
        $this->assertFalse($result->isOffensive(), 'Mild word "mist" should be ignored at Moderate severity');
    }

    public function test_german_moderate_words_caught_at_moderate_severity(): void
    {
        // 'arschloch' is moderate — should be caught at Moderate
        $result = Blasp::in('german')->withSeverity(Severity::Moderate)->check('du arschloch');
        $this->assertTrue($result->isOffensive(), 'Moderate word "arschloch" should be caught at Moderate severity');
    }

    public function test_german_moderate_words_filtered_by_high_severity(): void
    {
        // 'wichser' is moderate — should be ignored at High
        $result = Blasp::in('german')->withSeverity(Severity::High)->check('du wichser');
        $this->assertFalse($result->isOffensive(), 'Moderate word "wichser" should be ignored at High severity');
    }

    public function test_german_default_high_words_caught(): void
    {
        // 'ficken' is not in severity map — defaults to High
        $result = Blasp::in('german')->withSeverity(Severity::High)->check('ficken');
        $this->assertTrue($result->isOffensive(), 'Default High word "ficken" should be caught at High severity');
    }

    public function test_german_extreme_words_caught_at_extreme(): void
    {
        $result = Blasp::in('german')->withSeverity(Severity::Extreme)->check('du tunte');
        $this->assertTrue($result->isOffensive(), 'Extreme word "tunte" should be caught at Extreme severity');
    }

    public function test_german_high_words_filtered_by_extreme(): void
    {
        // 'scheiße' defaults to High — should be ignored at Extreme
        $result = Blasp::in('german')->withSeverity(Severity::Extreme)->check('scheiße');
        $this->assertFalse($result->isOffensive(), 'High word "scheiße" should be ignored at Extreme severity');
    }

    // --- Cross-cutting ---

    public function test_unmapped_words_default_to_high_across_languages(): void
    {
        // Words not in any severity map should default to High
        $spanishResult = Blasp::in('spanish')->check('joder');
        $frenchResult = Blasp::in('french')->check('putain');
        $germanResult = Blasp::in('german')->check('fotze');

        $this->assertTrue($spanishResult->isOffensive());
        $this->assertTrue($frenchResult->isOffensive());
        $this->assertTrue($germanResult->isOffensive());

        // All should be at least High severity
        $this->assertTrue($spanishResult->severity()->isAtLeast(Severity::High));
        $this->assertTrue($frenchResult->severity()->isAtLeast(Severity::High));
        $this->assertTrue($germanResult->severity()->isAtLeast(Severity::High));
    }
}
