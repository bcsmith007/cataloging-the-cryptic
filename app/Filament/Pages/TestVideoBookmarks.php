<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Http\RedirectResponse;

/**
 * Backward compatibility alias redirecting /admin/test-video-bookmarks to Bookmark Player.
 */
class TestVideoBookmarks extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'test-video-bookmarks';

    protected string $view = 'filament.pages.bookmark-player';

    /**
     * Redirect to the production Bookmark Player page with preserved query parameters.
     */
    public function mount(): RedirectResponse
    {
        return redirect()->to(BookmarkPlayer::getUrl(request()->query()));
    }
}
