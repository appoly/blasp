<?php

namespace Blaspsoft\Blasp\Exceptions;

use Blaspsoft\Blasp\Core\Result;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ProfanityRejectedException extends RuntimeException
{
    public function __construct(
        public readonly Model $model,
        public readonly string $attribute,
        public readonly Result $result,
    ) {
        parent::__construct("Profanity detected in '{$attribute}': " . implode(', ', $result->uniqueWords()));
    }

    public static function forModel(Model $model, string $attribute, Result $result): static
    {
        return new static($model, $attribute, $result);
    }
}
