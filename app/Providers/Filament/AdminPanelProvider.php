<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Configure the Filament administrative control panel.
 */
class AdminPanelProvider extends PanelProvider
{
    /**
     * Build and configure the panel instance.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->topbar(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('
                    <link rel="stylesheet" href="'.asset('fonts/fonts.css').'">
                    <link rel="stylesheet" href="'.asset('css/site.css').'">
                '),
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): View => view('partials.header'),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): View => view('partials.footer'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): HtmlString => new HtmlString('
                    <style>
                        /* Filament layout offset for sticky public header */
                        .fi-sidebar {
                            top: 4rem !important;
                            height: calc(100dvh - 4rem) !important;
                        }
                        .fi-sidebar-header-ctn,
                        .fi-sidebar-header {
                            display: none !important;
                        }

                        .bookmark-label-select .fi-select-input-btn {
                            font-size: 0.8125rem;
                            line-height: 1.25rem;
                        }
                        .bookmark-label-select .fi-dropdown-panel {
                            min-width: 100% !important;
                            width: max-content !important;
                            max-width: min(34rem, 90vw) !important;
                        }
                        .bookmark-label-select .fi-dropdown-list-item {
                            font-size: 0.8125rem !important;
                            line-height: 1.25rem !important;
                            padding-top: 0.375rem !important;
                            padding-bottom: 0.375rem !important;
                        }
                        .bookmark-label-select .fi-select-input-search-ctn input {
                            font-size: 0.8125rem !important;
                        }
                        /* Always-visible single-row bookmark repeater items with delete icon on right and no top line */
                        .bookmark-repeater .fi-fo-repeater-item {
                            display: flex;
                            flex-direction: row;
                            align-items: flex-end;
                            gap: 0.75rem;
                            padding: 0.75rem 1rem;
                        }
                        .bookmark-repeater .fi-fo-repeater-item-content,
                        .bookmark-repeater .fi-fo-repeater-item-has-header > .fi-fo-repeater-item-content {
                            flex: 1 1 0%;
                            min-width: 0;
                            border: none !important;
                            border-top: none !important;
                            padding: 0 !important;
                        }
                        .bookmark-repeater .fi-fo-repeater-item-header {
                            border: none !important;
                            border-top: none !important;
                            border-bottom: none !important;
                            background: transparent !important;
                            padding: 0 !important;
                            margin: 0 !important;
                            order: 2;
                            flex-shrink: 0;
                            display: flex;
                            align-items: center;
                            margin-bottom: 2px !important;
                        }
                        .bookmark-repeater .fi-fo-repeater-item-header .fi-fo-repeater-item-header-end-actions {
                            margin-inline-start: 0 !important;
                        }
                        .bookmark-repeater .fi-fo-repeater-item::before,
                        .bookmark-repeater .fi-fo-repeater-item::after,
                        .bookmark-repeater .fi-fo-repeater-item-content::before,
                        .bookmark-repeater .fi-fo-repeater-item-content::after {
                            display: none !important;
                            content: none !important;
                        }
                        .bookmark-repeater .fi-fo-repeater-actions {
                            display: flex;
                            justify-content: center;
                            width: 100%;
                            margin-top: 0.75rem;
                        }
                        .bookmark-repeater .fi-fo-repeater-actions button {
                            border-radius: 9999px;
                            border: 1px dashed rgba(148, 163, 184, 0.4);
                            padding: 0.375rem;
                            transition: all 0.15s ease-in-out;
                        }
                        .bookmark-repeater .fi-fo-repeater-actions button:hover {
                            border-color: rgba(245, 158, 11, 0.8);
                            background-color: rgba(245, 158, 11, 0.1);
                        }
                    </style>
                '),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
