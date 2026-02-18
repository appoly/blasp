<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Dictionary;
use Blaspsoft\Blasp\Enums\Severity;

class MultiLanguageDetectionConfigTest extends TestCase
{
    public function test_for_language_sets_language()
    {
        $dictionary = Dictionary::forLanguage('spanish');
        $this->assertEquals('spanish', $dictionary->getLanguage());
    }

    public function test_for_languages_merges_profanities()
    {
        $dictionary = Dictionary::forLanguages(['english', 'spanish']);

        $profanities = $dictionary->getProfanities();
        $this->assertContains('fuck', $profanities);
        $this->assertContains('mierda', $profanities);
    }

    public function test_for_all_languages_includes_all()
    {
        $dictionary = Dictionary::forAllLanguages();

        $profanities = $dictionary->getProfanities();
        $this->assertContains('fuck', $profanities);
        $this->assertContains('mierda', $profanities);
        $this->assertContains('merde', $profanities);
        $this->assertContains('scheiße', $profanities);
    }

    public function test_profanity_expressions_generated()
    {
        $dictionary = Dictionary::forLanguage('english');
        $expressions = $dictionary->getProfanityExpressions();

        $this->assertIsArray($expressions);
        $this->assertNotEmpty($expressions);
        $this->assertArrayHasKey('fuck', $expressions);
    }

    public function test_severity_map_populated()
    {
        $dictionary = Dictionary::forLanguage('english');

        $severity = $dictionary->getSeverity('fuck');
        $this->assertInstanceOf(Severity::class, $severity);
    }

    public function test_false_positives_loaded()
    {
        $dictionary = Dictionary::forLanguage('english');
        $falsePositives = $dictionary->getFalsePositives();

        $this->assertIsArray($falsePositives);
        $this->assertContains('class', $falsePositives);
        $this->assertContains('pass', $falsePositives);
    }

    public function test_allow_list_removes_profanities()
    {
        $dictionary = Dictionary::forLanguage('english', ['allow' => ['fuck']]);

        $this->assertNotContains('fuck', $dictionary->getProfanities());
    }

    public function test_block_list_adds_profanities()
    {
        $dictionary = Dictionary::forLanguage('english', ['block' => ['customword']]);

        $this->assertContains('customword', $dictionary->getProfanities());
    }

    public function test_block_list_gets_severity()
    {
        $dictionary = Dictionary::forLanguage('english', ['block' => ['customword']]);

        $severity = $dictionary->getSeverity('customword');
        $this->assertEquals(Severity::High, $severity);
    }

    public function test_separators_and_substitutions_present()
    {
        $dictionary = Dictionary::forLanguage('english');

        $this->assertNotEmpty($dictionary->getSeparators());
        $this->assertNotEmpty($dictionary->getSubstitutions());
    }
}
