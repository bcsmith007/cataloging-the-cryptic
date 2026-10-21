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
        Schema::table('videos', function (Blueprint $table) {
            $table->renameColumn('access_level', 'video_type');
        });

        DB::table('videos')->where('video_type', 'public')->update(['video_type' => 'youtube']);

        Schema::table('videos', function (Blueprint $table) {
            $table->string('video_type', 30)->default('youtube')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('videos')->whereIn('video_type', ['youtube', 'youtube_short'])->update(['video_type' => 'public']);

        Schema::table('videos', function (Blueprint $table) {
            $table->string('video_type', 20)->default('public')->change();
            $table->renameColumn('video_type', 'access_level');
        });
    }
};
