<?php

namespace Blaspsoft\Blasp\Laravel;

use Closure;
use Blaspsoft\Blasp\Core\Analyzer;
use Blaspsoft\Blasp\Core\Dictionary;
use Blaspsoft\Blasp\Core\Result;
use Blaspsoft\Blasp\Core\Contracts\MaskStrategyInterface;
use Blaspsoft\Blasp\Core\Masking\CharacterMask;
use Blaspsoft\Blasp\Core\Masking\GrawlixMask;
use Blaspsoft\Blasp\Core\Masking\CallbackMask;
use Blaspsoft\Blasp\Enums\Severity;
use Blaspsoft\Blasp\Laravel\Events\ProfanityDetected;

class PendingCheck
{
    protected BlaspManager $manager;
    protected ?string $driverName = null;
    protected array $languages = [];
    protected bool $allLanguages = false;
    protected ?MaskStrategyInterface $maskStrategy = null;
    protected array $allowList = [];
    protected array $blockList = [];
    protected ?Severity $minimumSeverity = null;
    protected bool $strictMode = false;
    protected bool $lenientMode = false;

    public function __construct(BlaspManager $manager)
    {
        $this->manager = $manager;
    }

    // --- Fluent builder methods ---

    public function driver(string $driver): self
    {
        $this->driverName = $driver;
        return $this;
    }

    public function in(string ...$languages): self
    {
        $this->languages = $languages;
        return $this;
    }

    public function inAllLanguages(): self
    {
        $this->allLanguages = true;
        return $this;
    }

    public function mask(string|Closure $mask): self
    {
        if ($mask instanceof Closure) {
            $this->maskStrategy = new CallbackMask($mask);
        } elseif ($mask === 'grawlix') {
            $this->maskStrategy = new GrawlixMask();
        } else {
            $this->maskStrategy = new CharacterMask($mask);
        }
        return $this;
    }

    public function allow(string ...$words): self
    {
        $this->allowList = array_merge($this->allowList, $words);
        return $this;
    }

    public function block(string ...$words): self
    {
        $this->blockList = array_merge($this->blockList, $words);
        return $this;
    }

    public function withSeverity(Severity $severity): self
    {
        $this->minimumSeverity = $severity;
        return $this;
    }

    public function strict(): self
    {
        $this->strictMode = true;
        $this->lenientMode = false;
        return $this;
    }

    public function lenient(): self
    {
        $this->lenientMode = true;
        $this->strictMode = false;
        return $this;
    }

    // --- Deprecated backward-compat builder methods ---

    /** @deprecated Use mask() instead */
    public function maskWith(string $character): self
    {
        return $this->mask($character);
    }

    /** @deprecated Use inAllLanguages() instead */
    public function allLanguages(): self
    {
        return $this->inAllLanguages();
    }

    /** @deprecated Use in() instead */
    public function language(string $language): self
    {
        return $this->in($language);
    }

    // --- Language shortcuts ---

    public function english(): self
    {
        return $this->in('english');
    }

    public function spanish(): self
    {
        return $this->in('spanish');
    }

    public function german(): self
    {
        return $this->in('german');
    }

    public function french(): self
    {
        return $this->in('french');
    }

    // --- Configure (backward-compat) ---

    public function configure(?array $profanities = null, ?array $falsePositives = null): self
    {
        if ($profanities !== null) {
            $this->blockList = array_merge($this->blockList, $profanities);
        }
        return $this;
    }

    // --- Execute ---

    public function check(?string $text): Result
    {
        $text = $text ?? '';

        $dictionary = $this->buildDictionary();
        $driver = $this->resolveDriver();
        $mask = $this->resolveMask();

        $options = [];
        if ($this->minimumSeverity !== null) {
            $options['severity'] = $this->minimumSeverity;
        }

        $analyzer = new Analyzer();
        $result = $analyzer->analyze($text, $driver, $dictionary, $mask, $options);

        // Fire event if configured
        if ($result->isOffensive() && config('blasp.events', false)) {
            event(new ProfanityDetected($result, $text));
        }

        return $result;
    }

    public function checkMany(array $texts): array
    {
        $results = [];
        foreach ($texts as $key => $text) {
            $results[$key] = $this->check($text);
        }
        return $results;
    }

    // --- Internal ---

    protected function buildDictionary(): Dictionary
    {
        $options = [
            'allow' => array_merge(config('blasp.allow', []), $this->allowList),
            'block' => array_merge(config('blasp.block', []), $this->blockList),
        ];

        if ($this->allLanguages) {
            return Dictionary::forAllLanguages($options);
        }

        if (!empty($this->languages)) {
            if (count($this->languages) === 1) {
                return Dictionary::forLanguage($this->languages[0], $options);
            }
            return Dictionary::forLanguages($this->languages, $options);
        }

        $defaultLanguage = config('blasp.language', config('blasp.default_language', 'english'));
        return Dictionary::forLanguage($defaultLanguage, $options);
    }

    protected function resolveDriver(): \Blaspsoft\Blasp\Core\Contracts\DriverInterface
    {
        $driverName = $this->driverName ?? $this->manager->getDefaultDriver();

        if ($this->lenientMode) {
            $driverName = 'pattern';
        }

        return $this->manager->resolveDriver($driverName);
    }

    protected function resolveMask(): MaskStrategyInterface
    {
        if ($this->maskStrategy !== null) {
            return $this->maskStrategy;
        }

        $maskConfig = config('blasp.mask', config('blasp.mask_character', '*'));
        return new CharacterMask($maskConfig);
    }
}
