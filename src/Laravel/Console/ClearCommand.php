<?php

namespace Blaspsoft\Blasp\Laravel\Console;

use Illuminate\Console\Command;
use Blaspsoft\Blasp\Core\Dictionary;

class ClearCommand extends Command
{
    protected $signature = 'blasp:clear';
    protected $description = 'Clear the Blasp profanity cache';

    public function handle(): void
    {
        Dictionary::clearCache();
        $this->info('Blasp cache cleared successfully!');
    }
}
