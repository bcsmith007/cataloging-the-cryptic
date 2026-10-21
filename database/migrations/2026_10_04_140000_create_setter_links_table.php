<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create setter_links table and seed standard link types for setters.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('setter_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setter_id')->constrained('setters')->cascadeOnDelete();
            $table->string('link_type', 50);
            $table->string('url', 1024);
            $table->string('title', 150)->nullable();
            $table->smallInteger('order_column')->default(1);
            $table->timestamps();

            $table->index(['setter_id', 'order_column']);
        });

        // Ensure standard creator link types exist in link_types
        $standardTypes = [
            ['name' => 'Patreon', 'slug' => 'patreon'],
            ['name' => 'Personal Website', 'slug' => 'personal_website'],
            ['name' => 'Logic Masters Germany', 'slug' => 'logic_masters_germany'],
            ['name' => 'SudokuPad', 'slug' => 'sudokupad'],
            ['name' => 'Twitter / X', 'slug' => 'twitter'],
            ['name' => 'YouTube', 'slug' => 'youtube'],
            ['name' => 'Discord', 'slug' => 'discord'],
            ['name' => 'Twitch', 'slug' => 'twitch'],
            ['name' => 'Bluesky', 'slug' => 'bluesky'],
            ['name' => 'Other', 'slug' => 'other'],
        ];

        foreach ($standardTypes as $type) {
            DB::table('link_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Migrate any existing patreon_url from setters into setter_links
        $settersWithPatreon = DB::table('setters')
            ->whereNotNull('patreon_url')
            ->where('patreon_url', '!=', '')
            ->select('id', 'patreon_url')
            ->get();

        foreach ($settersWithPatreon as $setter) {
            DB::table('setter_links')->insert([
                'setter_id' => $setter->id,
                'link_type' => 'patreon',
                'url' => $setter->patreon_url,
                'title' => 'Patreon',
                'order_column' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setter_links');
    }
};
