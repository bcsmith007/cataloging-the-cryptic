<?php

namespace App\Filament\Pages\Auth;

use App\Services\TimezoneService;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

/**
 * Custom profile page allowing the user to select their preferred timezone.
 */
class EditProfile extends BaseEditProfile
{
    /**
     * Configure the profile form schema with name, email, timezone, and password management.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getTimezoneFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    /**
     * Get the timezone dropdown form component.
     */
    protected function getTimezoneFormComponent(): Component
    {
        return Select::make('timezone')
            ->label('Timezone')
            ->searchable()
            ->options(TimezoneService::getTimezoneOptions())
            ->optionsLimit(1000)
            ->default(config('app.timezone', 'UTC'))
            ->required()
            ->helperText('All timestamps across the site will be displayed in your chosen timezone.');
    }
}
