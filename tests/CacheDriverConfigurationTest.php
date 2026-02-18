<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Dictionary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class CacheDriverConfigurationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('cache.default', 'array');
        Cache::flush();
    }

    public function test_dictionary_can_be_created_without_cache(): void
    {
        Config::set('blasp.cache.driver', null);

        $dictionary = Dictionary::forLanguage('english');

        $this->assertNotNull($dictionary);
        $this->assertNotEmpty($dictionary->getProfanities());
    }

    public function test_clear_cache_works(): void
    {
        Dictionary::clearCache();
        $this->assertFalse(Cache::has('blasp_cache_keys'));
    }

    public function test_dictionary_loads_consistently(): void
    {
        $dict1 = Dictionary::forLanguage('english');
        $dict2 = Dictionary::forLanguage('english');

        $this->assertEquals($dict1->getProfanities(), $dict2->getProfanities());
        $this->assertEquals($dict1->getFalsePositives(), $dict2->getFalsePositives());
    }

    public function test_different_languages_have_different_profanities(): void
    {
        $english = Dictionary::forLanguage('english');
        $spanish = Dictionary::forLanguage('spanish');

        $this->assertNotEquals($english->getProfanities(), $spanish->getProfanities());
    }

    public function test_clear_cache_with_custom_driver(): void
    {
        Config::set('blasp.cache.driver', 'array');

        Dictionary::clearCache();

        $keys = Cache::store('array')->get('blasp_cache_keys', []);
        $this->assertEmpty($keys);
    }
}
