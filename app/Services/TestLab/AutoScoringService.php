<?php

namespace App\Services\TestLab;

use App\Models\AiTest;
use App\Models\AiTestResult;
use App\Models\AiTestRun;
use Illuminate\Support\Str;

class AutoScoringService
{
    public function score(AiTest $test, AiTestRun|AiTestResult $run): array
    {
        $response = trim((string) $run->response_text);
        if ($response === '') return $this->emptySuggestion();

        $rubric = $test->evaluationRubric();
        $scores = [];
        $signals = [];
        $autoCount = 0;
        $manualCount = 0;

        foreach ($rubric as $criterion) {
            $key = $criterion['key'];
            $strategy = $criterion['auto_strategy'] ?? 'manual';
            $evaluation = match ($strategy) {
                'answer_key' => $this->answerKeyScore($response, trim((string) $test->expected_output)),
                'prompt_constraints' => $this->adherenceScore($response, trim((string) $test->prompt), trim((string) $test->expected_output)),
                'structure' => $this->structureScore($response, trim((string) $test->prompt)),
                'latency' => $this->speedScore($run->latency_ms),
                default => ['score' => null, 'detail' => 'Human rubric review required; no automatic score is assigned to this criterion.'],
            };

            $scores[$key] = $evaluation['score'];
            $signals[$key] = $evaluation['detail'];
            if ($evaluation['score'] === null) $manualCount++; else $autoCount++;
        }

        $overall = $this->weightedOverall($scores, $rubric);
        $confidence = $this->confidence($test, $run, $autoCount, count($rubric));

        $autoLabels = collect($rubric)->filter(fn ($item) => ($scores[$item['key']] ?? null) !== null)->pluck('label')->all();
        $manualLabels = collect($rubric)->filter(fn ($item) => ($scores[$item['key']] ?? null) === null)->pluck('label')->all();

        $summary = 'Automatic checks scored '.($autoLabels ? implode(', ', $autoLabels) : 'no criteria').' from deterministic signals.';
        if ($manualLabels) $summary .= ' Human review is still required for '.implode(', ', $manualLabels).'.';
        if ($overall !== null) $summary .= ' Partial auto-score: '.number_format($overall, 1).'/100 across auto-scored applicable criteria only.';

        return [
            'scores' => $scores,
            'overall' => $overall,
            'summary' => $summary,
            'confidence' => $confidence,
            'signals' => $signals,
            'auto_count' => $autoCount,
            'manual_count' => $manualCount,
            'is_partial' => $manualCount > 0,
        ];
    }

    private function emptySuggestion(): array
    {
        return [
            'scores' => [], 'overall' => null, 'summary' => null, 'confidence' => 0,
            'signals' => [], 'auto_count' => 0, 'manual_count' => 0, 'is_partial' => true,
        ];
    }

