<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Drivers\PipelineDriver;
use Blaspsoft\Blasp\Enums\Severity;
use Blaspsoft\Blasp\Facades\Blasp;

class PipelineDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('blasp.drivers.pipeline', [
            'drivers' => ['regex', 'phonetic'],
        ]);

        $this->app['config']->set('blasp.drivers.phonetic', [
            'phonemes' => 4,
            'min_word_length' => 3,
            'max_distance_ratio' => 0.6,
            'supported_languages' => ['english'],
            'false_positives' => [
                'fork', 'forked', 'forking',
                'beach', 'beaches',
                'witch', 'witches',
                'sheet', 'sheets',
                'deck', 'decks',
                'count', 'counts', 'counter', 'county',
                'ship', 'shipped', 'shipping',
                'duck', 'ducked', 'ducking',
                'fudge', 'fudging',
                'buck', 'bucks',
                'puck', 'pucks',
                'bass',
                'mass',
                'pass', 'passed',
                'heck',
                'shoot', 'shot',
                'what', 'white', 'while', 'whole',
            ],
        ]);
    }

    // -------------------------------------------------------
    // Resolution
    // -------------------------------------------------------

    public function test_resolves_from_manager_via_driver_name()
    {
        $result = Blasp::driver('pipeline')->check('hello world');

        $this->assertTrue($result->isClean());
    }

    public function test_ad_hoc_pipeline_via_facade()
    {
        $result = Blasp::pipeline('regex', 'phonetic')->check('hello world');

        $this->assertTrue($result->isClean());
    }

    // -------------------------------------------------------
    // Union merge — catches matches from different drivers
    // -------------------------------------------------------

    public function test_catches_obfuscated_text_via_regex()
    {
        $result = Blasp::driver('pipeline')->check('This is f-u-c-k-i-n-g awful');

        $this->assertTrue($result->isOffensive());
        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    public function test_catches_phonetic_evasion()
    {
        $result = Blasp::pipeline('regex', 'phonetic')->check('This is phucking awful');

        $this->assertTrue($result->isOffensive());
    }

    public function test_catches_exact_match_via_pattern()
    {
        $result = Blasp::pipeline('pattern', 'phonetic')->check('What the fuck');

        $this->assertTrue($result->isOffensive());
    }

    // -------------------------------------------------------
    // Deduplication
    // -------------------------------------------------------

    public function test_same_word_at_same_position_only_counted_once()
    {
        // "fuck" will be detected by both regex and phonetic at the same position
        $result = Blasp::pipeline('regex', 'phonetic')->check('What the fuck');

        $this->assertTrue($result->isOffensive());
        // Both drivers detect "fuck" at the same position — should be deduplicated to 1
        $this->assertSame(1, $result->count());
    }

    // -------------------------------------------------------
    // Clean text
    // -------------------------------------------------------

    public function test_clean_text_masks_applied_correctly()
    {
        $result = Blasp::driver('pipeline')->check('What the fuck');

        $this->assertTrue($result->isOffensive());
        $this->assertStringNotContainsString('fuck', $result->clean());
        $this->assertStringContainsString('What the', $result->clean());
    }

    public function test_clean_text_with_multiple_matches()
    {
        $result = Blasp::driver('pipeline')->check('fuck this shit');

        $this->assertTrue($result->isOffensive());
        $this->assertStringNotContainsString('fuck', $result->clean());
        $this->assertStringNotContainsString('shit', $result->clean());
    }

    // -------------------------------------------------------
    // Score
    // -------------------------------------------------------

    public function test_score_recalculated_from_merged_matches()
    {
        $result = Blasp::driver('pipeline')->check('fuck this shit');

        $this->assertTrue($result->isOffensive());
        $this->assertGreaterThan(0, $result->score());
    }

    // -------------------------------------------------------
    // Empty text
    // -------------------------------------------------------

    public function test_empty_text_returns_clean_result()
    {
        $result = Blasp::driver('pipeline')->check('');

        $this->assertTrue($result->isClean());
        $this->assertSame('', $result->clean());
        $this->assertSame(0, $result->count());
        $this->assertSame(0, $result->score());
    }

    // -------------------------------------------------------
    // Single-driver pipeline
    // -------------------------------------------------------

    public function test_single_driver_pipeline_matches_standalone()
    {
        $standalone = Blasp::driver('regex')->check('This is a fucking sentence');
        $pipeline = Blasp::pipeline('regex')->check('This is a fucking sentence');

        $this->assertSame($standalone->isOffensive(), $pipeline->isOffensive());
        $this->assertSame($standalone->count(), $pipeline->count());
        $this->assertSame($standalone->clean(), $pipeline->clean());
    }

    // -------------------------------------------------------
    // Severity filter
    // -------------------------------------------------------

    public function test_severity_filter_applies_across_merged_result()
    {
        $result = Blasp::driver('pipeline')
            ->withSeverity(Severity::Extreme)
            ->check('What the fuck');

        // "fuck" is High severity, not Extreme — should be filtered out by sub-drivers
        $this->assertTrue($result->isClean());
    }

    // -------------------------------------------------------
    // Language selection
    // -------------------------------------------------------

    public function test_works_with_language_selection()
    {
        $result = Blasp::pipeline('regex', 'phonetic')
            ->in('english')
            ->check('What the fuck');

        $this->assertTrue($result->isOffensive());
    }

    // -------------------------------------------------------
    // Mask strategies
    // -------------------------------------------------------

    public function test_works_with_custom_mask_character()
    {
        $result = Blasp::pipeline('regex', 'phonetic')
            ->mask('#')
            ->check('What the fuck');

        $this->assertTrue($result->isOffensive());
        $this->assertStringContainsString('####', $result->clean());
        $this->assertStringNotContainsString('fuck', $result->clean());
    }

    // -------------------------------------------------------
    // Allow / block lists
    // -------------------------------------------------------

    public function test_works_with_allow_list()
    {
        // Use regex-only pipeline to avoid phonetic matching variants
        $result = Blasp::pipeline('regex')
            ->allow('fuck')
            ->check('What the fuck');

        $this->assertTrue($result->isClean());
    }

    public function test_works_with_block_list()
    {
        $result = Blasp::pipeline('regex', 'phonetic')
            ->block('banana')
            ->check('I like banana');

        $this->assertTrue($result->isOffensive());
    }

    // -------------------------------------------------------
    // Original text preserved
    // -------------------------------------------------------

    public function test_original_text_preserved()
    {
        $text = 'What the fuck';
        $result = Blasp::driver('pipeline')->check($text);

        $this->assertSame($text, $result->original());
    }

    // -------------------------------------------------------
    // Pipeline with pattern + phonetic
    // -------------------------------------------------------

    public function test_pipeline_with_pattern_and_phonetic()
    {
        $result = Blasp::pipeline('pattern', 'phonetic')
            ->in('english')
            ->mask('#')
            ->check('fuck this phucking thing');

        $this->assertTrue($result->isOffensive());
        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    // -------------------------------------------------------
    // Clean text on clean input
    // -------------------------------------------------------

    public function test_clean_input_returns_unchanged()
    {
        $text = 'Hello world this is fine';
        $result = Blasp::driver('pipeline')->check($text);

        $this->assertTrue($result->isClean());
        $this->assertSame($text, $result->clean());
    }
}
