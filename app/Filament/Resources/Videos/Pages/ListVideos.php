<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideoResource;
use App\Models\Video;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * List page for Video resource with quick review tabs.
 */
class ListVideos extends ListRecords
{
    protected static string $resource = VideoResource::class;

    /**
     * Get header actions for the list page.
     *
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        $unfinalizedCount = Video::where('is_finalized', false)->count();

        return [
            Action::make('random_unfinalized')
                ->label('Random Unfinalized')
                ->icon('heroicon-m-sparkles')
                ->color('info')
                ->action(function () {
                    $video = Video::where('is_finalized', false)->inRandomOrder()->first();

                    if (! $video) {
                        Notification::make()
                            ->title('No unfinalized videos')
                            ->body('All videos in the catalog are currently finalized.')
                            ->info()
                            ->send();

                        return null;
                    }

                    return redirect()->to(VideoResource::getUrl('edit', ['record' => $video]));
                }),
            Action::make('unfinalized')
                ->label('Unfinalized Queue')
                ->icon('heroicon-m-clock')
                ->color('warning')
                ->badge($unfinalizedCount > 0 ? (string) $unfinalizedCount : null)
                ->url(fn (): string => route('filament.admin.pages.unfinalized-videos')),
            CreateAction::make()->label('Add Video'),
        ];
    }

    /**
     * Define view tabs including dedicated 'Unfinalized' and 'Needs Review / Untagged' tabs.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $unfinalizedCount = Video::where('is_finalized', false)->count();
        $needsReviewCount = Video::where(function (Builder $query) {
            $query->whereDoesntHave('puzzles')->orWhereDoesntHave('bookmarks');
        })->count();

        return [
            'all' => Tab::make('All Videos'),
            'unfinalized' => Tab::make('Unfinalized')
                ->badge($unfinalizedCount > 0 ? (string) $unfinalizedCount : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_finalized', false)),
            'needs_review' => Tab::make('Needs Review / Untagged')
                ->badge($needsReviewCount > 0 ? (string) $needsReviewCount : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function (Builder $q) {
                    $q->whereDoesntHave('puzzles')->orWhereDoesntHave('bookmarks');
                })),
            'public' => Tab::make('Public Videos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('access_level', 'public')),
            'exclusive' => Tab::make('Patron Exclusive')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('access_level', 'exclusive')),
            'live_stream' => Tab::make('Live Streams')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('video_type', 'live_stream')),
            'youtube_short' => Tab::make('Shorts')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('video_type', 'youtube_short')),
        ];
    }
}
