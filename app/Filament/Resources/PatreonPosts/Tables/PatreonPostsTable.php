<?php

namespace App\Filament\Resources\PatreonPosts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Table configuration for the PatreonPost resource.
 */
class PatreonPostsTable
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

                TextColumn::make('published_at')
                    ->label('Published Date')
                    ->date('M j, Y')
                    ->sortable(),

                TextColumn::make('videos_count')
                    ->counts('videos')
                    ->label('Total Videos')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('exclusive_videos_count')
                    ->counts('exclusiveVideos')
                    ->label('Exclusive Videos')
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('public_videos_count')
                    ->counts('publicVideos')
                    ->label('Public Videos')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('patreon_url')
                    ->label('Patreon Link')
                    ->limit(40)
                    ->url(fn ($record): string => $record->patreon_url, true),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updater.name')
                    ->label('Updated By')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
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
