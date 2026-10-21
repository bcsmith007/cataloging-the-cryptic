<?php

use App\Models\Video;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Converts existing YouTube JPEG thumbnail URLs in the database to WebP format.
     */
    public function up(): void
    {
        DB::table('videos')
            ->whereNotNull('youtube_thumbnail_url')
            ->where('youtube_thumbnail_url', 'like', '%/vi/%')
            ->orderBy('id')
            ->chunk(100, function ($videos): void {
                foreach ($videos as $video) {
                    $webpUrl = Video::convertToWebpThumbnailUrl($video->youtube_thumbnail_url);
                    if ($webpUrl !== $video->youtube_thumbnail_url) {
                        DB::table('videos')
                            ->where('id', $video->id)
                            ->update(['youtube_thumbnail_url' => $webpUrl]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('videos')
            ->whereNotNull('youtube_thumbnail_url')
            ->where('youtube_thumbnail_url', 'like', '%/vi_webp/%')
            ->orderBy('id')
            ->chunk(100, function ($videos): void {
                foreach ($videos as $video) {
                    $jpgUrl = preg_replace(
                        '/\/vi_webp\/([^\/]+)\/([^\/?#]+?)\.webp/i',
                        '/vi/$1/$2.jpg',
                        $video->youtube_thumbnail_url
                    );
                    if ($jpgUrl !== $video->youtube_thumbnail_url) {
                        DB::table('videos')
                            ->where('id', $video->id)
                            ->update(['youtube_thumbnail_url' => $jpgUrl]);
                    }
                }
            });
    }
};
