<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to make rules_raw nullable on the puzzles table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('puzzles', function (Blueprint $table) {
            $table->text('rules_raw')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puzzles', function (Blueprint $table) {
            $table->text('rules_raw')->nullable(false)->change();
        });
    }
};
