<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for video catalog and detail views.
 */
class VideoController extends Controller
{
    /**
     * Display a listing of all videos in reverse chronological order.
     */
    public function index(): View
    {
        $videos = Video::query()
            ->with(['hosts', 'puzzles.category'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(24);

        return view('videos.index', compact('videos'));
    }

    /**
     * Display the top 24 most viewed videos in the catalog, optionally filtered by host.
     */
    public function top(Request $request): View
    {
        [$currentHost, $currentHostSlug, $currentHostName] = $this->resolveHostFilter($request);

        $query = Video::query()
            ->with(['hosts', 'puzzles.category']);

        if ($currentHost) {
            $query->whereHas('hosts', fn ($q) => $q->where('hosts.id', $currentHost->id));
        } elseif ($currentHostSlug) {
            $query->whereHas('hosts', fn ($q) => $q->where('slug', $currentHostSlug)->orWhereRaw('LOWER(name) LIKE ?', ['%'.$currentHostSlug.'%']));
        }

        $videos = $query
            ->orderByDesc('view_count')
            ->orderByDesc('published_at')
            ->take(24)
            ->get();

        return view('videos.top', compact('videos', 'currentHost', 'currentHostSlug', 'currentHostName'));
    }

    /**
     * Display the top 24 longest videos in the catalog, excluding live stream replays,
     * optionally filtered by host (e.g. Simon Anthony or Mark Goodliffe).
     */
    public function longest(Request $request): View
    {
        [$currentHost, $currentHostSlug, $currentHostName] = $this->resolveHostFilter($request);

        $query = Video::query()
            ->with(['hosts', 'puzzles.category'])
            ->where('video_type', '!=', 'live_stream');

        if ($currentHost) {
            $query->whereHas('hosts', fn ($q) => $q->where('hosts.id', $currentHost->id));
        } elseif ($currentHostSlug) {
            $query->whereHas('hosts', fn ($q) => $q->where('slug', $currentHostSlug)->orWhereRaw('LOWER(name) LIKE ?', ['%'.$currentHostSlug.'%']));
        }

        $videos = $query
            ->orderByDesc('duration_seconds')
            ->orderByDesc('published_at')
            ->take(24)
            ->get();

        return view('videos.longest', compact('videos', 'currentHost', 'currentHostSlug', 'currentHostName'));
    }

    /**
     * Display the top 24 shortest videos in the catalog, excluding YouTube Shorts,
     * optionally filtered by host (e.g. Simon Anthony or Mark Goodliffe).
     */
    public function shortest(Request $request): View
    {
        [$currentHost, $currentHostSlug, $currentHostName] = $this->resolveHostFilter($request);

        $query = Video::query()
            ->with(['hosts', 'puzzles.category'])
            ->where('video_type', '!=', 'youtube_short')
            ->where('duration_seconds', '>', 0);

        if ($currentHost) {
            $query->whereHas('hosts', fn ($q) => $q->where('hosts.id', $currentHost->id));
        } elseif ($currentHostSlug) {
            $query->whereHas('hosts', fn ($q) => $q->where('slug', $currentHostSlug)->orWhereRaw('LOWER(name) LIKE ?', ['%'.$currentHostSlug.'%']));
        }

        $videos = $query
            ->orderBy('duration_seconds', 'asc')
            ->orderByDesc('published_at')
            ->take(24)
            ->get();

        return view('videos.shortest', compact('videos', 'currentHost', 'currentHostSlug', 'currentHostName'));
    }

    /**
     * Resolve the requested host model, normalized key, and display name from the request.
     *
     * @return array{0: ?Host, 1: ?string, 2: ?string}
     */
    protected function resolveHostFilter(Request $request): array
    {
        $rawHost = trim((string) $request->query('host', ''));

        if ($rawHost === '') {
            return [null, null, null];
        }

        $normalized = strtolower($rawHost);

        if (in_array($normalized, ['simon', 'simon-anthony', 'simon anthony'], true)) {
            $normalizedKey = 'simon';
            $defaultName = 'Simon Anthony';
            $host = Host::query()
                ->where('slug', 'simon-anthony')
                ->orWhere('slug', 'simon')
                ->orWhereRaw('LOWER(name) LIKE ?', ['%simon%'])
                ->first();
        } elseif (in_array($normalized, ['mark', 'mark-goodliffe', 'mark goodliffe'], true)) {
            $normalizedKey = 'mark';
            $defaultName = 'Mark Goodliffe';
            $host = Host::query()
                ->where('slug', 'mark-goodliffe')
                ->orWhere('slug', 'mark')
                ->orWhereRaw('LOWER(name) LIKE ?', ['%mark%'])
                ->first();
        } else {
            $normalizedKey = $normalized;
            $defaultName = ucfirst($rawHost);
            $host = Host::query()
                ->where('slug', $rawHost)
                ->orWhere('slug', $normalized)
                ->orWhereRaw('LOWER(name) LIKE ?', ['%'.$normalized.'%'])
                ->first();
        }

        $displayName = $host?->name ?? $defaultName;

        return [$host, $normalizedKey, $displayName];
    }

    /**
     * Redirect to a random video in the catalog.
     */
    public function random(): RedirectResponse
    {
        $video = Video::inRandomOrder()->first();

        if (! $video) {
            return redirect()->route('videos.index');
        }

        return redirect()->route('videos.show', $video->slug);
    }

    /**
     * Display the specified video detail page.
     */
    public function show(string $slug): View
    {
        $video = Video::with([
            'hosts',
            'puzzles.setters',
            'puzzles.category',
            'puzzles.constraints',
            'puzzles.links',
            'bookmarks' => fn ($query) => $query->with('puzzle')->orderBy('timestamp_seconds', 'asc'),
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('videos.show', compact('video'));
    }
}
