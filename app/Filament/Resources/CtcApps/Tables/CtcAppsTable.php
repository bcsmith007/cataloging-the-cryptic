<?php

namespace App\Filament\Resources\CtcApps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CtcAppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('developer')
                    ->label('Developer')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('badge')
                    ->label('Badge')
                    ->badge()
                    ->color('warning')
                    ->placeholder('-'),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('apple_store_url')
                    ->label('Apple')
                    ->state(fn ($record) => filled($record->apple_store_url) ? 'Yes' : '-')
                    ->toggleable(),

                TextColumn::make('google_play_url')
                    ->label('Google')
                    ->state(fn ($record) => filled($record->google_play_url) ? 'Yes' : '-')
                    ->toggleable(),

                TextColumn::make('steam_url')
                    ->label('Steam')
                    ->state(fn ($record) => filled($record->steam_url) ? 'Yes' : '-')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
