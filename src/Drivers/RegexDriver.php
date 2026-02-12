<?php

namespace Blaspsoft\Blasp\Drivers;

use Blaspsoft\Blasp\Core\Contracts\DriverInterface;
use Blaspsoft\Blasp\Core\Contracts\MaskStrategyInterface;
use Blaspsoft\Blasp\Core\Dictionary;
use Blaspsoft\Blasp\Core\MatchedWord;
use Blaspsoft\Blasp\Core\Result;
use Blaspsoft\Blasp\Core\Score;
use Blaspsoft\Blasp\Core\Matchers\FalsePositiveFilter;
use Blaspsoft\Blasp\Core\Matchers\CompoundWordDetector;
use Blaspsoft\Blasp\Enums\Severity;

class RegexDriver implements DriverInterface
{
    private FalsePositiveFilter $filter;
    private CompoundWordDetector $compoundDetector;

    public function detect(string $text, Dictionary $dictionary, MaskStrategyInterface $mask, array $options = []): Result
    {
        if (empty($text)) {
            return new Result($text ?? '', $text ?? '', [], 0);
        }

        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        $this->filter = new FalsePositiveFilter($dictionary->getFalsePositives());
        $this->compoundDetector = new CompoundWordDetector();

        $profanityExpressions = $dictionary->getProfanityExpressions();

        // Sort by key length descending (longest profanity first)
        uksort($profanityExpressions, fn($a, $b) => strlen($b) - strlen($a));

        $normalizer = $dictionary->getNormalizer();
        $workingCleanString = $text;
        $normalizedString = $normalizer->normalize($workingCleanString);
        $originalNormalized = preg_replace('/\s+/', ' ', $normalizedString);

        $matchedWords = [];
        $uniqueMap = [];
        $profanitiesCount = 0;
        $continue = true;

        while ($continue) {
            $continue = false;
            $normalizedString = preg_replace('/\s+/', ' ', $normalizedString);
            $workingCleanString = preg_replace('/\s+/', ' ', $workingCleanString);

            foreach ($profanityExpressions as $profanity => $expression) {
                preg_match_all($expression, $normalizedString, $matches, PREG_OFFSET_CAPTURE);

                if (!empty($matches[0])) {
                    foreach ($matches[0] as $match) {
                        $start = $match[1];
                        $length = mb_strlen($match[0], 'UTF-8');
                        $matchedText = $match[0];

                        // Check word boundary spanning
                        if ($this->filter->isSpanningWordBoundary($matchedText, $normalizedString, $start)) {
                            continue;
                        }

                        // Check hex/UUID token
                        if ($this->filter->isInsideHexToken($normalizedString, $start, $length)) {
                            continue;
                        }

                        // Full word context for false positive check
                        $fullWord = $this->filter->getFullWordContext($normalizedString, $start, $length);

                        // Check pure alpha substring against original (unmasked) normalized
                        $originalFullWord = $this->filter->getFullWordContext($originalNormalized, $start, $length);
                        if ($this->compoundDetector->isPureAlphaSubstring($matchedText, $originalFullWord, $profanity, $profanityExpressions)) {
                            continue;
                        }

                        // False positive check
                        if ($this->filter->isFalsePositive($fullWord)) {
                            continue;
                        }

                        $continue = true;

                        // Apply mask
                        $replacement = $mask->mask($matchedText, $length);

                        $workingCleanString = mb_substr($workingCleanString, 0, $start) . $replacement .
                            mb_substr($workingCleanString, $start + $length);

                        $normalizedString = mb_substr($normalizedString, 0, $start) . str_repeat('*', mb_strlen($match[0], 'UTF-8')) .
                            mb_substr($normalizedString, $start + mb_strlen($match[0], 'UTF-8'));

                        // Track match
                        $profanitiesCount++;

                        $matchedWords[] = new MatchedWord(
                            text: $matchedText,
                            base: $profanity,
                            severity: $dictionary->getSeverity($profanity),
                            position: $start,
                            length: $length,
                            language: $dictionary->getLanguage(),
                        );

                        if (!isset($uniqueMap[$profanity])) {
                            $uniqueMap[$profanity] = true;
                        }
                    }
                }
            }
        }

        // Apply severity filter if set
        $minimumSeverity = $options['severity'] ?? null;
        if ($minimumSeverity instanceof Severity) {
            $matchedWords = array_values(array_filter(
                $matchedWords,
                fn(MatchedWord $w) => $w->severity->isAtLeast($minimumSeverity)
            ));
        }

        $totalWords = max(1, str_word_count($text));
        $scoreValue = Score::calculate($matchedWords, $totalWords);

        return new Result($text, $workingCleanString, $matchedWords, $scoreValue);
    }
}
