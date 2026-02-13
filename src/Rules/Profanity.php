<?php

namespace Blaspsoft\Blasp\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Blaspsoft\Blasp\Enums\Severity;

class Profanity implements ValidationRule
{
    protected ?string $language = null;
    protected ?int $maxScore = null;
    protected ?Severity $minimumSeverity = null;

    public static function make(): self
    {
        return new self();
    }

    public function in(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function maxScore(int $score): self
    {
        $this->maxScore = $score;
        return $this;
    }

    public function severity(Severity $severity): self
    {
        $this->minimumSeverity = $severity;
        return $this;
    }

    public static function __callStatic(string $name, array $arguments): self
    {
        return (new self())->$name(...$arguments);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        $manager = app('blasp');
        $pendingCheck = $manager->newPendingCheck();

        if ($this->language) {
            $pendingCheck = $pendingCheck->in($this->language);
        }

        if ($this->minimumSeverity) {
            $pendingCheck = $pendingCheck->withSeverity($this->minimumSeverity);
        }

        $result = $pendingCheck->check($value);

        if ($this->maxScore !== null) {
            if ($result->score() > $this->maxScore) {
                $fail('The :attribute contains profanity.');
            }
            return;
        }

        if ($result->isOffensive()) {
            $fail('The :attribute contains profanity.');
        }
    }
}
