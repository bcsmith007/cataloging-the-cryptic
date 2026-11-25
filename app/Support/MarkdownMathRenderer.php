<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Safely renders Markdown containing LaTeX math ($...$ and $$...$$) without
 * Markdown mangling underscores, asterisks, or backslashes inside math blocks.
 */
class MarkdownMathRenderer
{
    /**
     * Render Markdown with protected LaTeX math segments to safe HTML.
     */
    public static function render(?string $markdown): string
    {
        $source = (string) ($markdown ?? '');
        if (trim($source) === '') {
            return '';
        }

        [$tokenized, $tokens] = static::extractMathTokens($source);

        // Clean up literal escaped Markdown punctuation pasted from Google Docs outside math blocks.
        $tokenized = static::unescapeGoogleDocsMarkdown($tokenized);

        $html = Str::markdown($tokenized, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        if ($tokens !== []) {
            $replacements = [];
            foreach ($tokens as $token => $mathSegment) {
                $replacements[$token] = htmlspecialchars($mathSegment, ENT_NOQUOTES, 'UTF-8');
            }

            $html = strtr($html, $replacements);
        }

        return $html;
    }

    /**
     * Normalize literal escaped Markdown characters pasted from Google Docs
     * while preserving LaTeX math blocks untouched.
     */
    public static function cleanGoogleDocsEscapes(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        [$tokenized, $tokens] = static::extractMathTokens($text);
        $cleaned = static::unescapeGoogleDocsMarkdown($tokenized);

        if ($tokens !== []) {
            $cleaned = strtr($cleaned, $tokens);
        }

        return $cleaned;
    }

    /**
     * Extract $$...$$ (display math) and $...$ (inline math) into alphanumeric placeholder tokens.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    protected static function extractMathTokens(string $text): array
    {
        $tokens = [];
        $prefix = 'KATEXMATH'.substr(md5($text), 0, 8).'IDX';
        $counter = 0;

        // 1. Match display math ($$...$$) first so it is never split as inline math.
        $text = (string) preg_replace_callback(
            '/(?<!\\\\)\$\$([\s\S]+?)(?<!\\\\)\$\$/',
            function (array $matches) use (&$tokens, &$counter, $prefix): string {
                $token = $prefix.$counter.'END';
                $counter++;
                $tokens[$token] = $matches[0];

                return $token;
            },
            $text
        );

        // 2. Match inline math ($...$), ignoring currency/space-adjacent $ and empty $$.
        $text = (string) preg_replace_callback(
            '/(?<!\\\\)\$(?!\s|\$)(?:[^$\n\\\\]|\\\\.)+(?<!\s|\\\\)\$/',
            function (array $matches) use (&$tokens, &$counter, $prefix): string {
                $token = $prefix.$counter.'END';
                $counter++;
                $tokens[$token] = $matches[0];

                return $token;
            },
            $text
        );

        return [$text, $tokens];
    }

    /**
     * Remove unnecessary backslash escapes commonly inserted when pasting from Google Docs.
     */
    protected static function unescapeGoogleDocsMarkdown(string $text): string
    {
        return (string) preg_replace('/\\\\([*#_+\-.!\[\]()>])/', '$1', $text);
    }
}
