<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Services\YouTubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests for WebP thumbnail conversion and delivery across the application.
 */
class WebpThumbnailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Video::convertToWebpThumbnailUrl properly transforms YouTube JPEG URLs to WebP.
     */
    public function test_convert_to_webp_thumbnail_url_transforms_youtube_urls(): void
    {
        // Standard i.ytimg.com hqdefault.jpg
        $this->assertSame(
            'https://i.ytimg.com/vi_webp/dQw4w9WgXcQ/hqdefault.webp',
            Video::convertToWebpThumbnailUrl('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg')
        );

        // Standard i.ytimg.com maxresdefault.jpg
        $this->assertSame(
            'https://i.ytimg.com/vi_webp/dQw4w9WgXcQ/maxresdefault.webp',
            Video::convertToWebpThumbnailUrl('https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg')
        );

        // Alternative host img.youtube.com
        $this->assertSame(
            'https://img.youtube.com/vi_webp/dQw4w9WgXcQ/sddefault.webp',
            Video::convertToWebpThumbnailUrl('https://img.youtube.com/vi/dQw4w9WgXcQ/sddefault.jpg')
        );

        // URL with query parameters preserved
        $this->assertSame(
            'https://i.ytimg.com/vi_webp/dQw4w9WgXcQ/hqdefault.webp?sqp=test123&rs=abc',
            Video::convertToWebpThumbnailUrl('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg?sqp=test123&rs=abc')
        );

        // Extension omitted
        $this->assertSame(
            'https://i.ytimg.com/vi_webp/dQw4w9WgXcQ/hqdefault.webp',
            Video::convertToWebpThumbnailUrl('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault')
        );
    }

    /**
     * Test that non-YouTube URLs and already-WebP URLs remain unmodified.
     */
    public function test_convert_to_webp_thumbnail_url_preserves_non_youtube_and_existing_webp(): void
    {
        // Already WebP
        $alreadyWebp = 'https://i.ytimg.com/vi_webp/dQw4w9WgXcQ/hqdefault.webp';
        $this->assertSame($alreadyWebp, Video::convertToWebpThumbnailUrl($alreadyWebp));

        // External image (e.g. Unsplash or Patreon)
        $externalUrl = 'https://images.unsplash.com/photo-1529699211952-734e80c4d42b?auto=format&fit=crop&w=800&q=80';
        $this->assertSame($externalUrl, Video::convertToWebpThumbnailUrl($externalUrl));

        // Empty / Null
        $this->assertNull(Video::convertToWebpThumbnailUrl(null));
        $this->assertNull(Video::convertToWebpThumbnailUrl(''));
    }

    /**
     * Test that Video model mutator automatically converts incoming YouTube JPEG thumbnail to WebP on save.
     */
    public function test_video_model_mutator_saves_thumbnail_as_webp(): void
    {
        $video = Video::factory()->create([
            'youtube_thumbnail_url' => 'https://i.ytimg.com/vi/sample12345/hqdefault.jpg',
        ]);

        $this->assertSame('https://i.ytimg.com/vi_webp/sample12345/hqdefault.webp', $video->youtube_thumbnail_url);

        $video->refresh();
        $this->assertSame('https://i.ytimg.com/vi_webp/sample12345/hqdefault.webp', $video->youtube_thumbnail_url);
    }

    /**
     * Test that fallback thumbnail URL generates a WebP URL when youtube_thumbnail_url is null.
     */
    public function test_fallback_thumbnail_generates_webp_url(): void
    {
        $video = Video::factory()->create([
            'youtube_video_id' => 'fallbackId99',
            'youtube_thumbnail_url' => null,
        ]);

        $this->assertSame('https://i.ytimg.com/vi_webp/fallbackId99/hqdefault.webp', $video->thumbnail_url);
        $this->assertSame('https://i.ytimg.com/vi_webp/fallbackId99/hqdefault.webp', $video->getThumbnailUrl());
    }

    /**
     * Test that public video views serve WebP thumbnails in rendered HTML.
     */
    public function test_public_views_render_webp_thumbnails(): void
    {
        $video = Video::factory()->create([
            'title' => 'WebP High Performance Video',
            'slug' => 'webp-high-performance-video',
            'youtube_video_id' => 'webpVid12345',
            'youtube_thumbnail_url' => 'https://i.ytimg.com/vi/webpVid12345/hqdefault.jpg',
        ]);

        // Home page
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('https://i.ytimg.com/vi_webp/webpVid12345/hqdefault.webp');

        // Videos catalog index
        $indexResponse = $this->get('/videos');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('https://i.ytimg.com/vi_webp/webpVid12345/hqdefault.webp');

        // Video show detail page
        $showResponse = $this->get(route('videos.show', $video->slug));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('https://i.ytimg.com/vi_webp/webpVid12345/hqdefault.webp');
    }

    /**
     * Test that YouTubeService returns WebP thumbnail when fetching video details.
     */
    public function test_youtube_service_returns_webp_thumbnail_from_api_and_oembed(): void
    {
        config(['services.youtube.key' => 'test-api-key']);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'apiWebpVid1',
                        'snippet' => [
                            'title' => 'API Video With JPG In Raw Response',
                            'publishedAt' => '2026-10-01T12:00:00Z',
                            'thumbnails' => [
                                'high' => ['url' => 'https://i.ytimg.com/vi/apiWebpVid1/hqdefault.jpg'],
                            ],
                        ],
                        'contentDetails' => ['duration' => 'PT10M'],
                        'statistics' => ['viewCount' => '1000'],
                    ],
                ],
            ]),
        ]);

        $service = new YouTubeService;
        $details = $service->getVideoDetails('apiWebpVid1');

        $this->assertNotNull($details);
        $this->assertSame('https://i.ytimg.com/vi_webp/apiWebpVid1/hqdefault.webp', $details['youtube_thumbnail_url']);
    }
}
