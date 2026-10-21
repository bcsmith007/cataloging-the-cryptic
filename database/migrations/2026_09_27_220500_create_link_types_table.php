<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create link_types table for dynamic puzzle link platforms.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('link_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        // Seed default link types
        $defaultTypes = [
            ['name' => 'SudokuPad', 'slug' => 'sudokupad'],
            ['name' => 'Logic Masters Germany', 'slug' => 'logic_masters_germany'],
            ['name' => 'Penpa', 'slug' => 'penpa'],
            ['name' => 'Times Puzzles', 'slug' => 'times_puzzles'],
            ['name' => 'Cracking the Cryptic App', 'slug' => 'ctc_app'],
            ['name' => 'Other', 'slug' => 'other'],
        ];

        foreach ($defaultTypes as $type) {
            DB::table('link_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Also ensure any existing distinct types from puzzle_links are stored in link_types
        if (Schema::hasTable('puzzle_links')) {
            $existing = DB::table('puzzle_links')
                ->whereNotNull('link_type')
                ->distinct()
                ->pluck('link_type');

            foreach ($existing as $slug) {
                if (! empty($slug)) {
                    $name = ucwords(str_replace(['_', '-'], ' ', $slug));
                    DB::table('link_types')->updateOrInsert(
                        ['slug' => $slug],
                        [
                            'name' => $name,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_types');
    }
};
