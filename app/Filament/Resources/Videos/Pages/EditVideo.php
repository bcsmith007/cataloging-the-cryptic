<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Pages\BookmarkPlayer;
use App\Filament\Resources\Videos\VideoResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVideo extends EditRecord
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bookmark_player')
                ->label('Bookmark Player')
                ->icon('heroicon-m-play-circle')
                ->color('primary')
                ->url(fn (): string => BookmarkPlayer::getUrl(['video' => $this->record->id]))
                ->openUrlInNewTab(),
            Action::make('view_public')
                ->label('View Video')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => route('videos.show', $this->record->slug))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
