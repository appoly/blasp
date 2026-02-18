<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Dictionary;
use Blaspsoft\Blasp\Core\Normalizers\EnglishNormalizer;
use Blaspsoft\Blasp\Core\Normalizers\SpanishNormalizer;
use Blaspsoft\Blasp\Core\Normalizers\GermanNormalizer;
use Blaspsoft\Blasp\Core\Normalizers\FrenchNormalizer;

class ConfigurationLoaderLanguageTest extends TestCase
{
    public function test_get_available_languages()
    {
        $languages = Dictionary::getAvailableLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('english', $languages);
        $this->assertContains('spanish', $languages);
        $this->assertContains('french', $languages);
        $this->assertContains('german', $languages);
    }

    public function test_load_specific_language_english()
    {
        $englishConfig = Dictionary::loadLanguageConfig('english');

        $this->assertIsArray($englishConfig);
        $this->assertArrayHasKey('profanities', $englishConfig);
        $this->assertArrayHasKey('false_positives', $englishConfig);
        $this->assertIsArray($englishConfig['profanities']);
        $this->assertIsArray($englishConfig['false_positives']);
        $this->assertContains('fuck', $englishConfig['profanities']);
        $this->assertContains('shit', $englishConfig['profanities']);
        $this->assertContains('class', $englishConfig['false_positives']);
        $this->assertContains('pass', $englishConfig['false_positives']);
    }

    public function test_load_specific_language_spanish()
    {
        $spanishConfig = Dictionary::loadLanguageConfig('spanish');

        $this->assertIsArray($spanishConfig);
        $this->assertArrayHasKey('profanities', $spanishConfig);
        $this->assertArrayHasKey('false_positives', $spanishConfig);
        $this->assertArrayHasKey('substitutions', $spanishConfig);
        $this->assertContains('mierda', $spanishConfig['profanities']);
        $this->assertContains('joder', $spanishConfig['profanities']);
        $this->assertContains('cabrón', $spanishConfig['profanities']);
        $this->assertContains('clase', $spanishConfig['false_positives']);
        $this->assertContains('análisis', $spanishConfig['false_positives']);
        $this->assertArrayHasKey('/ñ/', $spanishConfig['substitutions']);
        $this->assertArrayHasKey('/á/', $spanishConfig['substitutions']);
    }

    public function test_load_specific_language_french()
    {
        $frenchConfig = Dictionary::loadLanguageConfig('french');

        $this->assertIsArray($frenchConfig);
        $this->assertArrayHasKey('profanities', $frenchConfig);
        $this->assertArrayHasKey('false_positives', $frenchConfig);
        $this->assertArrayHasKey('substitutions', $frenchConfig);
        $this->assertContains('merde', $frenchConfig['profanities']);
        $this->assertContains('putain', $frenchConfig['profanities']);
        $this->assertContains('connard', $frenchConfig['profanities']);
        $this->assertContains('classe', $frenchConfig['false_positives']);
        $this->assertContains('analyse', $frenchConfig['false_positives']);
        $this->assertArrayHasKey('/à/', $frenchConfig['substitutions']);
        $this->assertArrayHasKey('/é/', $frenchConfig['substitutions']);
        $this->assertArrayHasKey('/ç/', $frenchConfig['substitutions']);
    }

    public function test_load_specific_language_german()
    {
        $germanConfig = Dictionary::loadLanguageConfig('german');

        $this->assertIsArray($germanConfig);
        $this->assertArrayHasKey('profanities', $germanConfig);
        $this->assertArrayHasKey('false_positives', $germanConfig);
        $this->assertArrayHasKey('substitutions', $germanConfig);
        $this->assertContains('scheiße', $germanConfig['profanities']);
        $this->assertContains('ficken', $germanConfig['profanities']);
        $this->assertContains('arsch', $germanConfig['profanities']);
        $this->assertContains('klasse', $germanConfig['false_positives']);
        $this->assertContains('analyse', $germanConfig['false_positives']);
        $this->assertArrayHasKey('/ä/', $germanConfig['substitutions']);
        $this->assertArrayHasKey('/ö/', $germanConfig['substitutions']);
        $this->assertArrayHasKey('/ü/', $germanConfig['substitutions']);
        $this->assertArrayHasKey('/ß/', $germanConfig['substitutions']);
    }

    public function test_load_nonexistent_language()
    {
        $result = Dictionary::loadLanguageConfig('nonexistent');
        $this->assertEmpty($result['profanities']);
    }

    public function test_normalizer_for_languages()
    {
        $this->assertInstanceOf(EnglishNormalizer::class, Dictionary::getNormalizerForLanguage('english'));
        $this->assertInstanceOf(SpanishNormalizer::class, Dictionary::getNormalizerForLanguage('spanish'));
        $this->assertInstanceOf(GermanNormalizer::class, Dictionary::getNormalizerForLanguage('german'));
        $this->assertInstanceOf(FrenchNormalizer::class, Dictionary::getNormalizerForLanguage('french'));
    }

    public function test_language_substitutions_are_merged()
    {
        $dictionary = Dictionary::forLanguage('french');
        $substitutions = $dictionary->getSubstitutions();

        // Main config base patterns should be present
        $this->assertArrayHasKey('/a/', $substitutions);
        $this->assertArrayHasKey('/z/', $substitutions);

        // Verify detection works with merged substitutions
        $result = \Blaspsoft\Blasp\Facades\Blasp::french()->check('connard');
        $this->assertTrue($result->isOffensive());
    }
}
