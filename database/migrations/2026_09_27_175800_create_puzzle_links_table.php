<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create puzzle_links table and transition existing puzzle URLs to flexible link types.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('puzzle_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('puzzle_id')->constrained('puzzles')->cascadeOnDelete();
            $table->string('link_type', 50);
            $table->string('url', 1024);
            $table->string('title', 150)->nullable();
            $table->smallInteger('order_column')->default(1);
            $table->timestamps();

            $table->index(['puzzle_id', 'order_column']);
        });

        // Migrate existing play_url_sudokupad to puzzle_links
        $puzzles = DB::table('puzzles')
            ->select('id', 'play_url_sudokupad', 'play_url_penpa', 'play_url_fpa')
            ->get();

        foreach ($puzzles as $puzzle) {
            $order = 1;

            if (! empty($puzzle->play_url_sudokupad)) {
                DB::table('puzzle_links')->insert([
                    'puzzle_id' => $puzzle->id,
                    'link_type' => 'sudokupad',
                    'url' => $puzzle->play_url_sudokupad,
                    'title' => null,
                    'order_column' => $order++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (! empty($puzzle->play_url_penpa)) {
                DB::table('puzzle_links')->insert([
                    'puzzle_id' => $puzzle->id,
                    'link_type' => 'penpa',
                    'url' => $puzzle->play_url_penpa,
                    'title' => null,
                    'order_column' => $order++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (! empty($puzzle->play_url_fpa)) {
                DB::table('puzzle_links')->insert([
                    'puzzle_id' => $puzzle->id,
                    'link_type' => 'fpa',
                    'url' => $puzzle->play_url_fpa,
                    'title' => null,
                    'order_column' => $order++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Drop redundant single-link columns from puzzles
        Schema::table('puzzles', function (Blueprint $table) {
            $table->dropColumn(['play_url_sudokupad', 'play_url_penpa', 'play_url_fpa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puzzles', function (Blueprint $table) {
            $table->string('play_url_sudokupad', 1024)->nullable()->after('rules_raw');
            $table->string('play_url_penpa', 1024)->nullable()->after('play_url_sudokupad');
            $table->string('play_url_fpa', 1024)->nullable()->after('play_url_penpa');
        });

        // Restore links back to single columns if reverting
        $links = DB::table('puzzle_links')->get();
        foreach ($links as $link) {
            if ($link->link_type === 'sudokupad') {
                DB::table('puzzles')->where('id', $link->puzzle_id)->update(['play_url_sudokupad' => $link->url]);
            } elseif ($link->link_type === 'penpa') {
                DB::table('puzzles')->where('id', $link->puzzle_id)->update(['play_url_penpa' => $link->url]);
            } elseif ($link->link_type === 'fpa') {
                DB::table('puzzles')->where('id', $link->puzzle_id)->update(['play_url_fpa' => $link->url]);
            }
        }

        Schema::dropIfExists('puzzle_links');
    }
};
