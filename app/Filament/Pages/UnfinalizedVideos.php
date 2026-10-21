<?php

namespace App\Filament\Pages;

use App\Models\Video;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Dedicated administrative page listing all videos pending review and finalization.
 *
 * Videos remain on this review queue until an administrator has verified all metadata,
 * linked puzzles, and timestamp bookmarks, and tagged the video as finalized.
 */
class UnfinalizedVideos extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Unfinalized Videos';

    protected static ?string $title = 'Unfinalized Videos';

    protected static ?string $slug = 'unfinalized-videos';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.unfinalized-videos';

    /**
     * Get the badge displayed next to the navigation item.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Video::where('is_finalized', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the color of the navigation badge.
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    /**
     * Get header actions for this page.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
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

                    return redirect()->to(route('filament.admin.resources.videos.edit', ['record' => $video]));
                }),
            Action::make('all_videos')
                ->label('All Videos')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(fn (): string => route('filament.admin.resources.videos.index')),
        ];
    }

    /**
     * Configure the table of unfinalized videos.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(Video::query()->where('is_finalized', false))
            ->recordUrl(fn (Video $record): string => route('filament.admin.resources.videos.edit', ['record' => $record]))
            ->columns([
                TextColumn::make('title')
                    ->label('Video Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->url(fn (Video $record): string => route('filament.admin.resources.videos.edit', ['record' => $record])),

                TextColumn::make('access_level')
                    ->label('Access')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'exclusive' => 'Patron Exclusive',
                        'public' => 'Public',
                        default => ucfirst($state ?? 'public'),
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'exclusive' => 'danger',
                        'public' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('video_type')
                    ->label('Video Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'youtube' => 'YouTube',
                        'youtube_short' => 'YouTube Short',
                        'live_stream' => 'Live Stream',
                        'patreon' => 'Patreon',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'youtube' => 'info',
                        'youtube_short' => 'warning',
                        'live_stream' => 'info',
                        'patreon' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Filter::make('missing_puzzles')
                    ->label('Missing Linked Puzzles')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('puzzles')),

                Filter::make('missing_bookmarks')
                    ->label('Missing Bookmarks / Timestamps')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('bookmarks')),

                SelectFilter::make('access_level')
                    ->label('Access Level')
                    ->options([
                        'public' => 'Public',
                        'exclusive' => 'Patron Exclusive',
                    ]),

                SelectFilter::make('video_type')
                    ->label('Video Type')
                    ->options([
                        'youtube' => 'YouTube',
                        'youtube_short' => 'YouTube Short',
                        'live_stream' => 'Live Stream',
                    ]),
            ])
            ->recordActions([
                Action::make('markFinalized')
                    ->label('Mark Finalized')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Video as Finalized')
                    ->modalDescription('Confirm that this video has been reviewed and all tagging and timestamps are complete.')
                    ->action(function (Video $record): void {
                        $record->update(['is_finalized' => true]);

                        Notification::make()
                            ->title('Video Finalized')
                            ->body("Video '{$record->title}' has been marked as finalized.")
                            ->success()
                            ->send();
                    }),

                Action::make('edit')
                    ->label('Edit & Review')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Video $record): string => route('filament.admin.resources.videos.edit', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markFinalized')
                        ->label('Mark Selected as Finalized')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected Videos as Finalized')
                        ->modalDescription('Confirm that all selected videos have been reviewed and their tagging is complete.')
                        ->action(function (Collection $records): void {
                            $records->each(fn (Video $v) => $v->update(['is_finalized' => true]));

                            Notification::make()
                                ->title('Videos Finalized')
                                ->body(count($records).' videos marked as finalized.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
