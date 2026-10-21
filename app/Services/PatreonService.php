<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Service for querying post metadata from Patreon.
 *
 * Extracts post IDs from Patreon URLs and fetches title and publish timestamps
 * via Patreon's public post API endpoint.
 */
class PatreonService
{
    /**
     * Extract the numeric post ID from a Patreon URL or raw ID string.
     */
    public static function extractPostId(string $input): ?string
    {
        $trimmed = trim($input);

        if (empty($trimmed)) {
            return null;
        }

        // Match any Patreon post URL with or without creator prefix:
        // e.g. https://www.patreon.com/crackingthecryptic/posts/one-impossible-170516826
        // or https://www.patreon.com/posts/whispering-shadows-98765432
        // or https://patreon.com/posts/12345678
        if (preg_match('%(?:patreon\.com/.*?/posts/|/posts/)(?:.*[-_])?(\d+)(?:[/?#]|$)%i', $trimmed, $matches)) {
            return $matches[1];
        }

        // Direct numeric ID
        if (ctype_digit($trimmed)) {
            return $trimmed;
        }

        return null;
    }

    /**
     * Fetch post metadata for a given post URL or ID from Patreon's public API.
     *
     * @param  string  $input  Patreon URL or numeric post ID.
     * @return array<string, mixed>|null
     */
    public function getPostDetails(string $input): ?array
    {
        $postId = static::extractPostId($input);

        if (! $postId) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
            ])->timeout(8)->get("https://www.patreon.com/api/posts/{$postId}");

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            $attributes = $json['data']['attributes'] ?? null;

            if (! $attributes) {
                return null;
            }

            $publishedRaw = $attributes['published_at'] ?? $attributes['created_at'] ?? null;
            $publishedAt = null;

            if ($publishedRaw) {
                $publishedAt = Carbon::parse($publishedRaw)->toDateTimeString();
            }

            return [
                'post_id' => $postId,
                'title' => trim($attributes['title'] ?? ''),
                'published_at' => $publishedAt,
                'patreon_url' => $attributes['url'] ?? $input,
                'teaser_text' => isset($attributes['teaser_text']) ? trim($attributes['teaser_text']) : null,
            ];
        } catch (Exception) {
            return null;
        }
    }
}
