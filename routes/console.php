<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('session:prune {--ip= : Delete all sessions matching a specific IP address} {--user_id= : Delete all sessions for a specific user ID}', function () {
    $ip = $this->option('ip');
    $userId = $this->option('user_id');

    if ($ip) {
        $deleted = DB::table('sessions')->where('ip_address', $ip)->delete();
        $this->info("Deleted {$deleted} session(s) for IP address {$ip}.");

        return;
    }

    if ($userId) {
        $deleted = DB::table('sessions')->where('user_id', $userId)->delete();
        $this->info("Deleted {$deleted} session(s) for user ID {$userId}.");

        return;
    }

    $lifetime = (int) config('session.lifetime', 120);
    $threshold = now()->subMinutes($lifetime)->getTimestamp();
    $deleted = DB::table('sessions')->where('last_activity', '<', $threshold)->delete();

    $this->info("Pruned {$deleted} expired session(s) older than {$lifetime} minutes.");
})->purpose('Prune expired database sessions or remove consumer sessions by IP or user ID');

Schedule::command('session:prune')->daily();
Schedule::command('ctc:sync-youtube')->everyThirtyMinutes()->withoutOverlapping();
