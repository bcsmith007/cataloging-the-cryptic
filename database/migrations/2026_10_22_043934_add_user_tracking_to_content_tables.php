<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The tables that track created_by and updated_by.
     *
     * @var list<string>
     */
    protected array $trackedTables = [
        'videos',
        'puzzles',
        'setters',
        'hosts',
        'categories',
        'constraints',
        'link_types',
        'patreon_posts',
        'bookmarks',
        'puzzle_links',
        'setter_links',
        'video_puzzles',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->trackedTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    if (! Schema::hasColumn($tableName, 'created_by_id')) {
                        $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                    }
                    if (! Schema::hasColumn($tableName, 'updated_by_id')) {
                        $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
                    }
                });
            }
        }

        // Ensure user named "Ben" exists for tagging all current records
        $ben = DB::table('users')->where('name', 'Ben')->first()
            ?? DB::table('users')->where('email', 'benjamin.c.smith@gmail.com')->first();

        if (! $ben) {
            $benId = DB::table('users')->insertGetId([
                'name' => 'Ben',
                'email' => 'benjamin.c.smith@gmail.com',
                'password' => Hash::make(Str::random(32)),
                'is_admin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $benId = $ben->id;
        }

        // Ensure "System" user exists for automated jobs
        $system = DB::table('users')->where('name', 'System')->first()
            ?? DB::table('users')->where('email', 'system@catalogingthecryptic.com')->first();

        if (! $system) {
            DB::table('users')->insert([
                'name' => 'System',
                'email' => 'system@catalogingthecryptic.com',
                'password' => Hash::make(Str::random(32)),
                'is_admin' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Tag all existing entries with the user named "Ben"
        foreach ($this->trackedTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)
                    ->whereNull('created_by_id')
                    ->update([
                        'created_by_id' => $benId,
                        'updated_by_id' => $benId,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->trackedTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    if (Schema::hasColumn($tableName, 'updated_by_id')) {
                        $table->dropConstrainedForeignId('updated_by_id');
                    }
                    if (Schema::hasColumn($tableName, 'created_by_id')) {
                        $table->dropConstrainedForeignId('created_by_id');
                    }
                });
            }
        }
    }
};
