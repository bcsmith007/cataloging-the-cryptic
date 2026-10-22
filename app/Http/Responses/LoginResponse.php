<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Handle post-login redirection and panel access verification.
 *
 * If a user successfully authenticates but lacks admin privileges, they are redirected
 * to the public home page with an informational notice rather than hitting a generic 403.
 */
class LoginResponse implements Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     */
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if ($user && ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            Filament::auth()->logout();

            return redirect()->route('home')->with('error', 'Your account does not have administrator privileges to access the admin panel.');
        }

        return redirect()->intended(Filament::getUrl());
    }
}
