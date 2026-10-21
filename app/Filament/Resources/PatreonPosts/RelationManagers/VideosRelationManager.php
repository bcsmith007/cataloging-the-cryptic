<?php

namespace App\Filament\Resources\PatreonPosts\RelationManagers;

use App\Filament\Resources\Videos\Schemas\VideoForm;
use App\Models\Video;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Relation manager for videos associated with a Patreon post.
 *
 * Supports adding both public and exclusive videos to the post,
 * utilizing the complete VideoForm schema with YouTube metadata ingestion.
 */
class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';

    protected static ?string $title = 'Contained Videos';

    /**
     * Configure form schema for creating or editing video in this post.
     *
     * Delegates directly to VideoForm to guarantee consistent fields,
     * YouTube data fetching, music selection, and access level toggles.
     */
    public function form(Schema $schema): Schema
    {
        return VideoForm::configure($schema);
    }

    /**
     * Configure the relation table.
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

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
                    }),

                TextColumn::make('video_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'youtube' => 'YouTube',
                        'youtube_short' => 'YouTube Short',
                        'live_stream' => 'Live Stream',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color('info'),

                TextColumn::make('puzzles_count')
                    ->counts('puzzles')
                    ->label('Puzzles')
                    ->badge(),

                TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state): string => Video::formatSecondsToHms($state)),

                TextColumn::make('published_at')
                    ->date('M j, Y'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add New Video to Post')
                    ->modalHeading('Add New Video to Patreon Post')
                    ->modalWidth('5xl')
                    ->slideOver(),
                AssociateAction::make()
                    ->preloadRecordSelect()
                    ->label('Attach Existing Video')
                    ->modalHeading('Attach Existing Video to Patreon Post'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Video Details')
                    ->modalWidth('5xl')
                    ->slideOver(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
