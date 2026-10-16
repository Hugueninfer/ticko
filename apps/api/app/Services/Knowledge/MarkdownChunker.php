<?php

declare(strict_types=1);

namespace App\Services\Knowledge;

use Illuminate\Support\Str;

/**
 * Splits a Markdown article into passages of about {@see self::TARGET_TOKENS}
 * tokens, never across a heading, with {@see self::OVERLAP_TOKENS} tokens
 * of overlap so a fact cut at a boundary is still found whole. Markdown
 * syntax is reduced to plain text. Tokens are estimated (4 characters each).
 */
final class MarkdownChunker
{
    public const int TARGET_TOKENS = 320;

    public const int OVERLAP_TOKENS = 40;

    private const int CHARS_PER_TOKEN = 4;

    /**
     * @return list<Chunk>
     */
    public function chunk(string $title, string $markdown): array
    {
        $chunks = [];

        foreach ($this->sections($title, $markdown) as [$heading, $text]) {
            foreach ($this->split($text) as $content) {
                $chunks[] = new Chunk(count($chunks) + 1, $heading, $content, self::estimateTokens($content));
            }
        }

        return $chunks;
    }

    public static function estimateTokens(string $text): int
    {
        return max(1, (int) ceil(mb_strlen($text) / self::CHARS_PER_TOKEN));
    }

    /**
     * @return list<array{string, string}> heading and plain text of each section
     */
    private function sections(string $title, string $markdown): array
    {
        $sections = [];
        $heading = $title;
        $buffer = [];

        foreach (preg_split('/\R/u', $markdown) ?: [] as $line) {
            if (preg_match('/^\s{0,3}#{1,6}\s+(.+?)\s*#*\s*$/u', $line, $match) === 1) {
                $sections[] = [$heading, implode("\n", $buffer)];
                $heading = Str::limit($this->plain($match[1]), 190, '…');
                $buffer = [];

                continue;
            }

            $buffer[] = $line;
        }

        $sections[] = [$heading, implode("\n", $buffer)];

        return array_values(array_filter(
            array_map(fn (array $section): array => [$section[0], $this->plain($section[1])], $sections),
            fn (array $section): bool => $section[1] !== '',
        ));
    }

    /**
     * @return list<string>
     */
    private function split(string $text): array
    {
        $max = self::TARGET_TOKENS * self::CHARS_PER_TOKEN;
        $pieces = [];

        // Paragraphs first, then sentences, then hard cuts for run-on text.
        foreach (preg_split('/\n{2,}/u', $text) ?: [] as $paragraph) {
            foreach (mb_strlen($paragraph) > $max ? (preg_split('/(?<=[.!?])\s+/u', $paragraph) ?: []) : [$paragraph] as $sentence) {
                array_push($pieces, ...mb_str_split($sentence, $max));
            }
        }

        $chunks = [];
        $current = '';

        foreach ($pieces as $piece) {
            $piece = trim($piece);

            if ($piece === '') {
                continue;
            }

            if ($current !== '' && mb_strlen($current) + mb_strlen($piece) + 2 > $max) {
                $chunks[] = $current;
                $current = $this->tail($current)."\n\n".$piece;

                continue;
            }

            $current = $current === '' ? $piece : $current."\n\n".$piece;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /** The last words of a passage, repeated at the start of the next one. */
    private function tail(string $text): string
    {
        $length = self::OVERLAP_TOKENS * self::CHARS_PER_TOKEN;
        $tail = mb_substr($text, -$length);
        $space = mb_strpos($tail, ' ');

        return '…'.trim($space === false ? $tail : mb_substr($tail, $space + 1));
    }

    /** Markdown to plain text: links keep their label, emphasis and code marks go. */
    private function plain(string $markdown): string
    {
        $text = preg_replace([
            '/!\[([^\]]*)\]\([^)]*\)/u',   // images -> alt text
            '/\[([^\]]+)\]\([^)]*\)/u',     // links -> label
            '/^\s*>\s?/mu',                  // block quotes
            '/^\s*[-*+]\s+/mu',              // bullets
            '/(\*\*|__|\*|~~|`)/u',         // emphasis and code marks
            '/<[^>]+>/u',                    // inline HTML
            '/[ \t]+/u',
            '/\n{3,}/u',
        ], ['$1', '$1', '', '• ', '', '', ' ', "\n\n"], $markdown) ?? $markdown;

        return trim($text);
    }
}
