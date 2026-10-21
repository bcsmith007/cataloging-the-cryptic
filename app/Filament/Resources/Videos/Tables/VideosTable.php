<?php

namespace App\Filament\Resources\Videos\Tables;

use App\Models\Video;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table configuration for the Video resource.
 *
 * Displays video status, hosts, linked puzzle counts, bookmark counts,
 * and review/tagging filters.
 */
class VideosTable
{
    /**
     * Configure the table.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

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

                TextColumn::make('patreonPost.title')
                    ->label('Patreon Post')
                    ->limit(25)
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('hosts.name')
                    ->label('Hosts')
                    ->badge()
                    ->separator(', '),

                TextColumn::make('puzzles_count')
                    ->counts('puzzles')
                    ->label('Puzzles')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'warning')
                    ->sortable(),

                TextColumn::make('bookmarks_count')
                    ->counts('bookmarks')
                    ->label('Bookmarks')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'warning')
                    ->sortable(),

                TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state): string => Video::formatSecondsToHms($state))
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('Published')
                    ->date('M j, Y')
                    ->sortable(),

                IconColumn::make('is_finalized')
                    ->label('Finalized')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                TernaryFilter::make('is_finalized')
                    ->label('Finalized Status')
                    ->placeholder('All videos')
                    ->trueLabel('Finalized only')
                    ->falseLabel('Unfinalized only'),

                Filter::make('needs_review')
                    ->label('Needs Review / Untagged (Missing Puzzles or Bookmarks)')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $q) {
                        $q->whereDoesntHave('puzzles')->orWhereDoesntHave('bookmarks');
                    })),

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

                SelectFilter::make('patreon_post_id')
                    ->label('Patreon Post')
                    ->relationship('patreonPost', 'title'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
