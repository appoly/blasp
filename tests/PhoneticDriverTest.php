<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Matchers\PhoneticMatcher;
use Blaspsoft\Blasp\Enums\Severity;
use Blaspsoft\Blasp\Facades\Blasp;

class PhoneticDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure phonetic driver config is set
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
    // PhoneticMatcher unit tests
    // -------------------------------------------------------

    public function test_matcher_exact_profanity_match()
    {
        $matcher = new PhoneticMatcher(['fuck', 'shit', 'ass']);

        $this->assertSame('fuck', $matcher->match('fuck'));
        $this->assertSame('shit', $matcher->match('shit'));
    }

    public function test_matcher_phonetic_variant_detection()
    {
        $matcher = new PhoneticMatcher(['fuck', 'shit']);

        $this->assertSame('fuck', $matcher->match('phuck'));
        $this->assertSame('fuck', $matcher->match('fuk'));
        $this->assertSame('shit', $matcher->match('sheit'));
    }

    public function test_matcher_short_word_skipping()
    {
        $matcher = new PhoneticMatcher(['fuck', 'shit'], minWordLength: 3);

        $this->assertNull($matcher->match('fu'));
        $this->assertNull($matcher->match('sh'));
    }

    public function test_matcher_phonetic_false_positive_respected()
    {
        $matcher = new PhoneticMatcher(
            ['fuck'],
            phoneticFalsePositives: ['fork'],
        );

        $this->assertNull($matcher->match('fork'));
    }

    public function test_matcher_high_levenshtein_distance_rejection()
    {
        $matcher = new PhoneticMatcher(
            ['fuck'],
            maxDistanceRatio: 0.3,
        );

        // "phucking" has high edit distance from "fuck" with strict ratio
        $this->assertNull($matcher->match('phucking'));
    }

    // -------------------------------------------------------
    // PhoneticDriver integration tests
    // -------------------------------------------------------

    public function test_resolves_from_manager()
    {
        $result = Blasp::driver('phonetic')->check('hello world');

        $this->assertTrue($result->isClean());
    }

    public function test_detects_standard_profanity()
    {
        $result = Blasp::driver('phonetic')->check('What the fuck');

        $this->assertTrue($result->isOffensive());
        $this->assertSame(1, $result->count());
    }

    public function test_detects_phonetic_evasion()
    {
        $result = Blasp::driver('phonetic')->check('This is phucking awful');

        $this->assertTrue($result->isOffensive());
        // Base word may be "fucking", "phuking", etc. depending on dictionary
        $matched = false;
        foreach ($result->uniqueWords() as $word) {
            if (str_contains($word, 'fuck') || str_contains($word, 'phuk')) {
                $matched = true;
                break;
            }
        }
        $this->assertTrue($matched, 'Expected a fuck/phuk variant in uniqueWords: ' . implode(', ', $result->uniqueWords()));
    }

    public function test_returns_correct_clean_text_with_masking()
    {
        $result = Blasp::driver('phonetic')->check('What the fuck');

        $this->assertTrue($result->isOffensive());
        $this->assertSame('What the ****', $result->clean());
    }

    public function test_handles_empty_text()
    {
        $result = Blasp::driver('phonetic')->check('');

        $this->assertTrue($result->isClean());
        $this->assertSame('', $result->clean());
        $this->assertSame(0, $result->count());
    }

    public function test_respects_severity_filter()
    {
        $result = Blasp::driver('phonetic')
            ->withSeverity(Severity::Extreme)
            ->check('What the fuck');

        // "fuck" is typically High severity, not Extreme, so should be filtered out
        $this->assertTrue($result->isClean());
    }

    public function test_respects_dictionary_false_positives()
    {
        $result = Blasp::driver('phonetic')->check('I live in scunthorpe');

        $this->assertTrue($result->isClean());
    }

    public function test_multiple_profanities_in_one_text()
    {
        $result = Blasp::driver('phonetic')->check('fuck this shit');

        $this->assertTrue($result->isOffensive());
        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    public function test_unsupported_language_returns_clean_result()
    {
        $result = Blasp::driver('phonetic')
            ->in('spanish')
            ->check('mierda');

        $this->assertTrue($result->isClean());
    }

    // -------------------------------------------------------
    // False positive regression tests
    // -------------------------------------------------------

    public function test_fork_is_not_flagged()
    {
        $result = Blasp::driver('phonetic')->check('Use a fork to eat');

        $this->assertTrue($result->isClean());
    }

    public function test_beach_is_not_flagged()
    {
        $result = Blasp::driver('phonetic')->check('Let us go to the beach');

        $this->assertTrue($result->isClean());
    }

    public function test_sheet_is_not_flagged()
    {
        $result = Blasp::driver('phonetic')->check('Print the sheet');

        $this->assertTrue($result->isClean());
    }

    public function test_duck_is_not_flagged()
    {
        $result = Blasp::driver('phonetic')->check('Look at that duck');

        $this->assertTrue($result->isClean());
    }

    public function test_count_is_not_flagged()
    {
        $result = Blasp::driver('phonetic')->check('Count the items');

        $this->assertTrue($result->isClean());
    }
}
