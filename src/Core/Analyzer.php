<?php

namespace Blaspsoft\Blasp\Core;

use Blaspsoft\Blasp\Core\Contracts\DriverInterface;
use Blaspsoft\Blasp\Core\Contracts\MaskStrategyInterface;
use Blaspsoft\Blasp\Core\Masking\CharacterMask;

class Analyzer
{
    public function analyze(
        string $text,
        DriverInterface $driver,
        Dictionary $dictionary,
        ?MaskStrategyInterface $mask = null,
        array $options = [],
    ): Result {
        $mask = $mask ?? new CharacterMask(config('blasp.mask', config('blasp.mask_character', '*')));

        return $driver->detect($text, $dictionary, $mask, $options);
    }
}
