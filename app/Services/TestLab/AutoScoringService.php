<?php

namespace App\Services\TestLab;

use App\Models\AiTest;
use App\Models\AiTestResult;
use Illuminate\Support\Str;

class AutoScoringService
{
    public function score(AiTest $test, AiTestResult $result): array
    {
        $response = trim((string) $result->response_text);
        if ($response === '') {
            return $this->emptySuggestion();
        }

        $expected = trim((string) $test->expected_output);
        $prompt = trim((string) $test->prompt);

        $accuracy = $this->accuracyScore($response, $expected);
        $adherence = $this->adherenceScore($response, $prompt, $expected);
        $quality = $this->qualityScore($response, $prompt);
        $creativity = $this->creativityScore($response, (string) $test->category);
        $speed = $this->speedScore($result->latency_ms);

        $scores = [
            'score_quality' => $quality['score'],
            'score_accuracy' => $accuracy['score'],
            'score_prompt_adherence' => $adherence['score'],
            'score_creativity' => $creativity['score'],
            'score_speed' => $speed['score'],
        ];

        $confidence = $this->confidence($expected, $accuracy, $adherence, $result->latency_ms);
        $overall = $this->weightedOverall($scores, $test->scoreWeights());
        $summary = $this->summary($scores, $accuracy, $adherence, $quality, $speed, $confidence);

        return [
            'scores' => $scores,
            'overall' => $overall,
            'summary' => $summary,
            'confidence' => $confidence,
            'signals' => [
                'accuracy' => $accuracy['detail'],
                'adherence' => $adherence['detail'],
                'quality' => $quality['detail'],
                'creativity' => $creativity['detail'],
                'speed' => $speed['detail'],
            ],
        ];
    }

    private function emptySuggestion(): array
    {
        return [
            'scores' => [],
            'overall' => null,
            'summary' => null,
            'confidence' => 0,
            'signals' => [],
        ];
    }

    private function accuracyScore(string $response, string $expected): array
    {
        if ($expected === '') {
            return [
                'score' => 70,
                'detail' => 'No answer key is stored, so accuracy uses a conservative neutral suggestion.',
                'numeric_ratio' => null,
                'keyword_ratio' => null,
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
            if (str_contains($responseNorm, $keyword)) {
                $keywordMatches++;
            }
        }
        $keywordRatio = $keywords !== [] ? $keywordMatches / count($keywords) : 0.5;

        $lastExpectedLine = $this->lastMeaningfulLine($expected);
        $lastResponseLine = $this->lastMeaningfulLine($response);
        $finalLineMatch = 0.0;
        if ($lastExpectedLine !== '' && $lastResponseLine !== '') {
            if ($this->normalize($lastExpectedLine) === $this->normalize($lastResponseLine)) {
                $finalLineMatch = 1.0;
            } elseif ($this->lineSimilarity($lastExpectedLine, $lastResponseLine) >= 0.75) {
                $finalLineMatch = 0.75;
            }
        }

        if ($numericRatio !== null) {
            $score = ($numericRatio * 70) + ($keywordRatio * 20) + ($finalLineMatch * 10);
        } else {
            $score = ($keywordRatio * 80) + ($finalLineMatch * 20);
        }

        $score = $this->clampScore($score);

        $detail = $numericRatio !== null
            ? sprintf('Matched about %d%% of answer-key numeric values and %d%% of key terms.', round($numericRatio * 100), round($keywordRatio * 100))
            : sprintf('Matched about %d%% of key answer terms.', round($keywordRatio * 100));

        return [
            'score' => $score,
            'detail' => $detail,
            'numeric_ratio' => $numericRatio,
            'keyword_ratio' => $keywordRatio,
        ];
    }

    private function adherenceScore(string $response, string $prompt, string $expected): array
    {
        $checks = [];
        $promptLower = Str::lower($prompt);
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

            if (str_contains($r, 'end your answer') || str_contains($r, 'final')) {
                $expectedLine = $this->lastMeaningfulLine($expected);
                $responseLine = $this->lastMeaningfulLine($response);
                $checks[] = $expectedLine !== '' && $this->lineSimilarity($expectedLine, $responseLine) >= 0.75;
                continue;
            }

            if (str_contains($r, 'do not change') && preg_match('/\$?\s*48[, ]?000/', $prompt)) {
                $checks[] = preg_match('/\$?\s*48[, ]?000/', $response) === 1;
                continue;
            }
        }

        $requestedLabels = $this->requestedLabels($prompt);
        foreach ($requestedLabels as $label) {
            $checks[] = str_contains($responseLower, Str::lower($label));
        }

        if ($checks === []) {
            $hasStructure = substr_count($response, "\n") >= 2;
            $checks = [$hasStructure, mb_strlen($response) >= 120];
        }

        $passed = count(array_filter($checks));
        $score = $this->clampScore(($passed / max(1, count($checks))) * 100);

        return [
            'score' => $score,
            'detail' => sprintf('Passed %d of %d detectable prompt constraints.', $passed, count($checks)),
            'checks' => count($checks),
            'passed' => $passed,
        ];
    }

