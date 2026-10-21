<?php

namespace App\Filament\Resources\Puzzles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Table configuration for the Puzzle resource.
 */
class PuzzlesTable
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
                    ->weight('bold'),

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

                TextColumn::make('category.name')
                    ->badge()
                    ->sortable(),

                TextColumn::make('setters.name')
                    ->label('Setters')
                    ->badge()
                    ->separator(', '),

                TextColumn::make('constraints.name')
                    ->label('Constraints')
                    ->badge()
                    ->color('gray')
                    ->separator(', '),

                TextColumn::make('difficulty_score')
                    ->label('Diff.')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('access_level')
                    ->label('Access Level')
                    ->options([
                        'public' => 'Public',
                        'exclusive' => 'Patron Exclusive',
                    ]),
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
                SelectFilter::make('constraints')
                    ->relationship('constraints', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('7xl'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
