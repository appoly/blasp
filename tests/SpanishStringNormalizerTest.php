<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Normalizers\SpanishNormalizer;

class SpanishStringNormalizerTest extends TestCase
{
    private SpanishNormalizer $normalizer;

    public function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new SpanishNormalizer();
    }

    public function test_normalize_accented_vowels()
    {
        $this->assertEquals('hola como estas', $this->normalizer->normalize('hóla cómo estás'));
        $this->assertEquals('feliz cumpleanos', $this->normalizer->normalize('felíz cumpleañós'));
        $this->assertEquals('nino pequeno', $this->normalizer->normalize('niño pequeño'));
    }

    public function test_normalize_enye_character()
    {
        $this->assertEquals('espanol', $this->normalizer->normalize('español'));
        $this->assertEquals('manana', $this->normalizer->normalize('mañana'));
        $this->assertEquals('NINO', $this->normalizer->normalize('NIÑO'));
    }

    public function test_normalize_double_l()
    {
        $this->assertEquals('yamo', $this->normalizer->normalize('llamo'));
        $this->assertEquals('yegar', $this->normalizer->normalize('llegar'));
        $this->assertEquals('YOVER', $this->normalizer->normalize('LLOVER'));
    }

    public function test_normalize_double_r()
    {
        $this->assertEquals('pero', $this->normalizer->normalize('perro'));
        $this->assertEquals('caro', $this->normalizer->normalize('carro'));
        $this->assertEquals('RUN', $this->normalizer->normalize('RRUN'));
    }

    public function test_normalize_spanish_profanity_variants()
    {
        $this->assertEquals('mierda', $this->normalizer->normalize('miérda'));
        $this->assertEquals('cabron', $this->normalizer->normalize('cabrón'));
        $this->assertEquals('joder', $this->normalizer->normalize('jodér'));
    }

    public function test_normalize_preserves_non_spanish_characters()
    {
        $this->assertEquals('hello world 123', $this->normalizer->normalize('hello world 123'));
        $this->assertEquals('test@email.com', $this->normalizer->normalize('test@email.com'));
    }

    public function test_normalize_mixed_case_preservation()
    {
        $this->assertEquals('MANANA', $this->normalizer->normalize('MAÑANA'));
        $this->assertEquals('Espanol', $this->normalizer->normalize('Español'));
        $this->assertEquals('hOLA', $this->normalizer->normalize('hÓLA'));
    }

    public function test_normalize_empty_and_special_strings()
    {
        $this->assertEquals('', $this->normalizer->normalize(''));
        $this->assertEquals('   ', $this->normalizer->normalize('   '));
        $this->assertEquals('nnn', $this->normalizer->normalize('ñññ'));
    }
}
