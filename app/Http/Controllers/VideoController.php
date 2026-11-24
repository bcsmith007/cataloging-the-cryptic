<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for video catalog and detail views.
 */
class VideoController extends Controller
{
    /**
     * Display a listing of all videos in reverse chronological order, optionally jumping to a specific date.
     */
    public function index(Request $request): View
    {
        $rawDate = trim((string) $request->query('date', ''));
        $jumpDate = null;
        $jumpCutoffUtc = null;

        if ($rawDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate) === 1) {
            try {
                $timezone = $request->user()?->timezone ?: config('app.timezone', 'UTC');
                $parsed = Carbon::createFromFormat('Y-m-d', $rawDate, $timezone);
                if ($parsed !== false && $parsed->format('Y-m-d') === $rawDate) {
                    $jumpDate = $rawDate;
                    $jumpCutoffUtc = $parsed->copy()->endOfDay()->utc()->format('Y-m-d H:i:s');
                }
            } catch (\Throwable) {
                $jumpDate = null;
                $jumpCutoffUtc = null;
            }
        }

        $query = Video::query()
            ->publiclyVisible()
            ->with(['hosts', 'puzzles.category', 'patreonPost']);

        if ($jumpCutoffUtc !== null) {
            $query->whereRaw(Video::EFFECTIVE_PUBLISHED_AT_SQL.' <= ?', [$jumpCutoffUtc]);
        }

        $videos = $query
            ->orderByEffectivePublishedAt('desc')
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        return view('videos.index', compact('videos', 'jumpDate'));
    }

    /**
     * Display the top 24 most viewed videos in the catalog, optionally filtered by host.
     */
    public function top(Request $request): View
    {
        [$currentHost, $currentHostSlug, $currentHostName] = $this->resolveHostFilter($request);

        $query = Video::query()
            ->publiclyVisible()
            ->with(['hosts', 'puzzles.category', 'patreonPost']);

        if ($currentHost) {
            $query->whereHas('hosts', fn ($q) => $q->where('hosts.id', $currentHost->id));
        } elseif ($currentHostSlug) {
            $query->whereHas('hosts', fn ($q) => $q->where('slug', $currentHostSlug)->orWhereRaw('LOWER(name) LIKE ?', ['%'.$currentHostSlug.'%']));
        }

        $videos = $query
            ->orderByDesc('view_count')
            ->orderByEffectivePublishedAt('desc')
            ->take(24)
            ->get();

        return view('videos.top', compact('videos', 'currentHost', 'currentHostSlug', 'currentHostName'));
    }

    /**
     * Display the 24 least viewed videos with positive view counts, optionally filtered by host.
     */
    public function leastViewed(Request $request): View
    {
        [$currentHost, $currentHostSlug, $currentHostName] = $this->resolveHostFilter($request);

        $query = Video::query()
            ->publiclyVisible()
            ->with(['hosts', 'puzzles.category', 'patreonPost'])
            ->where('access_level', '!=', 'exclusive')
            ->where('video_type', '!=', 'patreon')
            ->where('view_count', '>', 0);

        if ($currentHost) {
            $query->whereHas('hosts', fn ($q) => $q->where('hosts.id', $currentHost->id));
        } elseif ($currentHostSlug) {
            $query->whereHas('hosts', fn ($q) => $q->where('slug', $currentHostSlug)->orWhereRaw('LOWER(name) LIKE ?', ['%'.$currentHostSlug.'%']));
        }

        $videos = $query
            ->orderBy('view_count', 'asc')
            ->orderByEffectivePublishedAt('desc')
            ->take(24)
            ->get();

        return view('videos.least-viewed', compact('videos', 'currentHost', 'currentHostSlug', 'currentHostName'));
    }

    /**
     * Display the top 24 longest videos in the catalog, excluding live stream replays,
     * optionally filtered by host (e.g. Simon Anthony or Mark Goodliffe).
     */
    public function longest(Request $request): View
    {
        [$currentHost, $currentHostSlug, $currentHostName] = $this->resolveHostFilter($request);

        $query = Video::query()
            ->publiclyVisible()
            ->with(['hosts', 'puzzles.category', 'patreonPost'])
            ->where('video_type', '!=', 'live_stream');

        if ($currentHost) {
            $query->whereHas('hosts', fn ($q) => $q->where('hosts.id', $currentHost->id));
        } elseif ($currentHostSlug) {
            $query->whereHas('hosts', fn ($q) => $q->where('slug', $currentHostSlug)->orWhereRaw('LOWER(name) LIKE ?', ['%'.$currentHostSlug.'%']));
        }

        $videos = $query
            ->orderByDesc('duration_seconds')
            ->orderByEffectivePublishedAt('desc')
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
            ->publiclyVisible()
            ->with(['hosts', 'puzzles.category', 'patreonPost'])
            ->where('video_type', '!=', 'youtube_short')
            ->where('duration_seconds', '>', 0);

        if ($currentHost) {
            $query->whereHas('hosts', fn ($q) => $q->where('hosts.id', $currentHost->id));
        } elseif ($currentHostSlug) {
            $query->whereHas('hosts', fn ($q) => $q->where('slug', $currentHostSlug)->orWhereRaw('LOWER(name) LIKE ?', ['%'.$currentHostSlug.'%']));
        }

        $videos = $query
            ->orderBy('duration_seconds', 'asc')
            ->orderByEffectivePublishedAt('desc')
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
        $video = Video::query()->publiclyVisible()->inRandomOrder()->first();

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
            'patreonPost',
            'puzzles.setters',
            'puzzles.category',
            'puzzles.constraints',
            'puzzles.links',
            'bookmarks' => fn ($query) => $query->with('puzzle')->orderBy('timestamp_seconds', 'asc'),
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        if ($video->isPrivate() && ! auth()->check()) {
            abort(404);
        }

        $previousVideo = $video->previousVideo();
        $nextVideo = $video->nextVideo();

        return view('videos.show', compact('video', 'previousVideo', 'nextVideo'));
    }
}
