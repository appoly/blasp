<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Laravel\Facade as Blasp;

class BlaspCheckTest extends TestCase
{
    public function test_real_blasp_service()
    {
        $result = Blasp::check('This is a fuck!ng sentence');
        $this->assertTrue($result->isOffensive());
    }

    public function test_straight_match()
    {
        $result = Blasp::check('This is a fucking sentence');

        $this->assertTrue($result->isOffensive());
        $this->assertSame(1, $result->count());
        $this->assertCount(1, $result->uniqueWords());
        $this->assertSame('This is a ******* sentence', $result->clean());
    }

    public function test_substitution_match()
    {
        $result = Blasp::check('This is a fÛck!ng sentence');

        $this->assertTrue($result->isOffensive());
        $this->assertSame(1, $result->count());
        $this->assertCount(1, $result->uniqueWords());
        $this->assertSame('This is a ******* sentence', $result->clean());
    }

    public function test_obscured_match()
    {
        $result = Blasp::check('This is a f-u-c-k-i-n-g sentence');

        $this->assertTrue($result->isOffensive());
        $this->assertSame(1, $result->count());
        $this->assertCount(1, $result->uniqueWords());
        $this->assertSame('This is a ************* sentence', $result->clean());
    }

    public function test_doubled_match()
    {
        $result = Blasp::check('This is a ffuucckkiinngg sentence');

        $this->assertTrue($result->isOffensive());
        $this->assertSame(1, $result->count());
        $this->assertCount(1, $result->uniqueWords());
        $this->assertSame('This is a ************** sentence', $result->clean());
    }

    public function test_combination_match()
    {
        $result = Blasp::check('This is a f-uuck!ng sentence');

        $this->assertTrue($result->isOffensive());
        $this->assertSame(1, $result->count());
        $this->assertCount(1, $result->uniqueWords());
        $this->assertSame('This is a ********* sentence', $result->clean());
    }

    public function test_multiple_profanities_no_spaces()
    {
        $result = Blasp::check('cuntfuck shit');

        $this->assertTrue($result->isOffensive());
        $this->assertSame(3, $result->count());
        $this->assertCount(3, $result->uniqueWords());
        $this->assertSame('******** ****', $result->clean());
    }

    public function test_multiple_profanities()
    {
        $result = Blasp::check('This is a fuuckking sentence you fucking cunt!');
        $this->assertTrue($result->isOffensive());
        $this->assertSame(3, $result->count());
        $this->assertCount(2, $result->uniqueWords());
        $this->assertSame('This is a ********* sentence you ******* ****!', $result->clean());
    }

    public function test_scunthorpe_problem()
    {
        $result = Blasp::check('I live in a town called Scunthorpe');

        $this->assertFalse($result->isOffensive());
        $this->assertSame(0, $result->count());
        $this->assertCount(0, $result->uniqueWords());
        $this->assertSame('I live in a town called Scunthorpe', $result->clean());
    }

    public function test_penistone_problem()
    {
        $result = Blasp::check('I live in a town called Penistone');

        $this->assertFalse($result->isOffensive());
        $this->assertSame(0, $result->count());
        $this->assertCount(0, $result->uniqueWords());
        $this->assertSame('I live in a town called Penistone', $result->clean());
    }

    public function test_false_positives()
    {
        $words = [
            'Blackcocktail', 'Scunthorpe', 'Cockburn', 'Penistone', 'Lightwater',
            'Assume', 'Bass', 'Class', 'Compass', 'Pass',
            'Dickinson', 'Middlesex', 'Cockerel', 'Butterscotch', 'Blackcock',
            'Countryside', 'Arsenal', 'Flick', 'Flicker', 'Analyst',
        ];

        foreach ($words as $word) {
            $result = Blasp::check($word);
            $this->assertFalse($result->isOffensive(), "False positive detected for: $word");
            $this->assertSame(0, $result->count());
            $this->assertCount(0, $result->uniqueWords());
            $this->assertSame($word, $result->clean());
        }
    }

    public function test_cuntfuck_fuckcunt()
    {
        $result = Blasp::check('cuntfuck fuckcunt');
        $this->assertTrue($result->isOffensive());
        $this->assertSame(4, $result->count());
        $this->assertCount(2, $result->uniqueWords());
        $this->assertSame('******** ********', $result->clean());
    }

    public function test_fucking_shit_cunt_fuck()
    {
        $result = Blasp::check('fuckingshitcuntfuck');
        $this->assertTrue($result->isOffensive());
        $this->assertSame(3, $result->count());
        $this->assertCount(3, $result->uniqueWords());
        $this->assertSame('*******************', $result->clean());
    }

    public function test_billy_butcher()
    {
        $result = Blasp::check('oi! cunt!');
        $this->assertTrue($result->isOffensive());
        $this->assertSame(1, $result->count());
        $this->assertCount(1, $result->uniqueWords());
        $this->assertSame('oi! ****!', $result->clean());
    }