    private function answerKeyScore(string $response, string $expected): array
    {
        if ($expected === '') {
            return [
                'score' => null,
                'detail' => 'N/A for automatic scoring: no expected answer or reference output is stored.',
            ];
        }

        $responseNorm = $this->normalize($response);
        $expectedNorm = $this->normalize($expected);
        $expectedNumbers = $this->numbers($expected);
        $responseNumbers = $this->numbers($response);
        $numericRatio = null;

        if ($expectedNumbers !== []) {
            $matches = 0;
            $remaining = $responseNumbers;
            foreach ($expectedNumbers as $number) {
                $index = array_search($number, $remaining, true);
                if ($index !== false) {
                    $matches++;
                    unset($remaining[$index]);
                }
            }
            $numericRatio = $matches / max(1, count($expectedNumbers));
        }

        $keywords = $this->keywords($expectedNorm);
        $keywordMatches = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($responseNorm, $keyword)) $keywordMatches++;
        }
        $keywordRatio = $keywords !== [] ? $keywordMatches / count($keywords) : null;

        $lastExpectedLine = $this->lastMeaningfulLine($expected);
        $lastResponseLine = $this->lastMeaningfulLine($response);
        $finalLineMatch = null;
        if ($lastExpectedLine !== '' && $lastResponseLine !== '') {
            $similarity = $this->lineSimilarity($lastExpectedLine, $lastResponseLine);
            $finalLineMatch = $similarity >= .92 ? 1.0 : ($similarity >= .75 ? .75 : 0.0);
        }

        $components = [];
        if ($numericRatio !== null) $components[] = [$numericRatio, 70];
        if ($keywordRatio !== null) $components[] = [$keywordRatio, $numericRatio !== null ? 20 : 80];
        if ($finalLineMatch !== null) $components[] = [$finalLineMatch, $numericRatio !== null ? 10 : 20];

        if ($components === []) {
            return ['score' => null, 'detail' => 'N/A for automatic scoring: the stored answer key does not contain reliable machine-checkable signals.'];
        }

        $weighted = 0;
        $weightTotal = 0;
        foreach ($components as [$value, $weight]) {
            $weighted += $value * $weight;
            $weightTotal += $weight;
        }
        $score = $this->clampScore(($weighted / max(1, $weightTotal)) * 100);

        $parts = [];
        if ($numericRatio !== null) $parts[] = round($numericRatio * 100).'% answer-key numeric values matched';
        if ($keywordRatio !== null) $parts[] = round($keywordRatio * 100).'% key terms matched';
        if ($finalLineMatch !== null) $parts[] = 'final-answer similarity '.round($finalLineMatch * 100).'%';

        return ['score' => $score, 'detail' => implode('; ', $parts).'. Review manually before verification.'];
    }

    private function adherenceScore(string $response, string $prompt, string $expected): array
    {
        $checks = [];
        $responseLower = Str::lower($response);
        $requirements = $this->requirementLines($prompt);

        foreach ($requirements as $requirement) {
            $r = Str::lower($requirement);

            if (str_contains($r, 'show') && (str_contains($r, 'calculation') || str_contains($r, 'work'))) {
                $checks[] = preg_match('/(?:\d[\d,.]*\s*[×x*+\-÷\/]\s*\d|=\s*\$?\d|\bcalculation\b)/iu', $response) === 1;
                continue;
            }
            if ((str_contains($r, '2 decimal') || str_contains($r, 'two decimal')) && str_contains($r, 'percentage')) {
                $checks[] = preg_match('/\b\d+(?:,\d{3})*\.\d{2}%/u', $response) === 1;
                continue;
            }
            if (str_contains($r, 'end your answer') || str_contains($r, 'final answer')) {
                $expectedLine = $this->lastMeaningfulLine($expected);
                $responseLine = $this->lastMeaningfulLine($response);
                if ($expectedLine !== '') $checks[] = $this->lineSimilarity($expectedLine, $responseLine) >= 0.75;
                continue;
            }
            if (preg_match('/(?:include|mention|contain)\s+["“]?([^"”.,;]{3,50})/iu', $requirement, $m)) {
                $checks[] = str_contains($responseLower, Str::lower(trim($m[1])));
                continue;
            }
        }

        foreach ($this->requestedLabels($prompt) as $label) {
            $checks[] = str_contains($responseLower, Str::lower($label));
        }

        $promptLower = Str::lower($prompt);
        if (preg_match('/(?:exactly|return|provide|give)\s+(\d+)\s+(?:bullet|bullets|items|points)/iu', $prompt, $m)) {
            preg_match_all('/(?:^|\R)\s*(?:[-*]|\d+[.)])\s+/u', $response, $listMatches);
            $checks[] = count($listMatches[0] ?? []) === (int) $m[1];
        }
        if (str_contains($promptLower, 'json')) {
            json_decode(trim($response), true);
            $checks[] = json_last_error() === JSON_ERROR_NONE;
        }
        if (str_contains($promptLower, 'table')) {
            $checks[] = preg_match('/\|.+\|/u', $response) === 1;
        }

        if ($checks === []) {
            return ['score' => null, 'detail' => 'N/A for automatic scoring: no deterministic prompt constraints were detected.'];
        }

        $passed = count(array_filter($checks));
        return [
            'score' => $this->clampScore(($passed / count($checks)) * 100),
            'detail' => sprintf('Passed %d of %d detectable prompt constraints.', $passed, count($checks)),
        ];
    }

    private function structureScore(string $response, string $prompt): array
    {
        $checks = [];
        $promptLower = Str::lower($prompt);

        if (preg_match('/(?:exactly|return|provide|give)\s+(\d+)\s+(?:bullet|bullets|items|points)/iu', $prompt, $m)) {
            preg_match_all('/(?:^|\R)\s*(?:[-*]|\d+[.)])\s+/u', $response, $matches);
            $checks[] = count($matches[0] ?? []) === (int) $m[1];
        }
        if (str_contains($promptLower, 'json')) {
            json_decode(trim($response), true);
            $checks[] = json_last_error() === JSON_ERROR_NONE;
        }
        if (str_contains($promptLower, 'markdown table') || str_contains($promptLower, 'table')) {
            $checks[] = preg_match('/\|.+\|/u', $response) === 1;
        }
        if (preg_match('/(?:under|maximum|max)\s+(\d+)\s+words?/iu', $prompt, $m)) {
            $wordCount = count(preg_split('/\s+/u', trim($response), -1, PREG_SPLIT_NO_EMPTY) ?: []);
            $checks[] = $wordCount <= (int) $m[1];
        }

        if ($checks === []) {
            return ['score' => null, 'detail' => 'N/A for automatic scoring: no explicit machine-checkable structure requirement was detected.'];
        }

        $passed = count(array_filter($checks));
        return ['score' => $this->clampScore(($passed / count($checks)) * 100), 'detail' => sprintf('Passed %d of %d detectable structure checks.', $passed, count($checks))];
    }

    private function speedScore(?int $latencyMs): array
    {
        if (! $latencyMs || $latencyMs <= 0) {
            return ['score' => null, 'detail' => 'N/A: latency was not recorded, so Speed is excluded from automatic scoring.'];
        }

        $seconds = $latencyMs / 1000;
        $score = match (true) {
            $seconds <= 2 => 100,
            $seconds <= 4 => 92,
            $seconds <= 8 => 84,
            $seconds <= 15 => 74,
            $seconds <= 30 => 62,
            $seconds <= 60 => 48,
            default => 30,
        };

        return ['score' => $score, 'detail' => sprintf('Recorded latency: %.2f seconds.', $seconds)];
    }

    private function confidence(AiTest $test, AiTestRun|AiTestResult $run, int $autoCount, int $criteriaCount): int
    {
        if ($autoCount === 0) return 0;
        $coverage = $criteriaCount > 0 ? $autoCount / $criteriaCount : 0;
        $score = 35 + (int) round($coverage * 35);
        if (filled($test->expected_output)) $score += 15;
        if (! empty($run->latency_ms)) $score += 5;
        return max(25, min(90, $score));
    }

    private function weightedOverall(array $scores, array $rubric): ?float
    {
        $weighted = 0.0;
        $total = 0;
        foreach ($rubric as $criterion) {
            $key = $criterion['key'];
            $score = $scores[$key] ?? null;
            $weight = max(0, (int) ($criterion['weight'] ?? 0));
            if ($score === null || $weight <= 0) continue;
            $weighted += (float) $score * $weight;
            $total += $weight;
        }
        return $total > 0 ? round($weighted / $total, 1) : null;
    }

    private function numbers(string $text): array
    {
        preg_match_all('/(?<![\pL\pN])\$?\s*-?\d[\d,]*(?:\.\d+)?\s*%?/u', $text, $matches);
        return array_values(array_filter(array_map(function ($value) {
            $value = preg_replace('/[\s,$]/u', '', (string) $value);
            $percent = str_ends_with($value, '%');
            $value = rtrim($value, '%');
            if (! is_numeric($value)) return null;
            $normalized = rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
            return $normalized.($percent ? '%' : '');
        }, $matches[0] ?? [])));
    }

    private function keywords(string $normalized): array
    {
        $stop = array_flip(['this','that','with','from','into','then','than','have','your','their','there','where','when','what','which','will','would','should','could','must','only','also','each','exact','exactly','total','answer','calculate','calculation','final','amount','following','requirements']);
        $words = preg_split('/[^\pL\pN]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_values(array_unique(array_filter($words, fn ($word) => mb_strlen($word) >= 4 && ! isset($stop[$word]) && ! is_numeric($word))));
        return array_slice($words, 0, 40);
    }

    private function requirementLines(string $prompt): array
    {
        $lines = preg_split('/\R/u', $prompt) ?: [];
        $requirements = [];
        $inRequirements = false;
        foreach ($lines as $line) {
            $trim = trim($line);
            if (preg_match('/^requirements?\s*:/iu', $trim)) {
                $inRequirements = true;
                continue;
            }
            if ($inRequirements && preg_match('/^[-*]\s*(.+)$/u', $trim, $m)) $requirements[] = trim($m[1]);
        }
        return $requirements;
    }

    private function requestedLabels(string $prompt): array
    {
        $labels = [];
        if (preg_match('/(?:return|provide|include)\s+(?:the\s+)?following\s*:\s*(.+?)(?:\R\s*\R|Requirements?\s*:|$)/isu', $prompt, $match)) {
            preg_match_all('/^\s*[-*]\s*([^\r\n]+)/mu', $match[1], $items);
            foreach ($items[1] ?? [] as $item) {
                $label = trim(preg_replace('/[:.]+$/u', '', $item));
                if ($label !== '' && mb_strlen($label) <= 80) $labels[] = $label;
            }
        }
        return array_values(array_unique($labels));
    }

    private function lastMeaningfulLine(string $text): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), fn ($line) => $line !== ''));
        return $lines === [] ? '' : (string) end($lines);
    }

    private function lineSimilarity(string $a, string $b): float
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        if ($a === '' || $b === '') return 0.0;
        similar_text($a, $b, $percent);
        return $percent / 100;
    }

    private function normalize(string $text): string
    {
        $text = Str::lower($text);
        $text = str_replace(['—', '–', '×', '÷'], ['-', '-', 'x', '/'], $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function clampScore(float|int $score): int
    {
        return (int) round(max(0, min(100, $score)));
    }
}
