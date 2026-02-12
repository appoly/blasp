<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Dictionary;
use Illuminate\Support\Facades\Cache;

class ConfigurationLoaderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('cache.default', 'array');
        Cache::flush();
    }

    public function test_for_language_returns_dictionary()
    {
        $dictionary = Dictionary::forLanguage('english');

        $this->assertInstanceOf(Dictionary::class, $dictionary);
        $this->assertIsArray($dictionary->getProfanities());
        $this->assertIsArray($dictionary->getFalsePositives());
    }

    public function test_dictionary_has_profanity_expressions()
    {
        $dictionary = Dictionary::forLanguage('english');
        $expressions = $dictionary->getProfanityExpressions();

        $this->assertIsArray($expressions);
        $this->assertNotEmpty($expressions);
        $this->assertArrayHasKey('fuck', $expressions);
        $this->assertArrayHasKey('shit', $expressions);
    }

    public function test_for_languages_returns_multi_language_dictionary()
    {
        $dictionary = Dictionary::forLanguages(['english', 'spanish']);

        $profanities = $dictionary->getProfanities();
        $this->assertContains('fuck', $profanities);
        $this->assertContains('mierda', $profanities);
    }

    public function test_for_all_languages_returns_all_language_dictionary()
    {
        $dictionary = Dictionary::forAllLanguages();

        $profanities = $dictionary->getProfanities();
        $this->assertContains('fuck', $profanities);
        $this->assertContains('mierda', $profanities);
        $this->assertContains('merde', $profanities);
        $this->assertContains('scheiße', $profanities);
    }

    public function test_allow_list_removes_words()
    {
        $dictionary = Dictionary::forLanguage('english', ['allow' => ['fuck']]);

        $this->assertNotContains('fuck', $dictionary->getProfanities());
        $this->assertContains('shit', $dictionary->getProfanities());
    }

    public function test_block_list_adds_words()
    {
        $dictionary = Dictionary::forLanguage('english', ['block' => ['customword']]);

        $this->assertContains('customword', $dictionary->getProfanities());
    }

    public function test_severity_map_is_populated()
    {
        $dictionary = Dictionary::forLanguage('english');

        $severity = $dictionary->getSeverity('fuck');
        $this->assertNotNull($severity);
    }

    public function test_clear_cache()
    {
        Dictionary::clearCache();
        $this->assertFalse(Cache::has('blasp_cache_keys'));
    }

    public function test_get_available_languages()
    {
        $languages = Dictionary::getAvailableLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('english', $languages);
        $this->assertContains('spanish', $languages);
        $this->assertContains('french', $languages);
        $this->assertContains('german', $languages);
    }

    public function test_load_language_config()
    {
        $config = Dictionary::loadLanguageConfig('english');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('profanities', $config);
        $this->assertContains('fuck', $config['profanities']);
    }

    public function test_load_nonexistent_language_config()
    {
        $config = Dictionary::loadLanguageConfig('nonexistent');

        $this->assertIsArray($config);
        $this->assertEmpty($config['profanities']);
    }

    public function test_normalizer_is_set()
    {
        $dictionary = Dictionary::forLanguage('english');

        $this->assertNotNull($dictionary->getNormalizer());
    }

    public function test_separators_and_substitutions_loaded()
    {
        $dictionary = Dictionary::forLanguage('english');

        $this->assertNotEmpty($dictionary->getSeparators());
        $this->assertNotEmpty($dictionary->getSubstitutions());
    }
}
