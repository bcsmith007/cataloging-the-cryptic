<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to drop the tag column from the bookmarks table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropColumn('tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->string('tag', 50)->nullable();
        });
    }
};
