<?php

namespace App\Http\Controllers;

use App\Models\CtcApp;
use Illuminate\Contracts\View\View;

/**
 * Controller handling the public apps showcase directory.
 *
 * Displays all official CTC-released apps across Apple App Store,
 * Google Play, Steam, and browser players.
 */
class AppController extends Controller
{
    /**
     * Display the official CTC apps directory.
     */
    public function index(): View
    {
        $apps = CtcApp::active()->get();

        return view('apps.index', compact('apps'));
    }
}
