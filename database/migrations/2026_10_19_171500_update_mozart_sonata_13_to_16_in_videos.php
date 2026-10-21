<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Corrects typo in intro and outro music from Mozart's Sonata No. 13 to Mozart's Sonata No. 16.
     */
    public function up(): void
    {
        DB::table('videos')
            ->where('intro_music', "Mozart's Sonata No. 13")
            ->update(['intro_music' => "Mozart's Sonata No. 16"]);

        DB::table('videos')
            ->where('outro_music', "Mozart's Sonata No. 13")
            ->update(['outro_music' => "Mozart's Sonata No. 16"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('videos')
            ->where('intro_music', "Mozart's Sonata No. 16")
            ->update(['intro_music' => "Mozart's Sonata No. 13"]);

        DB::table('videos')
            ->where('outro_music', "Mozart's Sonata No. 16")
            ->update(['outro_music' => "Mozart's Sonata No. 13"]);
    }
};
