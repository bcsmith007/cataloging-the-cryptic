<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('youtube_sync_logs', function (Blueprint $table) {
            $table->bigInteger('total_views_added')->default(0)->after('updated_views_count');
        });

        // Backfill existing log records from the structured details JSON
        try {
            $logs = DB::table('youtube_sync_logs')->whereNotNull('details')->get();
            foreach ($logs as $log) {
                $details = is_string($log->details) ? json_decode($log->details, true) : (array) $log->details;
                if (! is_array($details)) {
                    continue;
                }

                $sum = 0;
                foreach ($details as $item) {
                    if (($item['type'] ?? '') === 'view_update') {
                        $sum += (int) ($item['diff'] ?? 0);
                    }
                }

                DB::table('youtube_sync_logs')
                    ->where('id', $log->id)
                    ->update(['total_views_added' => $sum]);
            }
        } catch (Throwable) {
            // Ignore if running in environments without previous log records
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('youtube_sync_logs', function (Blueprint $table) {
            $table->dropColumn('total_views_added');
        });
    }
};
