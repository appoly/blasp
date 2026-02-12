<?php

namespace Blaspsoft\Blasp\Laravel\Events;

use Blaspsoft\Blasp\Core\Result;
use Illuminate\Database\Eloquent\Model;

class ModelProfanityDetected
{
    public function __construct(
        public readonly Model $model,
        public readonly string $attribute,
        public readonly Result $result,
    ) {}
}
