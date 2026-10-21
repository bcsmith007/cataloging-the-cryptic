<?php

namespace App\Filament\Resources\LinkTypes\Schemas;

use App\Models\LinkType;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form schema configuration for LinkType resource.
 */
class LinkTypeForm
{
    /**
     * Configure the form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Link Type Information')
                    ->columnSpanFull()
                    ->schema(static::schemaComponents()),
            ]);
    }

    /**
     * Get the individual form components for creating or editing a link type.
     *
     * Reusable across the standalone LinkTypeResource and inline Select modals.
     */
    public static function schemaComponents(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->label('Link Type Name')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', LinkType::generateSlug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->helperText('Unique key identifier (auto-generated from name).'),
            ]),
        ];
    }
}
