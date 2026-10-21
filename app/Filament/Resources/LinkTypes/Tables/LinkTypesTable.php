<?php

namespace App\Filament\Resources\LinkTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Table configuration for LinkType resource.
 */
class LinkTypesTable
{
    /**
     * Configure the table.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('puzzle_links_count')
                    ->counts('puzzleLinks')
                    ->label('Links Count')
                    ->badge()
                    ->color('info')
                    ->sortable(),
            ])
            ->defaultSort('name', 'asc')
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
