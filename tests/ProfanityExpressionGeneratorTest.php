<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Matchers\RegexMatcher;

class ProfanityExpressionGeneratorTest extends TestCase
{
    private RegexMatcher $matcher;

    public function setUp(): void
    {
        parent::setUp();
        $this->matcher = new RegexMatcher();
    }

    public function test_generate_separator_expression()
    {
        $separators = ['-', '_', '.', ' '];
        $result = $this->matcher->generateSeparatorExpression($separators);

        $this->assertIsString($result);
    }

    public function test_generate_substitution_expressions()
    {
        $substitutions = [
            '/a/' => ['a', '@', '4'],
            '/e/' => ['e', '3'],
            '/o/' => ['o', '0']
        ];

        $result = $this->matcher->generateSubstitutionExpressions($substitutions);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('/a/', $result);
        $this->assertArrayHasKey('/e/', $result);
        $this->assertArrayHasKey('/o/', $result);
    }

    public function test_generate_profanity_expression_simple()
    {
        $profanity = 'test';
        $substitutionExpressions = [
            '/t/' => '[t\+]+{!!}',
            '/e/' => '[e3]+{!!}',
            '/s/' => '[s$]+{!!}'
        ];
        $separatorExpression = '[\-\s]*?';

        $result = $this->matcher->generateProfanityExpression(
            $profanity,
            $substitutionExpressions,
            $separatorExpression
        );

        $this->assertIsString($result);
        $this->assertStringStartsWith('/', $result);
        $this->assertStringEndsWith('/iu', $result);
    }

    public function test_generate_expressions_full_flow()
    {
        $profanities = ['fuck', 'shit'];
        $separators = ['-', '_', '.'];
        $substitutions = [
            '/f/' => ['f', 'ƒ'],
            '/u/' => ['u', 'υ', 'µ'],
            '/c/' => ['c', 'ç', '¢'],
            '/s/' => ['s', '5', '$'],
            '/h/' => ['h'],
            '/i/' => ['i', '!', '|'],
            '/t/' => ['t']
        ];

        $result = $this->matcher->generateExpressions($profanities, $separators, $substitutions);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fuck', $result);
        $this->assertArrayHasKey('shit', $result);

        foreach ($result as $profanity => $expression) {
            $this->assertIsString($expression);
            $this->assertStringStartsWith('/', $expression);
            $this->assertStringEndsWith('/iu', $expression);

            $testResult = @preg_match($expression, $profanity);
            $this->assertNotFalse($testResult, "Invalid regex generated for '$profanity': $expression");
        }
    }

    public function test_generated_expressions_match_profanities()
    {
        $profanities = ['fuck'];
        $separators = ['-', '_'];
        $substitutions = [
            '/f/' => ['f', 'ƒ'],
            '/u/' => ['u', 'υ', 'µ'],
            '/c/' => ['c', 'ç', '¢'],
            '/k/' => ['k']
        ];

        $expressions = $this->matcher->generateExpressions($profanities, $separators, $substitutions);
        $expression = $expressions['fuck'];

        $this->assertEquals(1, preg_match($expression, 'fuck'));
        $this->assertEquals(1, preg_match($expression, 'FUCK'));
        $this->assertEquals(1, preg_match($expression, 'ƒuck'));
        $this->assertEquals(1, preg_match($expression, 'fuçk'));
        $this->assertEquals(1, preg_match($expression, 'f-u-c-k'));
        $this->assertEquals(1, preg_match($expression, 'f_u_c_k'));
        $this->assertEquals(0, preg_match($expression, 'hello'));
        $this->assertEquals(0, preg_match($expression, 'world'));
    }

    public function test_separator_expression_with_various_chars()
    {
        $separators = ['-', '_', '.', ' ', '*', '!'];
        $result = $this->matcher->generateSeparatorExpression($separators);

        $this->assertIsString($result);

        $testExpression = '/f' . $result . 'u' . $result . 'c' . $result . 'k/i';

        $this->assertEquals(1, preg_match($testExpression, 'f-u-c-k'));
        $this->assertEquals(1, preg_match($testExpression, 'f_u_c_k'));
        $this->assertEquals(1, preg_match($testExpression, 'f u c k'));
        $this->assertEquals(1, preg_match($testExpression, 'f*u*c*k'));
        $this->assertEquals(1, preg_match($testExpression, 'f!u!c!k'));
        $this->assertEquals(1, preg_match($testExpression, 'fuck'));
    }

    public function test_generate_expressions_with_multi_char_substitutions()
    {
        $profanities = ['ass'];
        $separators = ['-'];
        $substitutions = [
            '/a/' => ['a', '@', '4'],
            '/s/' => ['s', '$', '5']
        ];

        $expressions = $this->matcher->generateExpressions($profanities, $separators, $substitutions);
        $expression = $expressions['ass'];

        $this->assertEquals(1, preg_match($expression, 'ass'));
        $this->assertEquals(1, preg_match($expression, '@ss'));
        $this->assertEquals(1, preg_match($expression, '4ss'));
        $this->assertEquals(1, preg_match($expression, 'a$s'));
        $this->assertEquals(1, preg_match($expression, 'a55'));
        $this->assertEquals(1, preg_match($expression, '@$$'));
        $this->assertEquals(1, preg_match($expression, '455'));
    }

