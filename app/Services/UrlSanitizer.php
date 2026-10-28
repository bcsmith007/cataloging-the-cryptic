<?php

namespace App\Services;

/**
 * Service for sanitizing and normalizing external URLs.
 *
 * Specifically handles unwrapping YouTube redirect URLs copied from video descriptions
 * to extract and store the underlying destination target URL.
 */
class UrlSanitizer
{
    /**
     * Unwrap a YouTube redirect URL to extract and decode its destination target URL.
     *
     * Example:
     * https://www.youtube.com/redirect?event=video_description&redir_token=...&q=https%3A%2F%2Fwww.thetimes.co.uk%2Fpuzzles%2F45741&v=...
     * -> https://www.thetimes.co.uk/puzzles/45741
     */
    public static function unwrapYouTubeRedirect(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $trimmed = trim($url);
        if ($trimmed === '') {
            return '';
        }

        // Normalize HTML entities that may be present when copying from web source
        if (str_contains($trimmed, '&amp;')) {
            $trimmed = str_replace('&amp;', '&', $trimmed);
        }

        // Fast bail-out for URLs that do not resemble a YouTube redirect
        if (stripos($trimmed, 'youtube.com/redirect') === false && stripos($trimmed, 'youtu.be/redirect') === false) {
            return $trimmed;
        }

        // Ensure scheme is present so parse_url can extract the host correctly
        $rawUrl = $trimmed;
        if (! preg_match('#^https?://#i', $rawUrl)) {
            $rawUrl = 'https://'.ltrim($rawUrl, '/');
        }

        $parts = parse_url($rawUrl);
        if (! $parts || empty($parts['host'])) {
            return $trimmed;
        }

        $host = strtolower($parts['host']);
        $path = strtolower($parts['path'] ?? '');

        $isYouTubeHost = ($host === 'youtube.com' || str_ends_with($host, '.youtube.com') || $host === 'youtu.be');
        $isRedirectPath = (rtrim($path, '/') === '/redirect');

        if (! $isYouTubeHost || ! $isRedirectPath) {
            return $trimmed;
        }

        $queryStr = $parts['query'] ?? '';
        if (empty($queryStr)) {
            return $trimmed;
        }

        parse_str($queryStr, $queryParams);

        if (empty($queryParams['q']) || ! is_string($queryParams['q'])) {
            return $trimmed;
        }

        $target = trim($queryParams['q']);

        // Handle possible double-URL-encoded query parameter
        if (preg_match('#^https?%3A%2F%2F#i', $target)) {
            $target = urldecode($target);
        }

        // Recursively unwrap if target is itself another redirect
        return static::unwrapYouTubeRedirect($target);
    }

    /**
     * Scan any text or markdown content and unwrap any embedded YouTube redirect links.
     */
    public static function unwrapAllInText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        if (stripos($text, 'youtube.com/redirect') === false && stripos($text, 'youtu.be/redirect') === false) {
            return $text;
        }

        return preg_replace_callback(
            '#https?://(?:www\.|m\.)?(?:youtube\.com|youtu\.be)/redirect\?[^\s<>"\'\)\]]+#i',
            fn (array $matches) => static::unwrapYouTubeRedirect($matches[0]) ?? $matches[0],
            $text
        );
    }
}
