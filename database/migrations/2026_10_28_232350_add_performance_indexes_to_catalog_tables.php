<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->index(['published_at', 'id'], 'videos_published_at_id_index');
            $table->index('view_count', 'videos_view_count_index');
            $table->index('duration_seconds', 'videos_duration_seconds_index');
            $table->index('video_type', 'videos_video_type_index');
        });

        Schema::table('video_hosts', function (Blueprint $table) {
            $table->index('host_id', 'video_hosts_host_id_index');
        });

        Schema::table('video_puzzles', function (Blueprint $table) {
            $table->index('puzzle_id', 'video_puzzles_puzzle_id_index');
        });

        Schema::table('puzzle_setters', function (Blueprint $table) {
            $table->index('setter_id', 'puzzle_setters_setter_id_index');
        });

        Schema::table('puzzle_constraints', function (Blueprint $table) {
            $table->index('constraint_id', 'puzzle_constraints_constraint_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puzzle_constraints', function (Blueprint $table) {
            $table->dropIndex('puzzle_constraints_constraint_id_index');
        });

        Schema::table('puzzle_setters', function (Blueprint $table) {
            $table->dropIndex('puzzle_setters_setter_id_index');
        });

        Schema::table('video_puzzles', function (Blueprint $table) {
            $table->dropIndex('video_puzzles_puzzle_id_index');
        });

        Schema::table('video_hosts', function (Blueprint $table) {
            $table->dropIndex('video_hosts_host_id_index');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex('videos_video_type_index');
            $table->dropIndex('videos_duration_seconds_index');
            $table->dropIndex('videos_view_count_index');
            $table->dropIndex('videos_published_at_id_index');
        });
    }
};
