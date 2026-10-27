<?php

namespace App\Filament\Resources\Videos\Schemas;

use App\Filament\Pages\BookmarkPlayer;
use App\Filament\Resources\Hosts\Schemas\HostForm;
use App\Filament\Resources\PatreonPosts\RelationManagers\VideosRelationManager;
use App\Filament\Resources\PatreonPosts\Schemas\PatreonPostForm;
use App\Models\Bookmark;
use App\Models\Host;
use App\Models\Video;
use App\Services\YouTubeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Form schema configuration for the Video resource.
 *
 * Implements fields for video metadata, access levels, host attachments,
 * a timecode bookmarks repeater, and automated YouTube metadata ingestion.
 */
class VideoForm
{
    /**
     * Configure the form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Video Source and Metadata')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('video_type')
                                ->label('Video Type')
                                ->options([
                                    'youtube' => 'YouTube',
                                    'youtube_short' => 'YouTube Short',
                                    'live_stream' => 'Live Stream',
                                ])
                                ->default('youtube')
                                ->required()
                                ->live(),

                            Select::make('access_level')
                                ->label('Access Level')
                                ->options([
                                    'public' => 'Public',
                                    'exclusive' => 'Patron Exclusive',
                                ])
                                ->default(fn ($livewire) => $livewire instanceof VideosRelationManager ? 'exclusive' : 'public')
                                ->required()
                                ->live(),

                            Select::make('patreon_post_id')
                                ->label('Patreon Post')
                                ->relationship('patreonPost', 'title')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->visible(fn ($livewire) => ! ($livewire instanceof VideosRelationManager))
                                ->createOptionForm(PatreonPostForm::schemaComponents())
                                ->createOptionAction(function (Action $action): Action {
                                    return $action
                                        ->modalHeading('Create New Patreon Post')
                                        ->modalWidth('2xl');
                                })
                                ->helperText('Parent Patreon publication if attached to a Patreon release.'),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('youtube_video_id')
                                ->label('YouTube Video ID or URL')
                                ->placeholder('e.g. hj-J9oRDHOU, dQw4w9WgXcQ or https://youtube.com/live/...')
                                ->visible(fn (callable $get) => in_array($get('video_type'), ['youtube', 'youtube_short', 'live_stream'], true))
                                ->required(fn (callable $get) => $get('access_level') === 'public')
                                ->live(onBlur: true)
                                ->suffixAction(
                                    Action::make('fetchYouTube')
                                        ->label('Fetch')
                                        ->icon('heroicon-m-arrow-down-tray')
                                        ->action(function ($state, callable $set) {
                                            static::populateFromYouTube($state, $set);
                                        })
                                )
                                ->afterStateUpdated(function (?string $state, callable $set) {
                                    if (filled($state)) {
                                        static::populateFromYouTube($state, $set);
                                    }
                                }),

                            TextInput::make('patreon_post_url')
                                ->label('Direct Patreon URL (Fallback)')
                                ->url()
                                ->maxLength(512)
                                ->helperText('Direct URL if not linked to a Patreon post record.')
                                ->visible(fn (callable $get) => $get('access_level') === 'exclusive' || filled($get('patreon_post_id'))),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('title')
                                ->label('Video Title')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                                    if ($operation === 'create' && filled($state)) {
                                        $set('slug', Video::generateSlug($state));
                                    }
                                }),

                            TextInput::make('slug')
                                ->required()
                                ->maxLength(280)
                                ->unique(ignoreRecord: true),
                        ]),

                        Grid::make(3)->schema([
                            DateTimePicker::make('published_at')
                                ->label('Published Date')
                                ->required()
                                ->default(now()),

                            TextInput::make('duration_seconds')
                                ->label('Duration')
                                ->placeholder('e.g. 1:30:00, 45:00, or 45')
                                ->default('0')
                                ->formatStateUsing(fn ($state) => filled($state) ? Video::formatSecondsToTimecode($state) : '0')
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Bookmark::parseTimecodeToSeconds($state) : 0)
                                ->helperText('Duration (HH:MM:SS or MM:SS, omitting units that do not exist). Stored as total seconds.'),

                            TextInput::make('view_count')
                                ->label('View Count')
                                ->numeric()
                                ->default(0),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('hosts')
                                ->relationship(
                                    name: 'hosts',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => $query->orderFeaturedFirst(),
                                )
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
                                        $labels += Host::whereIn('id', $ids)->pluck('name', 'id')->toArray();
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
                                                $host = Host::firstOrCreate(
                                                    ['name' => $name],
                                                    ['slug' => Host::generateSlug($name)]
                                                );
                                                $resolved[] = $host->id;
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
                                            $host = Host::firstOrCreate(
                                                ['name' => $name],
                                                ['slug' => Host::generateSlug($name)]
                                            );

                                            return $host->id;
                                        }

                                        return (int) $item;
                                    }, $state)));
                                })
                                ->createOptionForm(HostForm::schemaComponents())
                                ->createOptionAction(function (Action $action): Action {
                                    return $action
                                        ->modalHeading('Create New Host')
                                        ->modalWidth('xl');
                                }),

                            TextInput::make('youtube_thumbnail_url')
                                ->label('Thumbnail URL')
                                ->url()
                                ->maxLength(512),
                        ]),

                        Checkbox::make('is_finalized')
                            ->label('Video is Finalized')
                            ->helperText('Check once this video has been reviewed by an admin and all metadata, puzzles, and timestamps are complete.')
                            ->default(false),
                    ]),

                Section::make('Music Information')
                    ->description('Select from previous music tracks or type a new track to credit for this episode.')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('intro_music')
                                ->label('Intro Music')
                                ->placeholder('Select or type new track...')
                                ->searchable()
                                ->nullable()
                                ->options(fn () => static::getMusicOptions())
                                ->getSearchResultsUsing(fn (string $search): array => static::getMusicSearchResults($search))
                                ->getOptionLabelUsing(fn ($value): ?string => filled($value) ? (string) $value : null)
                                ->createOptionForm([
                                    TextInput::make('title')
                                        ->label('Music Track Title / Artist')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->createOptionUsing(fn (array $data): string => trim($data['title'])),

                            Select::make('outro_music')
                                ->label('Outro Music')
                                ->placeholder('Select or type new track...')
                                ->searchable()
                                ->nullable()
                                ->options(fn () => static::getMusicOptions())
                                ->getSearchResultsUsing(fn (string $search): array => static::getMusicSearchResults($search))
                                ->getOptionLabelUsing(fn ($value): ?string => filled($value) ? (string) $value : null)
                                ->createOptionForm([
                                    TextInput::make('title')
                                        ->label('Music Track Title / Artist')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->createOptionUsing(fn (array $data): string => trim($data['title'])),
                        ]),
                    ]),

                Section::make('Bookmarks and Timestamps')
                    ->description(function ($record, Section $component): Htmlable {
                        $video = static::resolveVideoModel($record, $component);
                        if ($video) {
                            $url = BookmarkPlayer::getUrl(['video' => $video->id]);

                            return new HtmlString('Annotate key video moments with timecodes, labels, and puzzle associations. Or <a href="'.e($url).'" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-500 underline hover:text-amber-400">open this video in the Bookmark Player</a>.');
                        }

                        return new HtmlString('Annotate key video moments with timecodes, labels, and puzzle associations.');
                    })
                    ->headerActions([
                        Action::make('openBookmarkPlayer')
                            ->label('Open in Bookmark Player')
                            ->icon('heroicon-m-play-circle')
                            ->color('primary')
                            ->url(function ($record, Action $action): ?string {
                                $video = static::resolveVideoModel($record, $action);

                                return $video ? BookmarkPlayer::getUrl(['video' => $video->id]) : null;
                            })
                            ->openUrlInNewTab()
                            ->visible(function ($record, Action $action): bool {
                                return (bool) static::resolveVideoModel($record, $action);
                            }),
                    ])
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('bookmarks')
                            ->relationship('bookmarks')
                            ->schema([
                                Grid::make(['default' => 12, 'sm' => 12, 'lg' => 12])->schema([
                                    TextInput::make('timestamp_seconds')
                                        ->label('Timestamp')
                                        ->placeholder('e.g. 1:23:45, 12:30, or 45')
                                        ->required()
                                        ->regex('/^(?:(?:\d{1,2}:)?\d{1,2}:)?\d{1,2}$|^\d+$/')
                                        ->validationMessages([
                                            'regex' => 'The timestamp must be in HH:MM:SS, MM:SS, or seconds format (e.g. 1:23:45, 12:30, or 45).',
                                        ])
                                        ->formatStateUsing(fn ($state): ?string => filled($state) ? (is_numeric($state) ? Bookmark::formatSecondsToTimecode((int) $state) : (string) $state) : null)
                                        ->dehydrateStateUsing(fn ($state): int => Bookmark::parseTimecodeToSeconds($state))
                                        ->columnSpan(['default' => 2, 'sm' => 2, 'lg' => 2]),

                                    Select::make('label')
                                        ->label('Bookmark Label')
                                        ->placeholder('Select or type new label...')
                                        ->searchable()
                                        ->required()
                                        ->columnSpan(['default' => 5, 'sm' => 5, 'lg' => 5])
                                        ->extraAttributes([
                                            'class' => 'bookmark-label-select',
                                        ])
                                        ->options(fn () => static::getBookmarkLabelOptions())
                                        ->getSearchResultsUsing(fn (string $search): array => static::getBookmarkLabelSearchResults($search))
                                        ->getOptionLabelUsing(fn ($value): ?string => filled($value) ? (string) $value : null)
                                        ->createOptionForm([
                                            TextInput::make('label')
                                                ->label('New Bookmark Label')
                                                ->required()
                                                ->maxLength(150),
                                        ])
                                        ->createOptionAction(fn (Action $action) => $action->modalHeading('Create New Bookmark Label')->modalWidth('md'))
                                        ->createOptionUsing(fn (array $data): string => trim($data['label'])),

                                    Select::make('puzzle_id')
                                        ->label('Associated Puzzle (Optional)')
                                        ->placeholder('Select video puzzle...')
                                        ->relationship(
                                            name: 'puzzle',
                                            titleAttribute: 'title',
                                            modifyQueryUsing: function (Builder $query, Select $component) {
                                                $video = $component->getLivewire()->getRecord();

                                                if ($video instanceof Video && $video->exists) {
                                                    $state = $component->getState();
                                                    $query->where(function (Builder $q) use ($video, $state) {
                                                        $q->whereIn('puzzles.id', $video->puzzles()->select('puzzles.id'));
                                                        if (filled($state) && is_numeric($state)) {
                                                            $q->orWhere('puzzles.id', (int) $state);
                                                        }
                                                    })->orderBy('puzzles.title', 'asc');
                                                } else {
                                                    $query->whereRaw('1 = 0');
                                                }
                                            }
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->rules([
                                            fn (Select $component) => function (string $attribute, $value, \Closure $fail) use ($component) {
                                                if (blank($value)) {
                                                    return;
                                                }

                                                $video = $component->getLivewire()->getRecord();
                                                if ($video instanceof Video && $video->exists) {
                                                    $isTagged = $video->puzzles()->where('puzzles.id', $value)->exists();
                                                    if (! $isTagged) {
                                                        $fail('The selected puzzle must be tagged to this video.');
                                                    }
                                                }
                                            },
                                        ])
                                        ->columnSpan(['default' => 5, 'sm' => 5, 'lg' => 5])
                                        ->extraAttributes([
                                            'class' => 'bookmark-label-select',
                                        ]),
                                ]),
                            ])
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->addAction(fn (Action $action) => $action
                                ->label('')
                                ->icon('heroicon-m-plus')
                                ->iconButton()
                                ->color('gray')
                                ->tooltip('Add Bookmark')
                            )
                            ->addActionAlignment(Alignment::Center)
                            ->deleteAction(fn (Action $action) => $action
                                ->iconButton()
                                ->tooltip('Delete Bookmark')
                            )
                            ->extraAttributes([
                                'class' => 'bookmark-repeater',
                            ]),
                    ]),
            ]);
    }

    /**
     * Populate form fields with metadata queried from YouTube.
     *
     * @param  string|null  $input  Video ID or URL.
     * @param  callable  $set  Form state setter.
     */
    protected static function populateFromYouTube(?string $input, callable $set): void
    {
        if (! filled($input)) {
            return;
        }

        $service = app(YouTubeService::class);
        $data = $service->getVideoDetails($input);

        if (! $data) {
            Notification::make()
                ->title('Unable to fetch YouTube video')
                ->body('Could not locate video information for the provided ID or URL.')
                ->danger()
                ->send();

            return;
        }

        // Store clean 11-character video ID
        $set('youtube_video_id', $data['video_id']);

        // Auto-set video type if URL/metadata indicates live stream or short
        if (! empty($data['detected_video_type'])) {
            $set('video_type', $data['detected_video_type']);
        }

        if (! empty($data['title'])) {
            $set('title', $data['title']);
            $set('slug', Video::generateSlug($data['title']));
        }

        if (! empty($data['published_at'])) {
            $set('published_at', $data['published_at']);
        }

        if ($data['duration_seconds'] > 0) {
            $set('duration_seconds', Video::formatSecondsToHms($data['duration_seconds']));
        }

        if ($data['view_count'] > 0) {
            $set('view_count', $data['view_count']);
        }

        if (! empty($data['youtube_thumbnail_url'])) {
            $set('youtube_thumbnail_url', $data['youtube_thumbnail_url']);
        }

        if ($data['source'] === 'api') {
            Notification::make()
                ->title('YouTube Metadata Fetched')
                ->body('Loaded title, published date, duration, and view count from YouTube Data API.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('YouTube Title and Thumbnail Fetched')
                ->body('Loaded title via YouTube oEmbed. To automatically pull duration and view counts, add YOUTUBE_API_KEY to your .env file.')
                ->info()
                ->send();
        }
    }

    /**
     * Get distinct list of all previously recorded intro and outro music tracks.
     *
     * @return array<string, string>
     */
    public static function getMusicOptions(): array
    {
        $intro = Video::query()
            ->whereNotNull('intro_music')
            ->where('intro_music', '!=', '')
            ->distinct()
            ->pluck('intro_music')
            ->toArray();

        $outro = Video::query()
            ->whereNotNull('outro_music')
            ->where('outro_music', '!=', '')
            ->distinct()
            ->pluck('outro_music')
            ->toArray();

        $combined = array_unique(array_filter(array_merge($intro, $outro)));
        natcasesort($combined);

        return ! empty($combined) ? array_combine($combined, $combined) : [];
    }

    /**
     * Search existing music tracks and offer an on-the-fly create option if no exact match.
     *
     * @return array<string, string>
     */
    public static function getMusicSearchResults(string $search): array
    {
        $options = static::getMusicOptions();
        $trimmed = trim($search);

        if ($trimmed === '') {
            return $options;
        }

        $matches = array_filter(
            $options,
            fn ($label) => str_contains(strtolower($label), strtolower($trimmed))
        );

        $hasExact = false;
        foreach ($matches as $label) {
            if (strcasecmp($label, $trimmed) === 0) {
                $hasExact = true;
                break;
            }
        }

        if (! $hasExact) {
            $matches = [$trimmed => $trimmed] + $matches;
        }

        return $matches;
    }

    /**
     * Retrieve unique bookmark labels used across all videos for quick selection.
     *
     * @return array<string, string>
     */
    public static function getBookmarkLabelOptions(): array
    {
        $dbLabels = Bookmark::query()
            ->whereNotNull('label')
            ->where('label', '!=', '')
            ->distinct()
            ->pluck('label')
            ->toArray();

        $defaultLabels = [
            'Introduction and Overview',
            'Rules Explanation',
            'Initial Break-in',
            'Middle Game Deductions',
            'The Epiphany',
            'End Game / Cleanup',
            'Puzzle 1',
            'Puzzle 2',
            'Puzzle 3',
            'Outro and Wrap-up',
        ];

        $combined = array_unique(array_filter(array_merge($defaultLabels, $dbLabels)));
        natcasesort($combined);

        return ! empty($combined) ? array_combine($combined, $combined) : [];
    }

    /**
     * Search existing bookmark labels and offer an on-the-fly create option if no exact match.
     *
     * @return array<string, string>
     */
    public static function getBookmarkLabelSearchResults(string $search): array
    {
        $options = static::getBookmarkLabelOptions();
        $trimmed = trim($search);

        if ($trimmed === '') {
            return $options;
        }

        $matches = array_filter(
            $options,
            fn ($label) => str_contains(strtolower($label), strtolower($trimmed))
        );

        $hasExact = false;
        foreach ($matches as $label) {
            if (strcasecmp($label, $trimmed) === 0) {
                $hasExact = true;
                break;
            }
        }

        if (! $hasExact) {
            $matches = [$trimmed => 'Add "'.$trimmed.'"'] + $matches;
        }

        return $matches;
    }

    /**
     * Resolve the active Video model instance from the form record or Livewire context.
     *
     * @param  mixed  $record
     * @param  mixed  $componentOrAction
     */
    protected static function resolveVideoModel($record, $componentOrAction): ?Video
    {
        if ($record instanceof Video && $record->exists) {
            return $record;
        }

        $livewire = method_exists($componentOrAction, 'getLivewire') ? $componentOrAction->getLivewire() : null;
        if ($livewire && method_exists($livewire, 'getRecord')) {
            $candidate = $livewire->getRecord();
            if ($candidate instanceof Video && $candidate->exists) {
                return $candidate;
            }
        }

        return null;
    }
}
