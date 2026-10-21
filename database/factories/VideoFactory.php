<?php

namespace Database\Factories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    protected $model = Video::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'patreon_post_id' => null,
            'youtube_video_id' => Str::random(11),
            'video_type' => 'youtube',
            'access_level' => 'public',
            'patreon_post_url' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'duration_seconds' => fake()->numberBetween(1200, 4800),
            'view_count' => fake()->numberBetween(5000, 200000),
            'stats_synced_at' => now(),
            'youtube_thumbnail_url' => 'https://i.ytimg.com/vi_webp/test/hqdefault.webp',
            'intro_music' => fake()->optional(0.7)->words(3, true),
            'outro_music' => fake()->optional(0.7)->words(3, true),
            'is_finalized' => false,
        ];
    }

    /**
     * Indicate that the video is marked as finalized.
     */
    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_finalized' => true,
        ]);
    }

    /**
     * Indicate that the video is patron-exclusive.
     */
    public function exclusive(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_level' => 'exclusive',
            'patreon_post_url' => 'https://www.patreon.com/posts/'.fake()->randomNumber(8, true),
        ]);
    }

    /**
     * Indicate that the video is public access.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_level' => 'public',
        ]);
    }

    /**
     * Indicate that the video is a Patreon exclusive.
     */
    public function patreon(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_level' => 'exclusive',
            'video_type' => 'patreon',
            'youtube_video_id' => null,
            'patreon_post_url' => 'https://www.patreon.com/posts/'.fake()->randomNumber(8, true),
        ]);
    }

    /**
     * Indicate that the video is a YouTube Short.
     */
    public function short(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_type' => 'youtube_short',
            'duration_seconds' => fake()->numberBetween(15, 60),
        ]);
    }

    /**
     * Indicate that the video is a Live Stream.
     */
    public function liveStream(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_type' => 'live_stream',
            'duration_seconds' => fake()->numberBetween(5400, 14400),
        ]);
    }
}
