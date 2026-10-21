<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller handling public cross-entity catalog search.
 */
class SearchController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Display search results across videos, puzzles, setters, categories, constraints, and hosts.
     */
    public function index(Request $request): View
    {
        $query = (string) $request->input('q', '');
        $type = (string) $request->input('type', 'all');

        $results = $this->searchService->search($query, $type);

        return view('search.index', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
        ]);
    }
}
