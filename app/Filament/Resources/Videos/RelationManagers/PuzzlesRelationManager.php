<?php

namespace App\Filament\Resources\Videos\RelationManagers;

use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Constraints\Schemas\ConstraintForm;
use App\Filament\Resources\LinkTypes\Schemas\LinkTypeForm;
use App\Filament\Resources\Setters\Schemas\SetterForm;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Constraint;
use App\Models\LinkType;
use App\Models\Puzzle;
use App\Models\PuzzleLink;
use App\Models\Setter;
use App\Models\Video;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Relation manager for puzzles contained within a video.
 *
 * Manages video_puzzles pivot data: ordering, start timestamp, and solve duration,
 * as well as full puzzle metadata when creating or updating puzzles directly on the video.
 */
class PuzzlesRelationManager extends RelationManager
{
    protected static string $relationship = 'puzzles';

    /**
     * Configure form schema for creating or editing video puzzles and pivot timing.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Video Solve Timing and Order')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('order_in_video')
                            ->label('Order in Video')
                            ->numeric()
                            ->default(fn () => ($this->getOwnerRecord()->puzzles()->count() ?? 0) + 1)
                            ->required(),

                        TextInput::make('start_seconds')
                            ->label('Start Time in Video')
                            ->placeholder('e.g. 1:23:45, 12:30, or 45')
                            ->formatStateUsing(fn ($state) => filled($state) ? (is_numeric($state) ? Video::formatSecondsToTimecode((int) $state) : (string) $state) : null)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Bookmark::parseTimecodeToSeconds($state) : null)
                            ->helperText('Timestamp (HH:MM:SS or MM:SS, omitting units that do not exist) when the puzzle solve begins (saved as seconds).'),

                        TextInput::make('solve_duration_seconds')
                            ->label('Solve Duration')
                            ->placeholder('e.g. 1:23:45, 12:30, or 45')
                            ->formatStateUsing(fn ($state) => filled($state) ? (is_numeric($state) ? Video::formatSecondsToTimecode((int) $state) : (string) $state) : null)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Bookmark::parseTimecodeToSeconds($state) : null)
                            ->helperText('Time taken (HH:MM:SS or MM:SS, omitting units that do not exist) to solve this puzzle (saved as seconds).'),
                    ]),

                Section::make('Puzzle Details')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Puzzle Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                                        if ($operation === 'create' && filled($state)) {
                                            $set('slug', Puzzle::generateSlug($state));
                                        }
                                    }),

                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->maxLength(280)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Unique URL slug (auto-generated from title, can be customized).'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->default(fn () => Category::where('slug', 'variant-sudoku')->value('id') ?? Category::query()->value('id'))
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
                                    ->default(fn () => $this->getOwnerRecord()?->isExclusive() ? 'exclusive' : 'public')
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('setters')
                                    ->label('Setters')
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
                                    ->label('Constraints / Rulesets')
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
                            ->rows(5),
                    ]),
            ]);
    }

    /**
     * Configure relation table for video puzzles.
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('video_puzzles.order_in_video', 'asc')
            ->columns([
                TextColumn::make('order_in_video')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
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
                    ->badge(),

                TextColumn::make('setters.name')
                    ->badge()
                    ->separator(', '),

                TextColumn::make('start_seconds')
                    ->label('Start')
                    ->formatStateUsing(fn ($state): string => $state !== null ? Video::formatSecondsToHms($state) : '-'),

                TextColumn::make('solve_duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state): string => $state !== null ? Video::formatSecondsToHms($state) : '-'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->modalWidth('2xl')
                    ->recordSelect(fn (Select $select) => $select->optionsLimit(500))
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->select('puzzles.*')->reorder()->orderBy('puzzles.title', 'asc'))
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('order_in_video')
                            ->label('Order in Video')
                            ->numeric()
                            ->default(fn () => ($this->getOwnerRecord()->puzzles()->count() ?? 0) + 1)
                            ->required(),
                        TextInput::make('start_seconds')
                            ->label('Start Timestamp')
                            ->placeholder('e.g. 1:23:45, 12:30, or 45')
                            ->formatStateUsing(fn ($state) => filled($state) ? (is_numeric($state) ? Video::formatSecondsToTimecode((int) $state) : (string) $state) : null)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Bookmark::parseTimecodeToSeconds($state) : null)
                            ->helperText('Timestamp (HH:MM:SS or MM:SS, omitting units that do not exist) when the puzzle solve begins (saved as seconds).'),
                        TextInput::make('solve_duration_seconds')
                            ->label('Solve Duration')
                            ->placeholder('e.g. 1:23:45, 12:30, or 45')
                            ->formatStateUsing(fn ($state) => filled($state) ? (is_numeric($state) ? Video::formatSecondsToTimecode((int) $state) : (string) $state) : null)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Bookmark::parseTimecodeToSeconds($state) : null)
                            ->helperText('Time taken (HH:MM:SS or MM:SS, omitting units that do not exist) to solve this puzzle (saved as seconds).'),
                    ]),
                CreateAction::make()
                    ->modalHeading('Add Puzzle to Video')
                    ->modalWidth('7xl'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Puzzle Details')
                    ->modalWidth('7xl'),
                DetachAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
