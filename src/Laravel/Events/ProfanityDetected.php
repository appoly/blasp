<?php

namespace Blaspsoft\Blasp\Laravel\Events;

use Blaspsoft\Blasp\Core\Result;

class ProfanityDetected
{
    public function __construct(
        public readonly Result $result,
        public readonly string $originalText,
    ) {}
}
