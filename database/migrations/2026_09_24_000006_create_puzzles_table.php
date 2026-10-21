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
        Schema::create('puzzles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('title', 255);
            $table->string('slug', 280)->unique();
            $table->text('rules_raw');
            $table->string('play_url_sudokupad', 1024)->nullable();
            $table->string('play_url_penpa', 1024)->nullable();
            $table->string('play_url_fpa', 1024)->nullable();
            $table->decimal('difficulty_score', 3, 1)->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzles');
    }
};
