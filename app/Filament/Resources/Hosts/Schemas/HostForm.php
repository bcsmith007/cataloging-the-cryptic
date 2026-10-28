<?php

namespace App\Filament\Resources\Hosts\Schemas;

use App\Models\Host;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form schema configuration for Host resource.
 */
class HostForm
{
    /**
     * Configure the form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Host Information')
                    ->columnSpanFull()
                    ->schema(static::schemaComponents()),
            ]);
    }

    /**
     * Get the individual form components for creating or editing a host.
     *
     * Reusable across the standalone HostResource and inline Select modals.
     */
    public static function schemaComponents(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->label('Host Name')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Host::generateSlug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(120)
                    ->unique(ignoreRecord: true),
            ]),

            TextInput::make('avatar_url')
                ->label('Avatar Image URL')
                ->url()
                ->unwrapRedirects()
                ->maxLength(512),
        ];
    }
}