    public function test_expressions_are_case_insensitive()
    {
        $profanities = ['test'];
        $separators = [];
        $substitutions = [
            '/t/' => ['t'],
            '/e/' => ['e', '3'],
            '/s/' => ['s', '$']
        ];

        $expressions = $this->matcher->generateExpressions($profanities, $separators, $substitutions);
        $expression = $expressions['test'];

        $this->assertEquals(1, preg_match($expression, 'test'));
        $this->assertEquals(1, preg_match($expression, 'TEST'));
        $this->assertEquals(1, preg_match($expression, 'Test'));
        $this->assertEquals(1, preg_match($expression, 'TeSt'));
        $this->assertEquals(1, preg_match($expression, 't3st'));
        $this->assertEquals(1, preg_match($expression, 'T3ST'));
        $this->assertEquals(1, preg_match($expression, 'te$t'));
    }

    public function test_empty_arrays_handling()
    {
        $result = $this->matcher->generateExpressions([], [], []);
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        $separatorResult = $this->matcher->generateSeparatorExpression([]);
        $this->assertIsString($separatorResult);

        $substitutionResult = $this->matcher->generateSubstitutionExpressions([]);
        $this->assertIsArray($substitutionResult);
        $this->assertEmpty($substitutionResult);
    }

    public function test_complex_profanity_patterns()
    {
        $profanities = ['fucking', 'bullshit'];
        $separators = ['-', '_', ' ', '.'];
        $substitutions = [
            '/f/' => ['f'],
            '/u/' => ['u', 'ü', 'ū'],
            '/c/' => ['c', 'ç'],
            '/k/' => ['k'],
            '/i/' => ['i', '!', '1'],
            '/n/' => ['n', 'ñ'],
            '/g/' => ['g'],
            '/b/' => ['b', 'ß'],
            '/l/' => ['l'],
            '/s/' => ['s', '$'],
            '/h/' => ['h'],
            '/t/' => ['t']
        ];

        $expressions = $this->matcher->generateExpressions($profanities, $separators, $substitutions);

        $fuckingExpression = $expressions['fucking'];
        $this->assertEquals(1, preg_match($fuckingExpression, 'fucking'));
        $this->assertEquals(1, preg_match($fuckingExpression, 'füçk1ng'));
        $this->assertEquals(1, preg_match($fuckingExpression, 'f-u-c-k-i-n-g'));

        $bullshitExpression = $expressions['bullshit'];
        $this->assertEquals(1, preg_match($bullshitExpression, 'bullshit'));
        $this->assertEquals(1, preg_match($bullshitExpression, 'ßull$h1t'));
        $this->assertEquals(1, preg_match($bullshitExpression, 'b.u.l.l.s.h.i.t'));
    }

    public function test_circular_substitutions_produce_valid_regex()
    {
        $substitutions = [
            '/c/' => ['c', 'k', 'ç'],
            '/k/' => ['k', 'c', 'q'],
        ];
        $subExpressions = $this->matcher->generateSubstitutionExpressions($substitutions);
        $separatorExpr = $this->matcher->generateSeparatorExpression([]);
        $regex = $this->matcher->generateProfanityExpression('cock', $subExpressions, $separatorExpr);

        $this->assertNotFalse(@preg_match($regex, ''));
        $this->assertMatchesRegularExpression($regex, 'cock');
        $this->assertMatchesRegularExpression($regex, 'kokk');
        $this->assertMatchesRegularExpression($regex, 'çoçk');
    }

    public function test_basic_profanity_matching()
    {
        $profanities = ['damn', 'hell'];
        $separators = ['-', '_'];
        $substitutions = [
            '/a/' => ['a', '@'],
            '/e/' => ['e', '3'],
            '/l/' => ['l', '1']
        ];

        $expressions = $this->matcher->generateExpressions($profanities, $separators, $substitutions);
        $damnExpression = $expressions['damn'];
        $hellExpression = $expressions['hell'];

        $this->assertEquals(1, preg_match($damnExpression, 'damn'));
        $this->assertEquals(1, preg_match($damnExpression, 'd@mn'));
        $this->assertEquals(1, preg_match($hellExpression, 'hell'));
        $this->assertEquals(1, preg_match($hellExpression, 'h3ll'));
        $this->assertEquals(1, preg_match($hellExpression, 'he11'));

        $this->assertEquals(0, preg_match($damnExpression, 'hello'));
        $this->assertEquals(0, preg_match($hellExpression, 'damn'));
    }
}
