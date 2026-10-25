<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Table configuration for the User resource.
 */
class UsersTable
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

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('timezone')
                    ->label('Timezone')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('Role')
                    ->placeholder('All Users')
                    ->trueLabel('Admins Only')
                    ->falseLabel('Standard Users Only'),
            ])
            ->recordActions([
                Action::make('toggleAdmin')
                    ->label(fn (User $record): string => $record->is_admin ? 'Revoke Admin' : 'Make Admin')
                    ->icon(fn (User $record): string => $record->is_admin ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check')
                    ->color(fn (User $record): string => $record->is_admin ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->is_admin ? 'Revoke Administrator Rights' : 'Grant Administrator Rights')
                    ->modalDescription(fn (User $record): string => $record->is_admin
                        ? "Are you sure you want to revoke admin privileges for {$record->name}? They will no longer have access to the admin panel."
                        : "Grant admin privileges to {$record->name}? They will gain full access to the admin panel.")
                    ->visible(fn (User $record): bool => auth()->id() !== $record->id)
                    ->action(fn (User $record) => $record->update(['is_admin' => ! $record->is_admin])),

                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => auth()->id() !== $record->id),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
