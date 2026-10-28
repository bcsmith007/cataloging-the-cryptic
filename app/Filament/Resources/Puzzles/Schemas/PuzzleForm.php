<?php

namespace App\Filament\Resources\Puzzles\Schemas;

use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Constraints\Schemas\ConstraintForm;
use App\Filament\Resources\LinkTypes\Schemas\LinkTypeForm;
use App\Filament\Resources\Setters\Schemas\SetterForm;
use App\Models\Category;
use App\Models\Constraint;
use App\Models\LinkType;
use App\Models\Puzzle;
use App\Models\PuzzleLink;
use App\Models\Setter;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form schema configuration for the Puzzle resource.
 */
class PuzzleForm
{
    /**
     * Configure the form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Puzzle Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                                        if ($operation === 'create' && filled($state)) {
                                            $set('slug', Puzzle::generateSlug($state));
                                        }
                                    }),

                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(280)
                                    ->unique(ignoreRecord: true),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (Select $component, string $search): array {
                                        $results = $component->getSearchResultsFromRelationship($search);
                                        $trimmed = trim($search);

                                        if ($trimmed !== '') {
                                            $hasExact = false;
                                            foreach ($results as $label) {
                                                if (strcasecmp(trim($label), $trimmed) === 0) {
                                                    $hasExact = true;
                                                    break;
                                                }
                                            }

                                            if (! $hasExact) {
                                                $results = ['create:'.$trimmed => $trimmed] + $results;
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

                                        return Category::where('id', $value)->value('name');
                                    })
                                    ->afterStateUpdated(function (Select $component, $state): void {
                                        if (is_string($state) && str_starts_with($state, 'create:')) {
                                            $name = trim(substr($state, 7));
                                            if ($name !== '') {
                                                $category = Category::firstOrCreate(
                                                    ['name' => $name],
                                                    ['slug' => Category::generateSlug($name)]
                                                );
                                                $component->state($category->id);
                                            }
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if (is_string($state) && str_starts_with($state, 'create:')) {
                                            $name = trim(substr($state, 7));
                                            $category = Category::firstOrCreate(
                                                ['name' => $name],
                                                ['slug' => Category::generateSlug($name)]
                                            );

                                            return $category->id;
                                        }

                                        return filled($state) ? (int) $state : null;
                                    })
                                    ->createOptionForm(CategoryForm::schemaComponents())
                                    ->createOptionAction(function (Action $action): Action {
                                        return $action
                                            ->modalHeading('Create New Category')
                                            ->modalWidth('xl');
                                    }),

                                Select::make('access_level')
                                    ->label('Access Level')
                                    ->options([
                                        'public' => 'Public',
                                        'exclusive' => 'Patron Exclusive',
                                    ])
                                    ->default('public')
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('setters')
                                    ->relationship('setters', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (Select $component, string $search): array {
                                        $results = $component->getSearchResultsFromRelationship($search);
                                        $trimmed = trim($search);

                                        if ($trimmed !== '') {
                                            $hasExact = false;
                                            foreach ($results as $label) {
                                                if (strcasecmp(trim($label), $trimmed) === 0) {
                                                    $hasExact = true;
                                                    break;
                                                }
                                            }

                                            if (! $hasExact) {
                                                $results = ['create:'.$trimmed => $trimmed] + $results;
                                            }
                                        }

                                        return $results;
                                    })
                                    ->getOptionLabelsUsing(function (Select $component, array $values): array {
                                        $labels = [];
                                        $ids = [];

                                        foreach ($values as $value) {
                                            if (is_string($value) && str_starts_with($value, 'create:')) {
                                                $labels[$value] = trim(substr($value, 7));
                                            } else {
                                                $ids[] = $value;
                                            }
                                        }

                                        if (! empty($ids)) {
                                            $labels += Setter::whereIn('id', $ids)->pluck('name', 'id')->toArray();
                                        }

                                        return $labels;
                                    })
                                    ->afterStateUpdated(function (Select $component, $state): void {
                                        if (empty($state) || ! is_array($state)) {
                                            return;
                                        }

                                        $resolved = [];
                                        $changed = false;

                                        foreach ($state as $item) {
                                            if (is_string($item) && str_starts_with($item, 'create:')) {
                                                $name = trim(substr($item, 7));
                                                if ($name !== '') {
                                                    $setter = Setter::firstOrCreate(
                                                        ['name' => $name],
                                                        ['slug' => Setter::generateSlug($name)]
                                                    );
                                                    $resolved[] = $setter->id;
                                                    $changed = true;
                                                }
                                            } else {
                                                $resolved[] = (int) $item;
                                            }
                                        }

                                        if ($changed) {
                                            $component->state(array_values(array_unique($resolved)));
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if (empty($state) || ! is_array($state)) {
                                            return $state;
                                        }

                                        return array_values(array_unique(array_map(function ($item) {
                                            if (is_string($item) && str_starts_with($item, 'create:')) {
                                                $name = trim(substr($item, 7));
                                                $setter = Setter::firstOrCreate(
                                                    ['name' => $name],
                                                    ['slug' => Setter::generateSlug($name)]
                                                );

                                                return $setter->id;
                                            }

                                            return (int) $item;
                                        }, $state)));
                                    })
                                    ->createOptionForm(SetterForm::schemaComponents())
                                    ->createOptionAction(function (Action $action): Action {
                                        return $action
                                            ->modalHeading('Create New Setter')
                                            ->modalWidth('xl');
                                    }),

                                Select::make('constraints')
                                    ->relationship('constraints', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (Select $component, string $search): array {
                                        $results = $component->getSearchResultsFromRelationship($search);
                                        $trimmed = trim($search);

                                        if ($trimmed !== '') {
                                            $hasExact = false;
                                            foreach ($results as $label) {
                                                if (strcasecmp(trim($label), $trimmed) === 0) {
                                                    $hasExact = true;
                                                    break;
                                                }
                                            }

                                            if (! $hasExact) {
                                                $results = ['create:'.$trimmed => $trimmed] + $results;
                                            }
                                        }

                                        return $results;
                                    })
                                    ->getOptionLabelsUsing(function (Select $component, array $values): array {
                                        $labels = [];
                                        $ids = [];

                                        foreach ($values as $value) {
                                            if (is_string($value) && str_starts_with($value, 'create:')) {
                                                $labels[$value] = trim(substr($value, 7));
                                            } else {
                                                $ids[] = $value;
                                            }
                                        }

                                        if (! empty($ids)) {
                                            $labels += Constraint::whereIn('id', $ids)->pluck('name', 'id')->toArray();
                                        }

                                        return $labels;
                                    })
                                    ->afterStateUpdated(function (Select $component, $state): void {
                                        if (empty($state) || ! is_array($state)) {
                                            return;
                                        }

                                        $resolved = [];
                                        $changed = false;

                                        foreach ($state as $item) {
                                            if (is_string($item) && str_starts_with($item, 'create:')) {
                                                $name = trim(substr($item, 7));
                                                if ($name !== '') {
                                                    $constraint = Constraint::firstOrCreate(
                                                        ['name' => $name],
                                                        ['slug' => Constraint::generateSlug($name)]
                                                    );
                                                    $resolved[] = $constraint->id;
                                                    $changed = true;
                                                }
                                            } else {
                                                $resolved[] = (int) $item;
                                            }
                                        }

                                        if ($changed) {
                                            $component->state(array_values(array_unique($resolved)));
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if (empty($state) || ! is_array($state)) {
                                            return $state;
                                        }

                                        return array_values(array_unique(array_map(function ($item) {
                                            if (is_string($item) && str_starts_with($item, 'create:')) {
                                                $name = trim(substr($item, 7));
                                                $constraint = Constraint::firstOrCreate(
                                                    ['name' => $name],
                                                    ['slug' => Constraint::generateSlug($name)]
                                                );

                                                return $constraint->id;
                                            }

                                            return (int) $item;
                                        }, $state)));
                                    })
                                    ->createOptionForm(ConstraintForm::schemaComponents())
                                    ->createOptionAction(function (Action $action): Action {
                                        return $action
                                            ->modalHeading('Create New Constraint Variant')
                                            ->modalWidth('xl');
                                    }),
                            ]),

                        Repeater::make('links')
                            ->relationship('links')
                            ->label('Puzzle Links')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('link_type')
                                            ->label('Link Type')
                                            ->options(fn () => PuzzleLink::typeOptions())
                                            ->default('sudokupad')
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
                                                    ?? PuzzleLink::typeOptions()[$value]
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
                                            })
                                            ->columnSpan(1),

                                        TextInput::make('url')
                                            ->label('Link URL')
                                            ->url()
                                            ->unwrapRedirects()
                                            ->required()
                                            ->maxLength(1024)
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Puzzle Link')
                            ->orderColumn('order_column')
                            ->reorderableWithButtons()
                            ->collapsible(),

                        Textarea::make('rules_raw')
                            ->label('Rules (Raw Text)')
                            ->rows(6),
                    ]),
            ]);
    }
}
