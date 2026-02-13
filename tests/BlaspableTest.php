<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Result;
use Blaspsoft\Blasp\Blaspable;
use Blaspsoft\Blasp\Events\ModelProfanityDetected;
use Blaspsoft\Blasp\Exceptions\ProfanityRejectedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

class BlaspableTestModel extends Model
{
    use Blaspable;

    protected $table = 'comments';
    protected $guarded = [];
    public $timestamps = false;

    protected array $blaspable = ['body', 'title'];
}

class BlaspableRejectModel extends Model
{
    use Blaspable;

    protected $table = 'comments';
    protected $guarded = [];
    public $timestamps = false;

    protected array $blaspable = ['body', 'title'];
    protected string $blaspMode = 'reject';
}

class BlaspableSpanishModel extends Model
{
    use Blaspable;

    protected $table = 'comments';
    protected $guarded = [];
    public $timestamps = false;

    protected array $blaspable = ['body'];
    protected string $blaspLanguage = 'spanish';
}

class BlaspableCustomMaskModel extends Model
{
    use Blaspable;

    protected $table = 'comments';
    protected $guarded = [];
    public $timestamps = false;

    protected array $blaspable = ['body'];
    protected string $blaspMask = '#';
}

class BlaspableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('email')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('comments');
        parent::tearDown();
    }

    public function test_sanitize_mode_masks_profanity_on_save()
    {
        $model = BlaspableTestModel::create([
            'body' => 'This is a fucking sentence',
            'title' => 'Clean title',
        ]);

        $this->assertStringNotContainsString('fucking', $model->body);
        $this->assertStringContainsString('*', $model->body);
        $this->assertSame('Clean title', $model->title);
        $this->assertTrue($model->exists);
    }

    public function test_reject_mode_throws_exception()
    {
        $this->expectException(ProfanityRejectedException::class);
        $this->expectExceptionMessage("Profanity detected in 'body'");

        BlaspableRejectModel::create([
            'body' => 'This is a fucking sentence',
            'title' => 'Clean title',
        ]);
    }

    public function test_reject_mode_does_not_persist_model()
    {
        try {
            BlaspableRejectModel::create([
                'body' => 'This is a fucking sentence',
            ]);
        } catch (ProfanityRejectedException) {
            // expected
        }

        $this->assertSame(0, BlaspableRejectModel::count());
    }

    public function test_clean_text_passes_through_untouched()
    {
        $model = BlaspableTestModel::create([
            'body' => 'This is a perfectly clean sentence',
            'title' => 'Nice title',
        ]);

        $this->assertSame('This is a perfectly clean sentence', $model->body);
        $this->assertSame('Nice title', $model->title);
    }

    public function test_only_dirty_attributes_are_checked()
    {
        $model = BlaspableTestModel::create([
            'body' => 'Clean body',
            'title' => 'Clean title',
        ]);

        // Update only body — title should not be re-checked
        $model->body = 'Still clean';
        $model->save();

        $this->assertArrayNotHasKey('title', $model->blaspResults());
        $this->assertArrayHasKey('body', $model->blaspResults());
    }

    public function test_non_blaspable_attributes_are_ignored()
    {
        $model = BlaspableTestModel::create([
            'body' => 'Clean body',
            'email' => 'fucking@example.com',
        ]);

        $this->assertSame('fucking@example.com', $model->email);
    }

    public function test_per_model_language_override()
    {
        $model = BlaspableSpanishModel::create([
            'body' => 'Esto es una mierda',
        ]);

        $this->assertStringNotContainsString('mierda', $model->body);
        $this->assertStringContainsString('*', $model->body);
    }

    public function test_per_model_mask_override()
    {
        $model = BlaspableCustomMaskModel::create([
            'body' => 'This is a fucking sentence',
        ]);

        $this->assertStringNotContainsString('fucking', $model->body);
        $this->assertStringContainsString('#', $model->body);
        $this->assertStringNotContainsString('*', $model->body);
    }

    public function test_had_profanity_returns_true_when_profanity_detected()
    {
        $model = BlaspableTestModel::create([
            'body' => 'This is a fucking sentence',
        ]);

        $this->assertTrue($model->hadProfanity());
    }

    public function test_had_profanity_returns_false_for_clean_text()
    {
        $model = BlaspableTestModel::create([
            'body' => 'This is a clean sentence',
        ]);

        $this->assertFalse($model->hadProfanity());
    }

    public function test_blasp_results_returns_results_array()
    {
        $model = BlaspableTestModel::create([
            'body' => 'This is a fucking sentence',
            'title' => 'Clean title',
        ]);

        $results = $model->blaspResults();

        $this->assertArrayHasKey('body', $results);
        $this->assertArrayHasKey('title', $results);
        $this->assertInstanceOf(Result::class, $results['body']);
        $this->assertTrue($results['body']->isOffensive());
        $this->assertFalse($results['title']->isOffensive());
    }

    public function test_blasp_result_returns_single_attribute_result()
    {
        $model = BlaspableTestModel::create([
            'body' => 'This is a fucking sentence',
        ]);

        $result = $model->blaspResult('body');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isOffensive());
    }

    public function test_blasp_result_returns_null_for_unknown_attribute()
    {
        $model = BlaspableTestModel::create([
            'body' => 'Clean body',
        ]);

        $this->assertNull($model->blaspResult('nonexistent'));
    }

    public function test_without_blasp_checking_disables_profanity_check()
    {
        $model = BlaspableTestModel::withoutBlaspChecking(function () {
            return BlaspableTestModel::create([
                'body' => 'This is a fucking sentence',
            ]);
        });

        $this->assertSame('This is a fucking sentence', $model->body);
        $this->assertTrue($model->exists);
    }

    public function test_model_profanity_detected_event_fires_in_sanitize_mode()
    {
        Event::fake([ModelProfanityDetected::class]);

        BlaspableTestModel::create([
            'body' => 'This is a fucking sentence',
        ]);

        Event::assertDispatched(ModelProfanityDetected::class, function ($event) {
            return $event->attribute === 'body'
                && $event->result->isOffensive()
                && $event->model instanceof BlaspableTestModel;
        });
    }

    public function test_model_profanity_detected_event_fires_in_reject_mode()
    {
        Event::fake([ModelProfanityDetected::class]);

        try {
            BlaspableRejectModel::create([
                'body' => 'This is a fucking sentence',
            ]);
        } catch (ProfanityRejectedException) {
            // expected
        }

        Event::assertDispatched(ModelProfanityDetected::class, function ($event) {
            return $event->attribute === 'body';
        });
    }

    public function test_event_not_fired_for_clean_text()
    {
        Event::fake([ModelProfanityDetected::class]);

        BlaspableTestModel::create([
            'body' => 'This is a clean sentence',
        ]);

        Event::assertNotDispatched(ModelProfanityDetected::class);
    }

    public function test_update_triggers_sanitization()
    {
        $model = BlaspableTestModel::create([
            'body' => 'Clean body',
        ]);

        $model->body = 'This is a fucking update';
        $model->save();

        $this->assertStringNotContainsString('fucking', $model->body);
        $this->assertStringContainsString('*', $model->body);
    }

    public function test_multiple_profane_attributes_are_sanitized()
    {
        $model = BlaspableTestModel::create([
            'body' => 'This is a fucking sentence',
            'title' => 'What the shit',
        ]);

        $this->assertStringNotContainsString('fucking', $model->body);
        $this->assertStringNotContainsString('shit', $model->title);
        $this->assertTrue($model->hadProfanity());
    }

    public function test_null_attributes_are_skipped()
    {
        $model = BlaspableTestModel::create([
            'body' => null,
            'title' => 'Clean title',
        ]);

        $this->assertNull($model->body);
        $this->assertSame('Clean title', $model->title);
    }

    public function test_profanity_rejected_exception_contains_model_and_attribute()
    {
        try {
            BlaspableRejectModel::create([
                'body' => 'This is a fucking sentence',
            ]);
            $this->fail('Expected ProfanityRejectedException was not thrown');
        } catch (ProfanityRejectedException $e) {
            $this->assertSame('body', $e->attribute);
            $this->assertInstanceOf(BlaspableRejectModel::class, $e->model);
            $this->assertInstanceOf(Result::class, $e->result);
            $this->assertTrue($e->result->isOffensive());
        }
    }
}
