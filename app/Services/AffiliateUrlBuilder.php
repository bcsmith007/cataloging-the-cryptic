<?php

namespace App\Services;

/**
 * Service for enriching store URLs with affiliate tokens and campaign parameters.
 */
class AffiliateUrlBuilder
{
    /**
     * Enrich a store URL with affiliate/tracking parameters if configured.
     *
     * @param  string  $store  Platform key: 'apple', 'google', or 'steam'.
     * @param  string|null  $url  Original store destination URL.
     * @return string|null The enriched URL, or original URL if no affiliate data is configured.
     */
    public static function build(string $store, ?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        /** @var array<string, mixed> $config */
        $config = config("affiliates.{$store}", []);
        $params = $config['custom_params'] ?? [];

        if ($store === 'apple') {
            if (! empty($config['pt'])) {
                $params['pt'] = $config['pt'];
            }
            if (! empty($config['ct'])) {
                $params['ct'] = $config['ct'];
            }
            if (! empty($config['at'])) {
                $params['at'] = $config['at'];
            }
        } elseif ($store === 'google') {
            if (! empty($config['referrer'])) {
                $params['referrer'] = $config['referrer'];
            }
        }

        if (empty($params)) {
            return $url;
        }

        return static::appendQueryParams($url, $params);
    }

    /**
     * Safely append query parameters to a URL preserving existing query strings and fragments.
     *
     * @param  string  $url  Target URL.
     * @param  array<string, mixed>  $params  Key-value query parameters to append.
     */
    public static function appendQueryParams(string $url, array $params): string
    {
        $parsed = parse_url($url);
        if (! $parsed) {
            return $url;
        }

        $existingQuery = [];
        if (! empty($parsed['query'])) {
            parse_str($parsed['query'], $existingQuery);
        }

        $merged = array_merge($existingQuery, $params);
        $newQuery = http_build_query($merged);

        $scheme = isset($parsed['scheme']) ? "{$parsed['scheme']}://" : '';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ":{$parsed['port']}" : '';
        $path = $parsed['path'] ?? '';
        $fragment = isset($parsed['fragment']) ? "#{$parsed['fragment']}" : '';

        return "{$scheme}{$host}{$port}{$path}?{$newQuery}{$fragment}";
    }
}
