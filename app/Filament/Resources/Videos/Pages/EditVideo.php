<?php

namespace App\Filament\Resources\Videos\Pages;

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
