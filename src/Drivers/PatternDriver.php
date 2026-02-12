<?php

namespace Blaspsoft\Blasp\Drivers;

use Blaspsoft\Blasp\Core\Contracts\DriverInterface;
use Blaspsoft\Blasp\Core\Contracts\MaskStrategyInterface;
use Blaspsoft\Blasp\Core\Dictionary;
use Blaspsoft\Blasp\Core\MatchedWord;
use Blaspsoft\Blasp\Core\Result;
use Blaspsoft\Blasp\Core\Score;
use Blaspsoft\Blasp\Enums\Severity;

class PatternDriver implements DriverInterface
{
    public function detect(string $text, Dictionary $dictionary, MaskStrategyInterface $mask, array $options = []): Result
    {
        if (empty($text)) {
            return new Result($text ?? '', $text ?? '', [], 0);
        }

        $matchedWords = [];
        $cleanText = $text;
        $lowerText = mb_strtolower($text, 'UTF-8');
        $profanities = $dictionary->getProfanities();
        $falsePositives = array_map('strtolower', $dictionary->getFalsePositives());

        // Sort profanities by length descending for longest-match-first
        usort($profanities, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

        foreach ($profanities as $profanity) {
            $lowerProfanity = mb_strtolower($profanity, 'UTF-8');
            $pattern = '/\b' . preg_quote($lowerProfanity, '/') . '\b/iu';

            if (preg_match_all($pattern, $lowerText, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $start = $match[1];
                    $length = mb_strlen($match[0], 'UTF-8');
                    $originalMatch = mb_substr($text, $start, $length);

                    // Skip false positives
                    if (in_array($lowerProfanity, $falsePositives)) {
                        continue;
                    }

                    $replacement = $mask->mask($originalMatch, $length);
                    $cleanText = mb_substr($cleanText, 0, $start) . $replacement . mb_substr($cleanText, $start + $length);

                    $matchedWords[] = new MatchedWord(
                        text: $originalMatch,
                        base: $profanity,
                        severity: $dictionary->getSeverity($profanity),
                        position: $start,
                        length: $length,
                        language: $dictionary->getLanguage(),
                    );
                }
            }
        }

        // Apply severity filter
        $minimumSeverity = $options['severity'] ?? null;
        if ($minimumSeverity instanceof Severity) {
            $matchedWords = array_values(array_filter(
                $matchedWords,
                fn(MatchedWord $w) => $w->severity->isAtLeast($minimumSeverity)
            ));
        }

        $totalWords = max(1, str_word_count($text));
        $scoreValue = Score::calculate($matchedWords, $totalWords);

        return new Result($text, $cleanText, $matchedWords, $scoreValue);
    }
}
