<?php

namespace Tests\Feature;

use App\Services\YouTubeService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test verifying YouTube video ID extraction, ISO duration parsing, and API ingestion.
 */
class YouTubeServiceTest extends TestCase
{
    /**
     * Test extracting YouTube video ID from various input formats.
     */
    public function test_extract_video_id(): void
    {
        $this->assertSame('dQw4w9WgXcQ', YouTubeService::extractVideoId('dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', YouTubeService::extractVideoId('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', YouTubeService::extractVideoId('https://youtu.be/dQw4w9WgXcQ?t=45'));
        $this->assertSame('dQw4w9WgXcQ', YouTubeService::extractVideoId('https://www.youtube.com/embed/dQw4w9WgXcQ'));
    }

    /**
     * Test parsing ISO 8601 duration strings into seconds.
     */
    public function test_parse_duration(): void
    {
        $this->assertSame(3723, YouTubeService::parseDuration('PT1H2M3S'));
        $this->assertSame(2062, YouTubeService::parseDuration('PT34M22S'));
        $this->assertSame(59, YouTubeService::parseDuration('PT59S'));
        $this->assertSame(0, YouTubeService::parseDuration('invalid'));
    }

    /**
     * Test fetching video details using YouTube Data API v3 format.
     */
    public function test_get_video_details_from_data_api(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'The Greatest Sudoku Ever Made',
                            'publishedAt' => '2026-09-20T14:00:00Z',
                            'description' => 'Simon tackles an extraordinary puzzle by Phistomefel.',
                            'thumbnails' => [
                                'high' => ['url' => 'https://i.ytimg.com/vi/test/hqdefault.jpg'],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT45M10S',
                        ],
                        'statistics' => [
                            'viewCount' => '125000',
                        ],
                    ],
                ],
            ]),
        ]);

        $service = new YouTubeService;
        $details = $service->getVideoDetails('https://www.youtube.com/watch?v=testVideo12');

        $this->assertNotNull($details);
        $this->assertSame('testVideo12', $details['video_id']);
        $this->assertSame('The Greatest Sudoku Ever Made', $details['title']);
        $this->assertSame('the-greatest-sudoku-ever-made', $details['slug']);
        $this->assertSame(2710, $details['duration_seconds']);
        $this->assertSame(125000, $details['view_count']);
        $this->assertSame('api', $details['source']);
    }

    /**
     * Test fallback to YouTube oEmbed when no API key is configured.
     */
    public function test_get_video_details_fallback_to_oembed(): void
    {
        config(['services.youtube.key' => null]);

        Http::fake([
            'https://www.youtube.com/oembed*' => Http::response([
                'title' => 'Approach to Miracle Sudoku',
                'thumbnail_url' => 'https://i.ytimg.com/vi/oembedTest/hqdefault.jpg',
            ]),
        ]);

        $service = new YouTubeService;
        $details = $service->getVideoDetails('oembedTest1');

        $this->assertNotNull($details);
        $this->assertSame('Approach to Miracle Sudoku', $details['title']);
        $this->assertSame('oembed', $details['source']);
        $this->assertSame('https://i.ytimg.com/vi_webp/oembedTest/hqdefault.webp', $details['youtube_thumbnail_url']);
    }

    /**
     * Test fetching a page of playlist videos from YouTube Data API v3.
     */
    public function test_get_playlist_videos(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'contentDetails' => ['videoId' => 'vid11111111'],
                        'snippet' => ['title' => 'Puzzle Video 1'],
                    ],
                    [
                        'contentDetails' => ['videoId' => 'vid22222222'],
                        'snippet' => ['title' => 'Puzzle Video 2'],
                    ],
                ],
                'nextPageToken' => 'next-token-xyz',
                'pageInfo' => ['totalResults' => 100],
            ]),
        ]);

        $service = new YouTubeService;
        $result = $service->getPlaylistVideos('UUC-UOdK8-mIjxBQm_ot1T-Q', null, 50);

        $this->assertNotNull($result);
        $this->assertCount(2, $result['items']);
        $this->assertSame('next-token-xyz', $result['nextPageToken']);
        $this->assertSame(100, $result['totalResults']);
    }

    /**
     * Test fetching batch video details with statistics and durations.
     */
    public function test_get_batch_video_details(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'vid11111111',
                        'snippet' => [
                            'title' => 'The Miracle Sudoku &amp; Friends',
                            'publishedAt' => '2026-09-25T18:00:00Z',
                            'thumbnails' => [
                                'high' => ['url' => 'https://i.ytimg.com/vi/vid11111111/hqdefault.jpg'],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT30M15S',
                        ],
                        'statistics' => [
                            'viewCount' => '45000',
                        ],
                    ],
                ],
            ]),
        ]);

        $service = new YouTubeService;
        $details = $service->getBatchVideoDetails(['vid11111111']);

        $this->assertArrayHasKey('vid11111111', $details);
        $video = $details['vid11111111'];
        $this->assertSame('The Miracle Sudoku & Friends', $video['title']);
        $this->assertSame(1815, $video['duration_seconds']);
        $this->assertSame(45000, $video['view_count']);
        $this->assertSame('youtube', $video['video_type']);
    }
}
