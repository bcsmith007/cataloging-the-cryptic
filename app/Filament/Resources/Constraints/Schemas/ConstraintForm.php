<?php

namespace App\Filament\Resources\Constraints\Schemas;

use App\Models\Constraint;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form schema configuration for Constraint (Variant) resource.
 */
class ConstraintForm
{
    /**
     * Configure the form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Constraint Variant Information')
                    ->columnSpanFull()
                    ->schema(static::schemaComponents()),
            ]);
    }

    /**
     * Get the individual form components for creating or editing a constraint.
     *
     * Reusable across the standalone ConstraintResource and inline Select modals.
     */
    public static function schemaComponents(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->label('Constraint Name')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Constraint::generateSlug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(120)
                    ->unique(ignoreRecord: true),
            ]),

            Textarea::make('description')
                ->label('Description / Rules')
                ->rows(3),
        ];
    }
}