    public function test_paragraph()
    {
        $paragraph = "This damn project is such a pain in the ass. I can't believe I have to deal with this bullshit every single day. It's like everything is completely fucked up, and nobody gives a shit. Sometimes I just want to scream, 'What the hell is going on?' Honestly, it's a total clusterfuck, and I'm so fucking done with this crap.";

        $result = Blasp::check($paragraph);

        $expectedOutcome = "This **** project is such a pain in the ***. I can't believe I have to deal with this ******** every single day. It's like everything is completely ****** up, and nobody gives a ****. Sometimes I just want to scream, 'What the **** is going on?' Honestly, it's a total ***********, and I'm so ******* done with this ****.";

        $this->assertTrue($result->isOffensive());
        $this->assertSame(9, $result->count());
        $this->assertCount(9, $result->uniqueWords());
        $this->assertSame($expectedOutcome, $result->clean());
    }

    public function test_word_boudary()
    {
        $result = Blasp::check('afuckb');
        $this->assertFalse($result->isOffensive());

        $result = Blasp::check('a f u c k b');
        $this->assertTrue($result->isOffensive());

        $result = Blasp::check('af@ckb');
        $this->assertTrue($result->isOffensive());
    }

    public function test_pural_profanity()
    {
        $result = Blasp::check('fuckings');
        $this->assertTrue($result->isOffensive());
        $this->assertSame(1, $result->count());
        $this->assertCount(1, $result->uniqueWords());
        $this->assertSame('*******s', $result->clean());
    }

    public function test_this_musicals_hit()
    {
        $result = Blasp::check('This musicals hit');
        $this->assertFalse($result->isOffensive());
        $this->assertSame(0, $result->count());
        $this->assertCount(0, $result->uniqueWords());
        $this->assertSame('This musicals hit', $result->clean());
    }

    public function test_ass_subtitution()
    {
        $result = Blasp::check('a$$');
        $this->assertTrue($result->isOffensive());
        $this->assertSame(1, $result->count());
        $this->assertCount(1, $result->uniqueWords());
        $this->assertSame('***', $result->clean());
    }

    public function test_embedded_profanities()
    {
        $result = Blasp::check('abcdtwatefghshitijklmfuckeropqrccuunntt');
        $this->assertTrue($result->isOffensive());
        $this->assertSame(4, $result->count());
        $this->assertCount(4, $result->uniqueWords());
        $this->assertSame('abcd****efgh****ijklm******opqr********', $result->clean());
    }

    public function test_multiple_profanities_with_spaces()
    {
        $result = Blasp::check('This is a fucking shit sentence');
        $this->assertTrue($result->isOffensive());
        $this->assertSame(2, $result->count());
        $this->assertCount(2, $result->uniqueWords());
        $this->assertSame('This is a ******* **** sentence', $result->clean());
    }

    public function test_spaced_profanity_with_substitution()
    {
        $result = Blasp::check('This is f u c k 1 n g awesome!');
        $this->assertTrue($result->isOffensive());
        $this->assertStringContainsString('*', $result->clean());
    }

    public function test_spaced_profanity_without_substitution()
    {
        $result = Blasp::check('f u c k i n g');
        $this->assertTrue($result->isOffensive());
    }

    public function test_partial_spacing_s_hit()
    {
        $result = Blasp::check('s hit');
        $this->assertTrue($result->isOffensive());
        $this->assertContains('shit', $result->uniqueWords());
    }

    public function test_partial_spacing_f_uck()
    {
        $result = Blasp::check('f uck');
        $this->assertTrue($result->isOffensive());
        $this->assertContains('fuck', $result->uniqueWords());
    }

    public function test_partial_spacing_t_wat()
    {
        $result = Blasp::check('t wat');
        $this->assertTrue($result->isOffensive());
        $this->assertContains('twat', $result->uniqueWords());
    }

    public function test_partial_spacing_fu_c_k()
    {
        $result = Blasp::check('fu c k');
        $this->assertTrue($result->isOffensive());
        $this->assertContains('fuck', $result->uniqueWords());
    }

    public function test_partial_spacing_tw_a_t()
    {
        $result = Blasp::check('tw a t');
        $this->assertTrue($result->isOffensive());
        $this->assertContains('twat', $result->uniqueWords());
    }

    public function test_no_false_positive_musicals_hit_embedded()
    {
        $result = Blasp::check('This musicals hit');
        $this->assertFalse($result->isOffensive());
        $this->assertSame('This musicals hit', $result->clean());
    }

    public function test_no_false_positive_an_alert()
    {
        $result = Blasp::check('an alert');
        $this->assertFalse($result->isOffensive());
        $this->assertSame('an alert', $result->clean());
    }

    public function test_no_false_positive_has_5_faces()
    {
        $result = Blasp::check('the user has 5 faces');
        $this->assertFalse($result->isOffensive());
        $this->assertSame('the user has 5 faces', $result->clean());
    }

    public function test_detects_at_ss_obfuscation()
    {
        $result = Blasp::check('This has @ss in it');
        $this->assertTrue($result->isOffensive());
    }

    public function test_no_false_positive_space_words()
    {
        $words = [
            'This product provides ample space for storage.',
            'The spacious design offers great workspace.',
            'Perfect for aerospace applications.',
            'Use the backspace key to delete.',
            'The spacecraft landed safely.',
        ];

        foreach ($words as $sentence) {
            $result = Blasp::check($sentence);
            $this->assertFalse(
                $result->isOffensive(),
                "\"$sentence\" should not be flagged but got: " . implode(', ', $result->uniqueWords())
            );
        }

        $result = Blasp::check('you spac');
        $this->assertTrue($result->isOffensive());

        $result = Blasp::check('you sp@c');
        $this->assertTrue($result->isOffensive());
    }
}
