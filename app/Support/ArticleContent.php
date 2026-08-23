<?php

namespace App\Support;

use Illuminate\Support\Str;

class ArticleContent
{
    public static function render(?string $content): string
    {
        $content = trim((string) $content);
        if ($content === '') {
            return '';
        }

        $lines = preg_split('/\R/u', $content) ?: [];
        $html = [];
        $paragraph = [];
        $listType = null;
        $listItems = [];

        $flushParagraph = function () use (&$paragraph, &$html): void {
            if ($paragraph === []) {
                return;
            }
            $text = trim(implode(' ', $paragraph));
            if ($text !== '') {
                $html[] = '<p>'.self::inline($text).'</p>';
            }
            $paragraph = [];
        };

        $flushList = function () use (&$listType, &$listItems, &$html): void {
            if ($listType === null || $listItems === []) {
                $listType = null;
                $listItems = [];
                return;
            }
            $items = implode('', array_map(fn (string $item) => '<li>'.self::inline($item).'</li>', $listItems));
            $html[] = '<'.$listType.'>'.$items.'</'.$listType.'>';
            $listType = null;
            $listItems = [];
        };

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                $flushParagraph();
                $flushList();
                continue;
            }

            if (preg_match('/^(#{2,3})\s+(.+)$/u', $line, $match)) {
                $flushParagraph();
                $flushList();
                $level = strlen($match[1]);
                $text = trim($match[2]);
                $id = Str::slug(strip_tags($text));
                $html[] = sprintf('<h%d id="%s">%s</h%d>', $level, e($id), self::inline($text), $level);
                continue;
            }

            if (preg_match('/^-\s+(.+)$/u', $line, $match)) {
                $flushParagraph();
                if ($listType !== null && $listType !== 'ul') {
                    $flushList();
                }
                $listType = 'ul';
                $listItems[] = trim($match[1]);
                continue;
            }

            if (preg_match('/^\d+[.)]\s+(.+)$/u', $line, $match)) {
                $flushParagraph();
                if ($listType !== null && $listType !== 'ol') {
                    $flushList();
                }
                $listType = 'ol';
                $listItems[] = trim($match[1]);
                continue;
            }

            if (str_starts_with($line, '> ')) {
                $flushParagraph();
                $flushList();
                $html[] = '<blockquote>'.self::inline(substr($line, 2)).'</blockquote>';
                continue;
            }

            $paragraph[] = $line;
        }

        $flushParagraph();
        $flushList();

        return implode("\n", $html);
    }

    /** @return array<int,array{question:string,answer:string}> */
    public static function faq(?string $content): array
    {
        $content = trim((string) $content);
        if ($content === '') {
            return [];
        }

        $lines = preg_split('/\R/u', $content) ?: [];
        $inFaq = false;
        $question = null;
        $answer = [];
        $faq = [];

        $flush = function () use (&$question, &$answer, &$faq): void {
            if ($question !== null && trim(implode(' ', $answer)) !== '') {
                $faq[] = [
                    'question' => $question,
                    'answer' => trim(implode(' ', $answer)),
                ];
            }
            $question = null;
            $answer = [];
        };

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if (preg_match('/^##\s+(Frequently Asked Questions|FAQ)\s*$/iu', $line)) {
                $inFaq = true;
                continue;
            }
            if (! $inFaq) {
                continue;
            }
            if (preg_match('/^##\s+/', $line)) {
                $flush();
                break;
            }
            if (preg_match('/^###\s+(.+)$/u', $line, $match)) {
                $flush();
                $question = trim($match[1]);
                continue;
            }
            if ($question !== null && $line !== '') {
                $answer[] = preg_replace('/^[-*>]\s*/u', '', $line) ?? $line;
            }
        }

        $flush();
        return array_slice($faq, 0, 8);
    }

    private static function inline(string $text): string
    {
        $escaped = e($text);
        $escaped = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/`([^`]+)`/u', '<code>$1</code>', $escaped) ?? $escaped;
        return $escaped;
    }
}
