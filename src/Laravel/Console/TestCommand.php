<?php

namespace Blaspsoft\Blasp\Laravel\Console;

use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'blasp:test {text} {--lang= : Language to check against} {--verbose}';
    protected $description = 'Test profanity detection on a given text';

    public function handle(): void
    {
        $text = $this->argument('text');
        $language = $this->option('lang') ?? config('blasp.language', config('blasp.default_language', 'english'));

        $manager = app('blasp');
        $result = $manager->in($language)->check($text);

        $this->info("Input: {$text}");
        $this->info("Language: {$language}");
        $this->newLine();

        if ($result->isOffensive()) {
            $this->error('Profanity detected!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Clean text', $result->clean()],
                    ['Score', $result->score()],
                    ['Count', $result->count()],
                    ['Severity', $result->severity()?->value ?? 'n/a'],
                    ['Unique words', implode(', ', $result->uniqueWords())],
                ]
            );

            if ($this->option('verbose')) {
                $this->newLine();
                $this->info('Matched words:');
                $rows = [];
                foreach ($result->words() as $word) {
                    $rows[] = [
                        $word->text,
                        $word->base,
                        $word->severity->value,
                        $word->position,
                        $word->length,
                    ];
                }
                $this->table(['Text', 'Base', 'Severity', 'Position', 'Length'], $rows);
            }
        } else {
            $this->info('No profanity detected. Text is clean.');
        }
    }
}
