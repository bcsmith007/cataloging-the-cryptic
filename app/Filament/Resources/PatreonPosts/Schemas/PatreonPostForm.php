<?php

namespace App\Filament\Resources\PatreonPosts\Schemas;

use App\Models\PatreonPost;
use App\Services\PatreonService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form schema configuration for PatreonPost resource.
 *
 * Implements automated fetching of post title, publish timestamp,
 * and content summary directly from Patreon's public post API.
 */
class PatreonPostForm
{
    /**
     * Configure the form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Patreon Post Details')
                    ->schema(static::schemaComponents()),
            ]);
    }

    /**
     * Reusable schema components array.
     *
     * @return array<int, mixed>
     */
    public static function schemaComponents(): array
    {
        return [
            TextInput::make('patreon_url')
                ->label('Patreon Post URL or ID')
                ->required()
                ->maxLength(512)
                ->placeholder('e.g. https://www.patreon.com/posts/whispering-shadows-98765432')
                ->helperText('Paste a Patreon post URL or numeric ID to automatically fetch the title and exact publication timestamp.')
                ->live(onBlur: true)
                ->suffixAction(
                    Action::make('fetchPatreon')
                        ->label('Fetch')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->action(function ($state, callable $set): void {
                            static::populateFromPatreon($state, $set);
                        })
                )
                ->afterStateUpdated(function (?string $state, callable $set): void {
                    if (filled($state)) {
                        static::populateFromPatreon($state, $set);
                    }
                }),

            Grid::make(2)->schema([
                TextInput::make('title')
                    ->label('Post Title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', PatreonPost::generateSlug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(280)
                    ->unique(ignoreRecord: true)
                    ->helperText('Auto-generated URL identifier for internal references.'),
            ]),

            DateTimePicker::make('published_at')
                ->label('Published Date & Time')
                ->required()
                ->default(now())
                ->helperText('Exact timestamp when this post was published on Patreon.'),

            Textarea::make('notes')
                ->label('Notes / Content Summary')
                ->rows(4)
                ->nullable()
                ->helperText('Optional description or editorial notes about this Patreon release.'),
        ];
    }

    /**
     * Populate form fields with metadata queried from Patreon.
     *
     * @param  string|null  $input  Patreon URL or numeric ID.
     * @param  callable  $set  Form state setter.
     */
    public static function populateFromPatreon(?string $input, callable $set): void
    {
        if (! filled($input)) {
            return;
        }

        $service = app(PatreonService::class);
        $data = $service->getPostDetails($input);

        if (! $data) {
            Notification::make()
                ->title('Unable to fetch Patreon post')
                ->body('Could not locate post information for the provided URL or ID. Check the link or enter details manually.')
                ->danger()
                ->send();

            return;
        }

        if (! empty($data['title'])) {
            $set('title', $data['title']);
            $set('slug', PatreonPost::generateSlug($data['title']));
        }

        if (! empty($data['published_at'])) {
            $set('published_at', $data['published_at']);
        }

        if (! empty($data['teaser_text'])) {
            $set('notes', $data['teaser_text']);
        }

        Notification::make()
            ->title('Patreon Post Metadata Fetched')
            ->body('Loaded post title, exact publication timestamp, and teaser summary.')
            ->success()
            ->send();
    }
}
