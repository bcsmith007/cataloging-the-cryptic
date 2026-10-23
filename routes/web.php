<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SetterController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\VideoController;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
Route::get('/videos/top', [VideoController::class, 'top'])->name('videos.top');
Route::get('/videos/most-viewed', fn (Request $request) => redirect()->route('videos.top', $request->query(), 301));
Route::get('/videos/longest', [VideoController::class, 'longest'])->name('videos.longest');
Route::get('/videos/shortest', [VideoController::class, 'shortest'])->name('videos.shortest');
Route::get('/videos/random', [VideoController::class, 'random'])->name('videos.random');
Route::get('/stats', [StatsController::class, 'index'])->name('stats');
Route::get('/videos/stats', fn () => redirect()->route('stats', [], 301));
Route::get('/videos/{record}/edit', fn (Video $record) => redirect()->route('filament.admin.resources.videos.edit', ['record' => $record]))->name('videos.edit');
Route::get('/videos/{slug}', [VideoController::class, 'show'])->name('videos.show');
Route::get('/setters', [SetterController::class, 'index'])->name('setters.index');
Route::get('/setters/{setter}', [SetterController::class, 'show'])->name('setters.show');
Route::view('/privacy', 'privacy')->name('privacy');
Route::get('/bookmark-player', function (Request $request) {
    $queryString = $request->getQueryString();

    return redirect('/admin/bookmark-player'.($queryString ? '?'.$queryString : ''));
});

Route::get('/test-video-bookmarks', function (Request $request) {
    $queryString = $request->getQueryString();

    return redirect('/admin/bookmark-player'.($queryString ? '?'.$queryString : ''));
});
