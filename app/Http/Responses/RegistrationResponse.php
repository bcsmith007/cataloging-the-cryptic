<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Handle post-registration redirection and access gating.
 *
 * If a newly registered user lacks admin privileges, they are logged out of the panel
 * and redirected to the public home page with an informational notice.
 */
class RegistrationResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     */
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if ($user && $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            return redirect()->intended(Filament::getUrl());
        }

        Filament::auth()->logout();

        return redirect()->route('home')->with('status', 'Your account has been created. An administrator must grant you admin privileges before you can access the admin panel.');
    }
}
