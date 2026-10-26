<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form schema configuration for Category resource.
 */
class CategoryForm
{
    /**
     * Configure the form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Category Information')
                    ->columnSpanFull()
                    ->schema(static::schemaComponents()),
            ]);
    }

    /**
     * Get the individual form components for creating or editing a category.
     *
     * Reusable across the standalone CategoryResource and inline Select modals.
     */
    public static function schemaComponents(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->label('Category Name')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Category::generateSlug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(120)
                    ->unique(ignoreRecord: true),
            ]),

            MarkdownEditor::make('description')
                ->label('Description')
                ->toolbarButtons([
                    'bold',
                    'italic',
                    'strike',
                    'link',
                    'heading',
                    'blockquote',
                    'bulletList',
                    'orderedList',
                    'table',
                    'undo',
                    'redo',
                ]),
        ];
    }
}
