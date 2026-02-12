<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Normalizers\GermanNormalizer;

class GermanStringNormalizerTest extends TestCase
{
    private GermanNormalizer $normalizer;

    public function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new GermanNormalizer();
    }

    public function test_normalize_umlauts()
    {
        $this->assertEquals('maedchen', $this->normalizer->normalize('mädchen'));
        $this->assertEquals('shoene', $this->normalizer->normalize('schöne'));
        $this->assertEquals('gruessen', $this->normalizer->normalize('grüssen'));
        $this->assertEquals('MAEDCHEN', $this->normalizer->normalize('MÄDCHEN'));
        $this->assertEquals('SHOENE', $this->normalizer->normalize('SCHÖNE'));
        $this->assertEquals('GRUESSEN', $this->normalizer->normalize('GRÜSSEN'));
    }

    public function test_normalize_eszett()
    {
        $this->assertEquals('weiss', $this->normalizer->normalize('weiß'));
        $this->assertEquals('strasse', $this->normalizer->normalize('straße'));
        $this->assertEquals('gross', $this->normalizer->normalize('groß'));
        $this->assertEquals('heiss', $this->normalizer->normalize('heiß'));
    }

    public function test_normalize_sch_combinations()
    {
        $this->assertEquals('shule', $this->normalizer->normalize('schule'));
        $this->assertEquals('mensh', $this->normalizer->normalize('mensch'));
        $this->assertEquals('SHULE', $this->normalizer->normalize('SCHULE'));
    }

    public function test_normalize_german_profanity_variants()
    {
        $this->assertEquals('sheisse', $this->normalizer->normalize('scheiße'));
        $this->assertEquals('arsh', $this->normalizer->normalize('arsch'));
        $this->assertEquals('ficken', $this->normalizer->normalize('ficken'));
        $this->assertEquals('maedchen', $this->normalizer->normalize('mädchen'));
    }

    public function test_normalize_preserves_non_german_characters()
    {
        $this->assertEquals('hello world 123', $this->normalizer->normalize('hello world 123'));
        $this->assertEquals('test@email.com', $this->normalizer->normalize('test@email.com'));
    }

    public function test_normalize_mixed_case_preservation()
    {
        $this->assertEquals('MAEDCHEN', $this->normalizer->normalize('MÄDCHEN'));
        $this->assertEquals('Shoene', $this->normalizer->normalize('Schöne'));
        $this->assertEquals('gRUEssen', $this->normalizer->normalize('gRÜßen'));
    }

    public function test_normalize_empty_and_special_strings()
    {
        $this->assertEquals('', $this->normalizer->normalize(''));
        $this->assertEquals('   ', $this->normalizer->normalize('   '));
        $this->assertEquals('aeaeaeaeaeae', $this->normalizer->normalize('ääääää'));
    }
}
