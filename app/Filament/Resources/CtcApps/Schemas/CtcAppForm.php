<?php

namespace App\Filament\Resources\CtcApps\Schemas;

use App\Models\CtcApp;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CtcAppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('App Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('App Name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                                    if ($operation === 'create' && filled($state)) {
                                        $set('slug', CtcApp::generateSlug($state));
                                    }
                                }),

                            TextInput::make('slug')
                                ->label('URL Slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->helperText('Unique URL identifier.'),

                            TextInput::make('developer')
                                ->label('Developer / Publisher')
                                ->required()
                                ->maxLength(255)
                                ->default('Studio Goya / Cracking The Cryptic'),

                            TextInput::make('badge')
                                ->label('Badge Label')
                                ->maxLength(50)
                                ->placeholder('e.g. Featured, Companion App, New')
                                ->helperText('Optional highlight badge displayed on the app card.'),
                        ]),

                        TextInput::make('tagline')
                            ->label('Tagline')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('A brief catchy description of the puzzle mechanics.'),

                        MarkdownEditor::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('Detailed information about the app, puzzle authors, and included features.')
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
                            ])
                            ->helperText(new HtmlString('Supports bullet lists, bold, italics, and arrows. <a href="/markdown" target="_blank" class="text-primary-500 hover:underline font-semibold">View Markdown Guide &rarr;</a>')),

                        TextInput::make('thumbnail_url')
                            ->label('Thumbnail / Icon URL')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull()
                            ->helperText('Direct link to an app icon or artwork image. If empty, a styled fallback tile is rendered.'),
                    ]),

                Section::make('Store & Platform Links')
                    ->description('Provide URLs to the app across supported platforms. Configured affiliate parameters are automatically appended.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('apple_store_url')
                                ->label('Apple App Store URL')
                                ->url()
                                ->maxLength(2048)
                                ->placeholder('https://apps.apple.com/app/...'),

                            TextInput::make('google_play_url')
                                ->label('Google Play Store URL')
                                ->url()
                                ->maxLength(2048)
                                ->placeholder('https://play.google.com/store/apps/details?id=...'),

                            TextInput::make('steam_url')
                                ->label('Steam Store URL')
                                ->url()
                                ->maxLength(2048)
                                ->placeholder('https://store.steampowered.com/app/...'),

                            TextInput::make('web_url')
                                ->label('Web / Browser Player URL')
                                ->url()
                                ->maxLength(2048)
                                ->placeholder('https://app.crackingthecryptic.com/...'),
                        ]),
                    ]),

                Section::make('Display Settings')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('sort_order')
                                ->label('Sort Order')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->helperText('Lower values appear first on the Apps page.'),

                            Toggle::make('is_active')
                                ->label('Visible on Public Site')
                                ->default(true)
                                ->required()
                                ->helperText('Toggle off to hide this app from the public /apps directory.'),
                        ]),
                    ]),
            ]);
    }
}
