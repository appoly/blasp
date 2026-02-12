<?php

namespace Blaspsoft\Blasp\Laravel\Console;

use Illuminate\Console\Command;
use Blaspsoft\Blasp\Core\Dictionary;

class LanguagesCommand extends Command
{
    protected $signature = 'blasp:languages';
    protected $description = 'List available languages and their word counts';

    public function handle(): void
    {
        $languages = Dictionary::getAvailableLanguages();

        $rows = [];
        foreach ($languages as $language) {
            $config = Dictionary::loadLanguageConfig($language);
            $profanityCount = count($config['profanities'] ?? []);
            $falsePositiveCount = count($config['false_positives'] ?? []);
            $hasSeverity = isset($config['severity']) ? 'Yes' : 'No';

            $rows[] = [
                ucfirst($language),
                $profanityCount,
                $falsePositiveCount,
                $hasSeverity,
            ];
        }

        $this->table(['Language', 'Profanities', 'False Positives', 'Severity Map'], $rows);
    }
}