    private function qualityScore(string $response, string $prompt): array
    {
        $length = mb_strlen($response);
        $lines = preg_split('/\R/u', $response) ?: [];
        $nonEmptyLines = array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== ''));

        $score = 55;
        if ($length >= 180) $score += 12;
        if ($length >= 350) $score += 8;
        if ($length > 12000) $score -= 12;
        if (count($nonEmptyLines) >= 4) $score += 8;
        if (preg_match('/(?:^|\R)\s*(?:[-*]|\d+[.)])\s+/u', $response)) $score += 5;
        if (preg_match('/(?:=|therefore|thus|total|final)/iu', $response)) $score += 7;
        if (str_contains(Str::lower($prompt), 'show the calculations') && ! preg_match('/(?:=|×|\*|\+|\-|\/|%)/u', $response)) $score -= 18;

        $score = $this->clampScore($score);

        return [
            'score' => $score,
            'detail' => sprintf('Response has %d characters across %d non-empty lines; structure and task-specific explanation were checked.', $length, count($nonEmptyLines)),
        ];
    }

    private function creativityScore(string $response, string $category): array
    {
        $creativeCategories = ['Writing', 'Image', 'Video', 'Audio'];
        if (! in_array($category, $creativeCategories, true)) {
            return [
                'score' => 80,
                'detail' => 'Creativity is not a primary objective for this test category, so a neutral-high score is suggested.',
            ];
        }

        $words = preg_split('/[^\pL\pN]+/u', Str::lower($response), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return ['score' => 50, 'detail' => 'Not enough text to assess originality.'];
        }

        $uniqueRatio = count(array_unique($words)) / max(1, count($words));
        $score = 55 + min(40, $uniqueRatio * 65);

        return [
            'score' => $this->clampScore($score),
            'detail' => sprintf('Lexical variety was about %d%%; this is only a lightweight originality signal.', round($uniqueRatio * 100)),
        ];
    }

    private function speedScore(?int $latencyMs): array
    {
        if (! $latencyMs || $latencyMs <= 0) {
            return [
                'score' => 50,
                'detail' => 'Latency was not recorded, so Speed uses a neutral 50/100 suggestion.',
            ];
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

        return [
            'score' => $score,
            'detail' => sprintf('Recorded latency: %.2f seconds.', $seconds),
        ];
    }

    private function confidence(string $expected, array $accuracy, array $adherence, ?int $latencyMs): int
    {
        $score = 45;
        if ($expected !== '') $score += 20;
        if (($accuracy['numeric_ratio'] ?? null) !== null) $score += 15;
        if (($adherence['checks'] ?? 0) >= 3) $score += 10;
        if ($latencyMs) $score += 5;

        return max(35, min(95, $score));
    }

    private function weightedOverall(array $scores, array $weights): float
    {
        $criteria = config('test_lab.criteria', []);
        $weighted = 0.0;
        $total = 0;

        foreach ($criteria as $key => $definition) {
            $field = $definition['field'];
            $weight = max(0, (int) ($weights[$key] ?? 0));
            if ($weight <= 0 || ! array_key_exists($field, $scores)) continue;
            $weighted += ((float) $scores[$field]) * $weight;
            $total += $weight;
        }

        return $total > 0 ? round($weighted / $total, 1) : 0.0;
    }

    private function summary(array $scores, array $accuracy, array $adherence, array $quality, array $speed, int $confidence): string
    {
        return sprintf(
            'Auto-evaluation suggestion (%d%% confidence). Accuracy: %d/100 — %s Prompt adherence: %d/100 — %s Quality: %d/100. Speed: %d/100 — %s Review the saved response before marking this result Verified.',
            $confidence,
            $scores['score_accuracy'],
            $accuracy['detail'],
            $scores['score_prompt_adherence'],
            $adherence['detail'],
            $scores['score_quality'],
            $scores['score_speed'],
            $speed['detail'],
        );
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
        $stop = array_flip([
            'this','that','with','from','into','then','than','have','your','their','there','where','when','what','which','will','would','should','could','must','only','also','each','exact','exactly','total','answer','calculate','calculation','final','amount','monthly','current','represented','percentage','following','requirements','budget',
        ]);

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
            if ($inRequirements && preg_match('/^[-*]\s*(.+)$/u', $trim, $m)) {
                $requirements[] = trim($m[1]);
            }
        }

        return $requirements;
    }

    private function requestedLabels(string $prompt): array
    {
        $labels = [];
        if (preg_match('/calculate\s+the\s+new\s+monthly\s+amount\s+for\s*:\s*(.+?)(?:\R\s*\R|Then\s+calculate|Requirements?\s*:)/isu', $prompt, $match)) {
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
