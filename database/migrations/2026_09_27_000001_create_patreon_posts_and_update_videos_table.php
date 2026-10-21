<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patreon_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 280)->unique();
            $table->string('patreon_url', 512);
            $table->timestampTz('published_at');
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('patreon_post_id')->nullable()->after('id')->constrained('patreon_posts')->nullOnDelete();
            $table->string('access_level', 30)->default('public')->after('video_type');
        });

        // Migrate any existing 'patreon' video_type records into dedicated patreon_posts
        $patreonVideos = DB::table('videos')->where('video_type', 'patreon')->get();
        foreach ($patreonVideos as $pv) {
            $postId = DB::table('patreon_posts')->insertGetId([
                'title' => $pv->title,
                'slug' => $pv->slug.'-patreon-post',
                'patreon_url' => $pv->patreon_post_url ?? 'https://www.patreon.com',
                'published_at' => $pv->published_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('videos')->where('id', $pv->id)->update([
                'patreon_post_id' => $postId,
                'access_level' => 'exclusive',
                'video_type' => 'youtube',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['patreon_post_id']);
            $table->dropColumn(['patreon_post_id', 'access_level']);
        });

        Schema::dropIfExists('patreon_posts');
    }
};
