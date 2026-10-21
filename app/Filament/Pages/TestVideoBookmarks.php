<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Videos\Schemas\VideoForm;
use App\Models\Bookmark;
use App\Models\Puzzle;
use App\Models\Video;
use App\Services\YouTubeService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Test page for real-time video playback and automatic bookmark timestamp capture.
 *
 * Allows playing a selected video (or any custom YouTube video) at the top of the page,
 * capturing the player's exact playback position when clicking the repeater's add button (+),
 * and testing real-time synchronization between the video and bookmark timestamps.
 */
class TestVideoBookmarks extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static ?string $navigationLabel = 'Bookmark Player Test';

    protected static ?string $title = 'Video Bookmark Player (Test Page)';

    protected static ?string $slug = 'test-video-bookmarks';

    protected static string|UnitEnum|null $navigationGroup = 'Testing & Tools';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.test-video-bookmarks';

    public ?int $selectedVideoId = null;

    public string $youtubeVideoId = '7lP9x9a_B0M';

    public string $videoTitle = 'The Miracle Sudoku';

    public string $customYoutubeInput = '';

    /**
     * Form state containing the bookmarks array.
     *
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Initialize the page with the requested video, latest library video, or sample video.
     */
    public function mount(?int $video = null): void
    {
        $requestedId = $video ?: request()->query('video') ?: request()->query('video_id');
        $videoModel = $requestedId ? Video::find($requestedId) : null;

        if (! $videoModel) {
            $videoModel = Video::whereNotNull('youtube_video_id')->orderByDesc('published_at')->first();
        }

        if ($videoModel) {
            $this->selectedVideoId = $videoModel->id;
            $rawId = $videoModel->youtube_video_id ?: '7lP9x9a_B0M';
            $this->youtubeVideoId = YouTubeService::extractVideoId($rawId) ?: $rawId;
            $this->videoTitle = $videoModel->title;
            $this->loadVideoBookmarks($videoModel);
        } else {
            $this->youtubeVideoId = '7lP9x9a_B0M';
            $this->videoTitle = 'The Miracle Sudoku (Sample)';
            $this->data = ['bookmarks' => []];
        }

        $this->form->fill($this->data);
    }

    /**
     * Load bookmarks from an existing Video model into form state.
     */
    protected function loadVideoBookmarks(Video $video): void
    {
        $video->loadMissing(['bookmarks', 'puzzles']);

        $bookmarks = $video->bookmarks
            ->sortByDesc('timestamp_seconds')
            ->values()
            ->map(function (Bookmark $bookmark) {
                return [
                    'timestamp_seconds' => Bookmark::formatSecondsToTimecode($bookmark->timestamp_seconds),
                    'label' => $bookmark->label,
                    'puzzle_id' => $bookmark->puzzle_id,
                ];
            })->toArray();

        $this->data = [
            'bookmarks' => $bookmarks,
        ];
    }

    /**
     * Livewire lifecycle hook when selectedVideoId is updated.
     *
     * @param  int|string|null  $videoId
     */
    public function updatedSelectedVideoId($videoId): void
    {
        $this->selectVideo($videoId ? (int) $videoId : null);
    }

    /**
     * Switch to another video when the user selects one from the library dropdown.
     */
    public function selectVideo(?int $videoId): void
    {
        if (! $videoId) {
            return;
        }

        $video = Video::find($videoId);
        if (! $video) {
            return;
        }

        $this->selectedVideoId = $video->id;
        $rawId = $video->youtube_video_id ?: '7lP9x9a_B0M';
        $this->youtubeVideoId = YouTubeService::extractVideoId($rawId) ?: $rawId;
        $this->videoTitle = $video->title;
        $this->customYoutubeInput = '';

        $this->loadVideoBookmarks($video);
        $this->form->fill($this->data);

        $this->dispatch('video-player-change', videoId: $this->youtubeVideoId);

        Notification::make()
            ->title('Video Loaded')
            ->body('Loaded '.$this->videoTitle.' with '.count($this->data['bookmarks']).' existing bookmark(s).')
            ->success()
            ->send();
    }

    /**
     * Load a custom YouTube video URL or ID entered by the user.
     */
    public function loadCustomVideo(): void
    {
        $input = trim($this->customYoutubeInput);
        if ($input === '') {
            return;
        }

        $videoId = YouTubeService::extractVideoId($input) ?: $input;

        if ($videoId !== '') {
            $this->selectedVideoId = null;
            $this->youtubeVideoId = $videoId;
            $this->videoTitle = 'Custom Video ('.$videoId.')';

            $this->dispatch('video-player-change', videoId: $videoId);

            Notification::make()
                ->title('Custom Video Loaded')
                ->body('Embedded YouTube video ID: '.$videoId)
                ->info()
                ->send();
        }
    }

    /**
     * Get allowable bookmark label options including previous labels from the database.
     *
     * @return array<string, string>
     */
    public function getBookmarkLabelOptions(): array
    {
        $options = VideoForm::getBookmarkLabelOptions();

        if (! empty($this->data['bookmarks'])) {
            foreach ($this->data['bookmarks'] as $item) {
                if (! empty($item['label']) && is_string($item['label'])) {
                    $lbl = trim($item['label']);
                    if ($lbl !== '') {
                        $options[$lbl] = $lbl;
                    }
                }
            }
            natcasesort($options);
        }

        return $options;
    }

    /**
     * Search existing bookmark labels and offer on-the-fly create option if no exact match.
     *
     * @return array<string, string>
     */
    public function getBookmarkLabelSearchResults(string $search): array
    {
        $options = $this->getBookmarkLabelOptions();
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
     * Get allowable puzzle options for the currently selected video.
     *
     * @return array<int, string>
     */
    public function getAssociatedPuzzleOptions(mixed $currentState = null): array
    {
        if (! $this->selectedVideoId) {
            if (filled($currentState) && is_numeric($currentState)) {
                $puzzle = Puzzle::find((int) $currentState);

                return $puzzle ? [$puzzle->id => $puzzle->title] : [];
            }

            return [];
        }

        $video = Video::with('puzzles')->find($this->selectedVideoId);
        if (! $video) {
            return [];
        }

        $options = $video->puzzles->pluck('title', 'id')->toArray();

        if (filled($currentState) && is_numeric($currentState) && ! isset($options[(int) $currentState])) {
            $puzzle = Puzzle::find((int) $currentState);
            if ($puzzle) {
                $options[$puzzle->id] = $puzzle->title;
            }
        }

        return $options;
    }

    /**
     * Programmatically add a new bookmark row with a specific timestamp and optional label.
     * Prepend at the beginning of the list (reverse chronological order) so scrolling is not needed.
     */
    public function addBookmarkWithTimestamp(string $timestamp, string $label = '', ?int $puzzleId = null): void
    {
        $allowed = $this->getAssociatedPuzzleOptions();
        if ($puzzleId !== null && ! array_key_exists($puzzleId, $allowed)) {
            $puzzleId = null;
        }

        $rawState = $this->form->getRawState();
        $existing = is_array($rawState['bookmarks'] ?? null) && ! empty($rawState['bookmarks'])
            ? $rawState['bookmarks']
            : ($this->data['bookmarks'] ?? []);

        $items = array_values($existing);
        array_unshift($items, [
            'timestamp_seconds' => $timestamp,
            'label' => $label,
            'puzzle_id' => $puzzleId,
        ]);

        $this->data['bookmarks'] = $items;
        $this->form->fill($this->data);

        Notification::make()
            ->title('Timestamp Captured')
            ->body('Added bookmark at '.$timestamp)
            ->success()
            ->send();
    }

    /**
     * Save current bookmarks to the selected video in the database.
     */
    public function saveBookmarks(): void
    {
        $state = $this->form->getState();
        $bookmarks = $state['bookmarks'] ?? [];

        if (! $this->selectedVideoId) {
            Notification::make()
                ->title('Test Verification Successful')
                ->body('Verified '.count($bookmarks).' bookmark(s) ready for saving. Select a database video to persist changes.')
                ->info()
                ->send();

            return;
        }

        $video = Video::find($this->selectedVideoId);
        if (! $video) {
            return;
        }

        $video->bookmarks()->delete();

        // Sort bookmarks chronologically by timestamp before saving to database
        $sorted = collect($bookmarks)->filter(function ($item) {
            return filled($item['timestamp_seconds']) && filled($item['label']);
        })->sortBy(function ($item) {
            return Bookmark::parseTimecodeToSeconds($item['timestamp_seconds']);
        });

        foreach ($sorted as $item) {
            $seconds = Bookmark::parseTimecodeToSeconds($item['timestamp_seconds']);
            $puzzleId = ! empty($item['puzzle_id']) ? (int) $item['puzzle_id'] : null;

            if ($puzzleId && ! $video->puzzles()->where('puzzles.id', $puzzleId)->exists()) {
                $puzzleId = null;
            }

            $video->bookmarks()->create([
                'timestamp_seconds' => $seconds,
                'label' => trim($item['label']),
                'puzzle_id' => $puzzleId,
            ]);
        }

        Notification::make()
            ->title('Bookmarks Saved')
            ->body('Saved '.count($sorted).' bookmark(s) to '.$video->title.'.')
            ->success()
            ->send();
    }

    /**
     * Clear all current bookmarks from the form state.
     */
    public function clearBookmarks(): void
    {
        $this->data['bookmarks'] = [];
        $this->form->fill($this->data);

        Notification::make()
            ->title('Bookmarks Cleared')
            ->body('All bookmark rows have been removed from the form.')
            ->info()
            ->send();
    }

    /**
     * Configure the test form schema containing the bookmarks repeater.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Bookmarks and Timestamps')
                    ->description('Annotate key moments in this video. Click the (+) Add Bookmark button to capture the current video playback timestamp.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('bookmarks')
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
                                        ->columnSpan(['default' => 2, 'sm' => 2, 'lg' => 2])
                                        ->extraAttributes([
                                            'class' => 'bookmark-timestamp-input',
                                        ]),

                                    Select::make('label')
                                        ->label('Bookmark Label')
                                        ->placeholder('Select or type new label...')
                                        ->searchable()
                                        ->required()
                                        ->options(fn () => $this->getBookmarkLabelOptions())
                                        ->getSearchResultsUsing(fn (string $search): array => $this->getBookmarkLabelSearchResults($search))
                                        ->getOptionLabelUsing(fn ($value): ?string => filled($value) ? (string) $value : null)
                                        ->createOptionForm([
                                            TextInput::make('label')
                                                ->label('New Bookmark Label')
                                                ->required()
                                                ->maxLength(150),
                                        ])
                                        ->createOptionAction(fn (Action $action) => $action->modalHeading('Create New Bookmark Label')->modalWidth('md'))
                                        ->createOptionUsing(fn (array $data): string => trim($data['label']))
                                        ->columnSpan(['default' => 5, 'sm' => 5, 'lg' => 5])
                                        ->extraAttributes([
                                            'class' => 'bookmark-label-select',
                                        ]),

                                    Select::make('puzzle_id')
                                        ->label('Associated Puzzle (Optional)')
                                        ->placeholder(fn () => empty($this->getAssociatedPuzzleOptions()) ? 'No puzzles associated with this video' : 'Select video puzzle...')
                                        ->options(fn (Select $component) => $this->getAssociatedPuzzleOptions($component->getState()))
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->rules([
                                            fn () => function (string $attribute, $value, \Closure $fail) {
                                                if (blank($value)) {
                                                    return;
                                                }

                                                if (! $this->selectedVideoId) {
                                                    return;
                                                }

                                                $video = Video::find($this->selectedVideoId);
                                                if ($video && ! $video->puzzles()->where('puzzles.id', $value)->exists()) {
                                                    $fail('The selected puzzle must be tagged to this video.');
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
                            ->reorderable(true)
                            ->addAction(fn (Action $action) => $action
                                ->label('Add Bookmark')
                                ->icon('heroicon-m-plus')
                                ->button()
                                ->color('amber')
                                ->tooltip('Add Bookmark with current video timestamp')
                                ->livewireClickHandlerEnabled(false)
                                ->extraAttributes([
                                    'class' => 'bookmark-add-btn',
                                    'x-on:click.prevent.stop' => 'captureAndAddBookmark()',
                                ])
                                ->action(function (array $arguments, Repeater $component): void {
                                    $timestamp = $arguments['timestamp'] ?? '0:00';
                                    $this->addBookmarkWithTimestamp($timestamp);
                                })
                            )
                            ->addActionAlignment(Alignment::Start)
                            ->deleteAction(fn (Action $action) => $action
                                ->iconButton()
                                ->tooltip('Delete Bookmark')
                            )
                            ->extraAttributes([
                                'class' => 'bookmark-repeater',
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Get header actions for this test page.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Bookmarks')
                ->icon(Heroicon::OutlinedCheck)
                ->color('primary')
                ->action('saveBookmarks'),

            Action::make('clear')
                ->label('Clear List')
                ->icon(Heroicon::OutlinedTrash)
                ->color('gray')
                ->requiresConfirmation()
                ->action('clearBookmarks'),

            Action::make('videos')
                ->label('Manage Videos')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(fn (): string => route('filament.admin.resources.videos.index')),
        ];
    }
}
