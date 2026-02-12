<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Laravel\Facade as Blasp;
use Blaspsoft\Blasp\Core\Dictionary;

class MultiLanguageProfanityTest extends TestCase
{
    public function test_english_profanities()
    {
        $testCases = [
            'fuck' => 'This fuck word',
            'shit' => 'This shit happens',
            'ass' => 'What an ass',
            'bitch' => 'Stop being a bitch',
            'damn' => 'Damn it all',
        ];

        foreach ($testCases as $profanity => $text) {
            $result = Blasp::english()->check($text);
            $this->assertTrue($result->isOffensive(), "Failed to detect: $profanity");
        }
    }

    public function test_spanish_profanities()
    {
        $testCases = [
            'mierda' => 'Esta es una mierda',
            'joder' => 'No quiero joder',
            'cabron' => 'Eres un cabron',
            'puta' => 'La puta madre',
        ];

        foreach ($testCases as $profanity => $text) {
            $result = Blasp::spanish()->check($text);
            $this->assertTrue($result->isOffensive(), "Failed to detect Spanish: $profanity");
        }
    }

    public function test_german_profanities()
    {
        $testCases = [
            'scheisse' => 'Das ist scheisse',
            'scheisse' => 'Das ist scheisse',
            'arsch' => 'Du bist ein arsch',
            'ficken' => 'Ich will ficken',
            'verdammt' => 'Verdammt noch mal',
        ];

        foreach ($testCases as $profanity => $text) {
            $result = Blasp::german()->check($text);
            $this->assertTrue($result->isOffensive(), "Failed to detect German: $profanity");
        }
    }

    public function test_french_profanities()
    {
        $testCases = [
            'merde' => "C'est de la merde",
            'putain' => 'Putain de merde',
            'connard' => 'Quel connard',
            'salope' => 'Une vraie salope',
        ];

        foreach ($testCases as $profanity => $text) {
            $result = Blasp::french()->check($text);
            $this->assertTrue($result->isOffensive(), "Failed to detect French: $profanity");
        }
    }

    public function test_profanity_variations()
    {
        $testCases = [
            'f-u-c-k' => 'obscuring with dashes',
            'ffuucckk' => 'character doubling',
            's.h.i.t' => 'obscuring with dots',
            '@ss' => 'substitution',
        ];

        foreach ($testCases as $variation => $description) {
            $result = Blasp::check("This has $variation in it");
            $this->assertTrue(
                $result->isOffensive(),
                "Failed to detect variation ($description): $variation"
            );
        }
    }

    public function test_case_insensitivity()
    {
        $testCases = [
            'english' => ['FUCK', 'FuCk', 'fUcK'],
            'spanish' => ['MIERDA', 'MiErDa', 'mIeRdA'],
            'german' => ['SCHEISSE', 'ScHeIsSe', 'schEISSE'],
            'french' => ['MERDE', 'MeRdE', 'mErDe'],
        ];

        foreach ($testCases as $language => $variations) {
            foreach ($variations as $variation) {
                $result = Blasp::in($language)->check("Word: $variation here");
                $this->assertTrue(
                    $result->isOffensive(),
                    "Failed to detect $language case variation: $variation"
                );
            }
        }
    }

    public function test_false_positives_not_flagged()
    {
        $safeFalsePositives = ['class', 'pass', 'hello'];

        foreach ($safeFalsePositives as $word) {
            $result = Blasp::check("This contains $word word");
            $this->assertFalse(
                $result->isOffensive(),
                "False positive incorrectly detected: $word"
            );
        }
    }

    public function test_comprehensive_language_coverage()
    {
        $languages = ['english', 'spanish', 'german', 'french'];

        foreach ($languages as $language) {
            $config = Dictionary::loadLanguageConfig($language);
            $profanities = $config['profanities'] ?? [];
            $totalProfanities = count($profanities);
            $detected = 0;
            $failed = [];

            foreach ($profanities as $profanity) {
                $result = Blasp::in($language)->check($profanity);
                if ($result->isOffensive()) {
                    $detected++;
                } else {
                    $failed[] = $profanity;
                }
            }

            $detectionRate = ($totalProfanities > 0) ? ($detected / $totalProfanities) * 100 : 0;

            $this->assertGreaterThanOrEqual(
                90,
                $detectionRate,
                sprintf(
                    "%s: Detection rate %.2f%% (detected %d/%d). Failed: %s",
                    ucfirst($language),
                    $detectionRate,
                    $detected,
                    $totalProfanities,
                    implode(', ', array_slice($failed, 0, 5))
                )
            );
        }
    }
}
