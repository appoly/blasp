<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Dictionary;
use Blaspsoft\Blasp\Enums\Severity;
use Blaspsoft\Blasp\Facades\Blasp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ResultCachingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('blasp.cache.enabled', true);
        $this->app['config']->set('blasp.cache.results', true);
        $this->app['config']->set('blasp.cache.driver', null);
        Cache::flush();
    }

    public function test_results_are_cached(): void
    {
        $result1 = Blasp::check('This is a fucking sentence');
        $result2 = Blasp::check('This is a fucking sentence');

        $this->assertTrue($result1->isOffensive());
        $this->assertTrue($result2->isOffensive());
        $this->assertSame($result1->clean(), $result2->clean());
        $this->assertSame($result1->score(), $result2->score());
        $this->assertSame($result1->count(), $result2->count());

        // Verify cache keys were tracked
        $keys = Cache::get('blasp_result_cache_keys', []);
        $this->assertNotEmpty($keys);
    }

    public function test_cache_key_varies_by_language(): void
    {
        $englishResult = Blasp::in('english')->check('damn');
        $spanishResult = Blasp::in('spanish')->check('damn');

        // English should detect 'damn', Spanish should not
        $this->assertTrue($englishResult->isOffensive());
        $this->assertFalse($spanishResult->isOffensive());

        // Both should be cached as separate entries
        $keys = Cache::get('blasp_result_cache_keys', []);
        $this->assertCount(2, $keys);
    }

    public function test_cache_key_varies_by_severity(): void
    {
        $result1 = Blasp::withSeverity(Severity::Mild)->check('damn this');
        $result2 = Blasp::withSeverity(Severity::Extreme)->check('damn this');

        // Mild severity catches 'damn', Extreme does not
        $this->assertTrue($result1->isOffensive());
        $this->assertFalse($result2->isOffensive());
    }

    public function test_cache_key_varies_by_allow_list(): void
    {
        $result1 = Blasp::check('damn this');
        $result2 = Blasp::allow('damn')->check('damn this');

        $this->assertTrue($result1->isOffensive());
        $this->assertFalse($result2->isOffensive());
    }

    public function test_cache_key_varies_by_block_list(): void
    {
        $result1 = Blasp::check('foobar');
        $result2 = Blasp::block('foobar')->check('foobar');

        $this->assertFalse($result1->isOffensive());
        $this->assertTrue($result2->isOffensive());
    }

    public function test_cache_key_varies_by_driver(): void
    {
        $result1 = Blasp::driver('regex')->check('fuck this');
        $result2 = Blasp::driver('pattern')->check('fuck this');

        // Both should detect it, but they should be separate cache entries
        $this->assertTrue($result1->isOffensive());
        $this->assertTrue($result2->isOffensive());

        $keys = Cache::get('blasp_result_cache_keys', []);
        $this->assertCount(2, $keys);
    }

    public function test_callback_mask_bypasses_cache(): void
    {
        Blasp::mask(fn($word, $len) => '[CENSORED]')->check('fuck this');

        // No cache keys should be tracked for CallbackMask
        $keys = Cache::get('blasp_result_cache_keys', []);
        $this->assertEmpty($keys);
    }

    public function test_clear_cache_wipes_result_cache(): void
    {
        Blasp::check('This is a fucking sentence');

        $keys = Cache::get('blasp_result_cache_keys', []);
        $this->assertNotEmpty($keys);

        Dictionary::clearCache();

        $keys = Cache::get('blasp_result_cache_keys', []);
        $this->assertNull(Cache::get('blasp_result_cache_keys'));

        // Verify the cached result data was also cleared
        foreach ($keys as $key) {
            $this->assertNull(Cache::get($key));
        }
    }

    public function test_disabling_results_config_skips_caching(): void
    {
        Config::set('blasp.cache.results', false);

        Blasp::check('This is a fucking sentence');

        $keys = Cache::get('blasp_result_cache_keys', []);
        $this->assertEmpty($keys);
    }

    public function test_disabling_cache_entirely_skips_caching(): void
    {
        Config::set('blasp.cache.enabled', false);

        Blasp::check('This is a fucking sentence');

        $keys = Cache::get('blasp_result_cache_keys', []);
        $this->assertEmpty($keys);
    }

    public function test_cached_results_deserialize_correctly(): void
    {
        $result1 = Blasp::check('This is a fucking sentence');

        // Clear PHP state but keep cache
        // Second call should come from cache
        $result2 = Blasp::check('This is a fucking sentence');

        $this->assertSame($result1->isOffensive(), $result2->isOffensive());
        $this->assertSame($result1->clean(), $result2->clean());
        $this->assertSame($result1->original(), $result2->original());
        $this->assertSame($result1->score(), $result2->score());
        $this->assertSame($result1->count(), $result2->count());
        $this->assertSame($result1->uniqueWords(), $result2->uniqueWords());
    }

    public function test_clean_text_is_not_cached_incorrectly(): void
    {
        $result = Blasp::check('hello world');
        $this->assertFalse($result->isOffensive());
        $this->assertSame('hello world', $result->clean());

        // Second call
        $result2 = Blasp::check('hello world');
        $this->assertFalse($result2->isOffensive());
        $this->assertSame('hello world', $result2->clean());
    }
}
