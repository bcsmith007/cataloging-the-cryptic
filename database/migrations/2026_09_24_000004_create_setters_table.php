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
        Schema::create('setters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->text('bio')->nullable();
            $table->string('patreon_url', 512)->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setters');
    }
};
