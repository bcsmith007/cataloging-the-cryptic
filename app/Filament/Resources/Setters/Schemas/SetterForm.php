<?php

namespace App\Filament\Resources\Setters\Schemas;

use App\Filament\Resources\LinkTypes\Schemas\LinkTypeForm;
use App\Models\LinkType;
use App\Models\Setter;
use App\Models\SetterLink;
use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form schema configuration for Setter resource.
 */
class SetterForm
{
    /**
     * Configure the form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Setter Information')
                    ->columnSpanFull()
                    ->schema(static::schemaComponents(withRelationships: true)),
            ]);
    }

    /**
     * Get the individual form components for creating or editing a setter.
     *
     * Reusable across the standalone SetterResource and inline Select modals.
     */
    public static function schemaComponents(bool $withRelationships = false): array
    {
        $components = [
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->label('Setter Name')
                    ->required()
                    ->maxLength(150)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Setter::generateSlug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(180)
                    ->unique(ignoreRecord: true),
            ]),

            MarkdownEditor::make('bio')
                ->label('Biography / Notes')
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

        if ($withRelationships) {
            $components[] = Repeater::make('links')
                ->relationship('links')
                ->label('External Links')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('link_type')
                            ->label('Link Type')
                            ->options(fn () => SetterLink::typeOptions())
                            ->default('patreon')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (Select $component, string $search): array {
                                $results = LinkType::query()
                                    ->where('name', 'ilike', "%{$search}%")
                                    ->orWhere('slug', 'ilike', "%{$search}%")
                                    ->pluck('name', 'slug')
                                    ->toArray();
                                $trimmed = trim($search);

                                if ($trimmed !== '') {
                                    $hasExact = false;
                                    foreach ($results as $slug => $label) {
                                        if (strcasecmp(trim($label), $trimmed) === 0 || strcasecmp(trim($slug), $trimmed) === 0) {
                                            $hasExact = true;
                                            break;
                                        }
                                    }

                                    if (! $hasExact) {
                                        $results = ['create:'.$trimmed => 'Add "'.$trimmed.'"'] + $results;
                                    }
                                }

                                return $results;
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                if (blank($value)) {
                                    return null;
                                }

                                if (is_string($value) && str_starts_with($value, 'create:')) {
                                    return trim(substr($value, 7));
                                }

                                return LinkType::where('slug', $value)->value('name')
                                    ?? SetterLink::typeOptions()[$value]
                                    ?? ucwords(str_replace(['_', '-'], ' ', (string) $value));
                            })
                            ->afterStateUpdated(function (Select $component, $state): void {
                                if (is_string($state) && str_starts_with($state, 'create:')) {
                                    $name = trim(substr($state, 7));
                                    if ($name !== '') {
                                        $type = LinkType::firstOrCreate(
                                            ['slug' => LinkType::generateSlug($name)],
                                            ['name' => $name]
                                        );
                                        $component->state($type->slug);
                                    }
                                }
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (is_string($state) && str_starts_with($state, 'create:')) {
                                    $name = trim(substr($state, 7));
                                    $type = LinkType::firstOrCreate(
                                        ['slug' => LinkType::generateSlug($name)],
                                        ['name' => $name]
                                    );

                                    return $type->slug;
                                }

                                return $state;
                            })
                            ->createOptionForm(LinkTypeForm::schemaComponents())
                            ->createOptionAction(function (Action $action): Action {
                                return $action
                                    ->modalHeading('Create New Link Type')
                                    ->modalWidth('md');
                            })
                            ->createOptionUsing(function (array $data): string {
                                $name = trim($data['name']);
                                $slug = ! empty($data['slug']) ? $data['slug'] : LinkType::generateSlug($name);
                                $type = LinkType::firstOrCreate(
                                    ['slug' => $slug],
                                    ['name' => $name]
                                );

                                return $type->slug;
                            }),

                        TextInput::make('url')
                            ->label('Link URL')
                            ->url()
                            ->required()
                            ->maxLength(1024),

                        TextInput::make('title')
                            ->label('Custom Label (Optional)')
                            ->placeholder('Defaults to link type')
                            ->maxLength(150),
                    ]),
                ])
                ->defaultItems(0)
                ->addActionLabel('Add Link')
                ->orderColumn('order_column')
                ->reorderableWithButtons()
                ->collapsible();
        }

        return $components;
    }
}
