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
        Schema::table('puzzles', function (Blueprint $table) {
            $table->string('access_level', 30)->default('public')->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puzzles', function (Blueprint $table) {
            $table->dropColumn('access_level');
        });
    }
};
