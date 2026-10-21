<x-filament-panels::page>
    <div
        x-data="videoBookmarkController({
            initialVideoId: @js($youtubeVideoId),
        })"
        x-init="initPlayer()"
        @video-player-change.window="loadVideo($event.detail?.videoId || $event.detail?.[0]?.videoId || $event.detail)"
        class="space-y-6"
    >
        {{-- Video Selection & Custom URL Bar --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                <div class="md:col-span-6 space-y-1">
                    <label class="block text-xs font-medium text-slate-300">
                        Select Video from Library
                    </label>
                    <select
                        wire:model.live="selectedVideoId"
                        @change="const opt = $event.target.selectedOptions[0]; const vid = opt ? opt.getAttribute('data-youtube-id') : null; if (vid) loadVideo(vid);"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                    >
                        @php
                            $libraryVideos = \App\Models\Video::whereNotNull('youtube_video_id')->orderByDesc('published_at')->get();
                            if ($selectedVideoId && ! $libraryVideos->contains('id', $selectedVideoId)) {
                                $selectedModel = \App\Models\Video::find($selectedVideoId);
                                if ($selectedModel) {
                                    $libraryVideos->prepend($selectedModel);
                                }
                            }
                        @endphp
                        @foreach ($libraryVideos as $vid)
                            @php
                                $optYtId = \App\Services\YouTubeService::extractVideoId($vid->youtube_video_id) ?: $vid->youtube_video_id;
                            @endphp
                            <option
                                value="{{ $vid->id }}"
                                data-youtube-id="{{ $optYtId }}"
                                @selected($vid->id === $selectedVideoId)
                            >
                                {{ $vid->title }} ({{ $vid->published_at ? $vid->published_at->format('M j, Y') : 'Draft' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-4 space-y-1">
                    <label class="block text-xs font-medium text-slate-300">
                        Or Test Any YouTube URL / ID
                    </label>
                    <input
                        type="text"
                        wire:model="customYoutubeInput"
                        placeholder="e.g. https://youtu.be/dQw4w9WgXcQ"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                        @keydown.enter.prevent="$wire.loadCustomVideo()"
                    />
                </div>

                <div class="md:col-span-2">
                    <button
                        type="button"
                        wire:click="loadCustomVideo"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-200 transition-colors hover:border-amber-500/60 hover:bg-slate-700 hover:text-white"
                    >
                        Load URL
                    </button>
                </div>
            </div>
        </div>

        {{-- Video Player Card --}}
        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-2xl">
            {{-- Header info bar --}}
            <div class="flex flex-wrap items-center justify-between border-b border-slate-800/80 bg-slate-900/80 px-4 py-3">
                <div class="flex items-center space-x-3">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full" :class="isPlaying ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></span>
                    <span class="font-medium text-slate-200 truncate max-w-lg" x-text="displayTitle || @js($videoTitle)">{{ $videoTitle }}</span>
                    <span class="text-xs font-mono text-slate-500">ID: <span x-text="videoId"></span></span>
                </div>

                <div class="flex items-center space-x-3">
                    <span class="text-xs font-mono text-slate-400">
                        Status: <span x-text="playerStateText" class="text-slate-200 font-semibold">Ready</span>
                    </span>
                </div>
            </div>

            {{-- 16:9 YouTube Player Frame (Protected from Livewire morphing via wire:ignore) --}}
            <div id="yt-player-frame-wrapper" wire:ignore class="relative w-full aspect-video bg-black flex items-center justify-center">
                <div id="yt-test-player-container" class="w-full h-full"></div>
            </div>

            {{-- Real-Time Interactive Player Controls Bar --}}
            <div class="flex flex-wrap items-center gap-2 border-t border-slate-800/80 bg-slate-900/95 px-4 py-3 shadow-inner backdrop-blur-md">
                {{-- 1. Play / Pause Button --}}
                <button
                    type="button"
                    @click="togglePlay()"
                    class="yt-control-btn rounded-lg border border-slate-700/80 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 shadow-sm hover:border-slate-500 hover:bg-slate-700 hover:text-white transition-all transform active:scale-95 cursor-pointer"
                    title="Toggle Play / Pause"
                >
                    <svg width="16" height="16" x-show="!isPlaying" class="yt-control-icon text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <svg width="16" height="16" x-show="isPlaying" class="yt-control-icon text-amber-400" fill="currentColor" viewBox="0 0 24 24" style="display: none;">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                    </svg>
                    <span x-text="isPlaying ? 'Pause' : 'Play'">Play</span>
                </button>

                {{-- 2. -5s Rewind Button --}}
                <button
                    type="button"
                    @click="seekRelative(-5)"
                    class="yt-control-btn group rounded-lg border border-slate-700/80 bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-300 shadow-sm hover:border-slate-500 hover:bg-slate-700 hover:text-white transition-all transform active:scale-95 cursor-pointer"
                    title="Rewind 5 seconds"
                >
                    <svg width="16" height="16" class="yt-control-icon text-slate-400 group-hover:text-amber-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                    </svg>
                    <span>-5s</span>
                </button>

                {{-- 3. +5s Forward Button --}}
                <button
                    type="button"
                    @click="seekRelative(5)"
                    class="yt-control-btn group rounded-lg border border-slate-700/80 bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-300 shadow-sm hover:border-slate-500 hover:bg-slate-700 hover:text-white transition-all transform active:scale-95 cursor-pointer"
                    title="Fast-forward 5 seconds"
                >
                    <svg width="16" height="16" class="yt-control-icon text-slate-400 group-hover:text-amber-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.934 12.8a1 1 0 000-1.6l-5.334-4A1 1 0 005 8v8a1 1 0 001.6.8l5.334-4zM19.934 12.8a1 1 0 000-1.6l-5.334-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.334-4z" />
                    </svg>
                    <span>+5s</span>
                </button>

                {{-- 4. Time: x:xx/y:yy Display Button --}}
                <button
                    type="button"
                    @click="copyCurrentTime()"
                    class="yt-control-btn group rounded-lg border border-slate-700/80 bg-slate-950 px-3 py-1.5 text-xs font-mono shadow-inner hover:border-amber-500/50 hover:bg-slate-900 transition-all cursor-pointer transform active:scale-95"
                    title="Click to copy current timestamp"
                >
                    <svg width="16" height="16" class="yt-control-icon text-slate-400 group-hover:text-amber-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-slate-400 text-[11px]">Time:</span>
                    <span x-show="!timeCopied" class="text-amber-400 font-bold" x-text="formattedCurrentTime">0:00</span>
                    <span x-show="!timeCopied" class="text-slate-600">/</span>
                    <span x-show="!timeCopied" class="text-slate-400" x-text="formattedDuration">0:00</span>
                    <span x-show="timeCopied" class="text-emerald-400 font-semibold text-[11px]" style="display: none;">Copied!</span>
                </button>
            </div>
        </div>

        {{-- Bookmarks Repeater Form with top-positioned Add Button --}}
        <div>
            <style>
                /* Unified height and horizontal alignment for player control bar buttons */
                .yt-control-btn {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    flex-direction: row !important;
                    flex-wrap: nowrap !important;
                    white-space: nowrap !important;
                    gap: 0.375rem !important;
                    height: 2.125rem !important;
                    box-sizing: border-box !important;
                }

                /* Force every single SVG icon in the player control bar to be strictly 16x16px */
                .yt-control-icon,
                .yt-control-btn svg {
                    width: 16px !important;
                    height: 16px !important;
                    min-width: 16px !important;
                    min-height: 16px !important;
                    max-width: 16px !important;
                    max-height: 16px !important;
                    flex-shrink: 0 !important;
                    display: inline-block !important;
                    vertical-align: middle !important;
                }

                .bookmark-repeater {
                    display: flex !important;
                    flex-direction: column !important;
                }
                .bookmark-repeater > .fi-fo-repeater-add {
                    order: -1 !important;
                    margin-bottom: 1rem !important;
                    margin-top: 0.25rem !important;
                    padding-bottom: 0.75rem !important;
                    border-bottom: 1px dashed rgba(100, 116, 139, 0.3) !important;
                }
                .bookmark-repeater > .fi-fo-repeater-items {
                    order: 1 !important;
                }
                .bookmark-repeater .bookmark-add-btn {
                    transition: transform 0.15s ease-in-out, background-color 0.15s ease-in-out;
                }
                .bookmark-repeater .bookmark-add-btn:hover {
                    transform: translateY(-1px);
                }
            </style>
            {{ $this->form }}
        </div>

        {{-- Explanatory guide card --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4 text-xs text-slate-400 space-y-2">
            <div class="font-semibold text-slate-300">How real-time timestamp capture works on this test page:</div>
            <ul class="list-disc list-inside space-y-1">
                <li><strong class="text-slate-200">Clicking the (+) button</strong> adds a new bookmark at the current playback timestamp at the top of the list.</li>
                <li><strong class="text-slate-200">Clicking the play button next to any timestamp</strong> seeks the video directly to that bookmark.</li>
                <li><strong class="text-slate-200">Save Bookmarks button</strong> saves your changes directly to the selected database video so you can verify data persistence.</li>
            </ul>
        </div>
    </div>

    {{-- YouTube IFrame API and Alpine controller script --}}
    @push('scripts')
        <script>
            function videoBookmarkController(config) {
                return {
                    videoId: config.initialVideoId,
                    player: null,
                    isPlaying: false,
                    currentTime: 0,
                    duration: 0,
                    timer: null,
                    pendingCapturedTimestamp: null,
                    timeCopied: false,

                    get formattedCurrentTime() {
                        return this.formatSeconds(this.currentTime);
                    },

                    get formattedDuration() {
                        return this.formatSeconds(this.duration);
                    },

                    get playerStateText() {
                        if (this.isPlaying) return 'Playing';
                        if (this.currentTime > 0) return 'Paused';
                        return 'Ready';
                    },

                    formatSeconds(totalSeconds) {
                        totalSeconds = Math.max(0, Math.floor(totalSeconds || 0));
                        const hours = Math.floor(totalSeconds / 3600);
                        const minutes = Math.floor((totalSeconds % 3600) / 60);
                        const seconds = totalSeconds % 60;
                        const pad = (n) => String(n).padStart(2, '0');

                        if (hours > 0) {
                            return `${hours}:${pad(minutes)}:${pad(seconds)}`;
                        }
                        return `${minutes}:${pad(seconds)}`;
                    },

                    parseTimecodeToSeconds(timecode) {
                        if (!timecode) return 0;
                        const parts = String(timecode).trim().split(':').map(Number);
                        if (parts.length === 3) {
                            return (parts[0] * 3600) + (parts[1] * 60) + parts[2];
                        }
                        if (parts.length === 2) {
                            return (parts[0] * 60) + parts[1];
                        }
                        return parts[0] || 0;
                    },

                    loadVideo(newId) {
                        if (!newId) return;
                        this.videoId = String(newId).trim();
                        this.currentTime = 0;
                        this.isPlaying = false;

                        // If player exists, is in DOM, and has loadVideoById, use it
                        const iframe = document.getElementById('yt-test-player-container');
                        if (iframe && this.player && typeof this.player.loadVideoById === 'function') {
                            try {
                                this.player.loadVideoById({
                                    videoId: this.videoId,
                                    startSeconds: 0
                                });
                                console.log('[TestPlayer] Smoothly loaded video ID via loadVideoById:', this.videoId);
                                return;
                            } catch (err) {
                                console.warn('[TestPlayer] loadVideoById error, remounting:', err);
                            }
                        }

                        // Otherwise, mount freshly
                        this.mountYouTubePlayer();
                    },

                    initPlayer() {
                        const self = this;

                        // Watch for Livewire updates to youtubeVideoId
                        if (this.$watch) {
                            this.$watch('$wire.youtubeVideoId', (newId) => {
                                if (newId && newId !== self.videoId) {
                                    self.loadVideo(newId);
                                }
                            });
                        }

                        // Listen for Livewire video switch event
                        window.addEventListener('video-player-change', (event) => {
                            const newId = event.detail?.videoId || (Array.isArray(event.detail) ? event.detail[0]?.videoId : event.detail);
                            if (newId) {
                                self.loadVideo(newId);
                            }
                        });

                        // Set up global click listener for the repeater's (+) add button
                        document.addEventListener('click', (event) => {
                            const addBtn = event.target.closest('.bookmark-add-btn, .bookmark-repeater .fi-fo-repeater-actions button, .bookmark-repeater button[wire\\:click*="add"], .fi-fo-repeater-add button');
                            if (addBtn) {
                                event.preventDefault();
                                event.stopPropagation();
                                self.captureAndAddBookmark();
                            }
                        }, true);

                        // Set up MutationObserver to highlight newly inserted repeater rows
                        this.setupRepeaterObserver();

                        // Load YouTube IFrame API script if not already present
                        if (!window.YT || !window.YT.Player) {
                            if (!document.getElementById('yt-iframe-api-script')) {
                                const tag = document.createElement('script');
                                tag.id = 'yt-iframe-api-script';
                                tag.src = 'https://www.youtube.com/iframe_api';
                                const firstScriptTag = document.getElementsByTagName('script')[0];
                                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
                            }

                            const previousReady = window.onYouTubeIframeAPIReady;
                            window.onYouTubeIframeAPIReady = function() {
                                if (typeof previousReady === 'function') previousReady();
                                self.mountYouTubePlayer();
                            };
                        } else {
                            this.mountYouTubePlayer();
                        }
                    },

                    mountYouTubePlayer() {
                        const self = this;
                        const wrapper = document.getElementById('yt-player-frame-wrapper');
                        if (!wrapper) return;

                        if (this.player) {
                            try {
                                if (typeof this.player.destroy === 'function') {
                                    this.player.destroy();
                                }
                            } catch (e) {}
                            this.player = null;
                        }

                        // Ensure a fresh target div element exists inside the wire:ignore wrapper
                        wrapper.innerHTML = '<div id="yt-test-player-container" class="w-full h-full"></div>';

                        if (!window.YT || !window.YT.Player) {
                            return;
                        }

                        this.player = new YT.Player('yt-test-player-container', {
                            videoId: self.videoId,
                            playerVars: {
                                enablejsapi: 1,
                                origin: window.location.origin,
                                rel: 0,
                                modestbranding: 1
                            },
                            events: {
                                onReady: (e) => {
                                    window.ytTestPlayer = self.player;
                                    self.duration = self.player.getDuration() || 0;
                                    self.startTimer();
                                },
                                onStateChange: (e) => {
                                    self.isPlaying = (e.data === YT.PlayerState.PLAYING);
                                    if (self.isPlaying) {
                                        self.startTimer();
                                    } else {
                                        self.updateCurrentTime();
                                    }
                                }
                            }
                        });
                    },

                    startTimer() {
                        if (this.timer) clearInterval(this.timer);
                        const self = this;
                        this.timer = setInterval(() => {
                            self.updateCurrentTime();
                        }, 250);
                    },

                    updateCurrentTime() {
                        if (this.player && typeof this.player.getCurrentTime === 'function') {
                            this.currentTime = this.player.getCurrentTime();
                            this.duration = this.player.getDuration() || this.duration;
                        }
                    },

                    togglePlay() {
                        if (!this.player) return;
                        if (this.isPlaying) {
                            this.player.pauseVideo();
                        } else {
                            this.player.playVideo();
                        }
                    },

                    seekRelative(deltaSeconds) {
                        if (!this.player) return;
                        const newTime = Math.max(0, (this.player.getCurrentTime() || 0) + deltaSeconds);
                        this.player.seekTo(newTime, true);
                        this.updateCurrentTime();
                    },

                    seekTo(timecode) {
                        if (!this.player) return;
                        const seconds = this.parseTimecodeToSeconds(timecode);
                        this.player.seekTo(seconds, true);
                        this.player.playVideo();
                        this.updateCurrentTime();
                    },

                    copyCurrentTime() {
                        const time = this.formattedCurrentTime;
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(time).catch(() => {});
                        }
                        this.timeCopied = true;
                        setTimeout(() => {
                            this.timeCopied = false;
                        }, 1500);
                    },

                    captureAndAddBookmark() {
                        this.updateCurrentTime();
                        const timeString = this.formattedCurrentTime;

                        // Trigger through Livewire helper
                        this.$wire.addBookmarkWithTimestamp(timeString);
                    },

                    setupRepeaterObserver() {
                        const observer = new MutationObserver(() => {
                            const inputs = document.querySelectorAll('.bookmark-repeater input[placeholder*="1:23:45"], .bookmark-repeater .bookmark-timestamp-input input');
                            if (inputs.length > 0) {
                                const firstInput = inputs[0];
                                if (!firstInput.dataset.highlighted && firstInput.value) {
                                    firstInput.dataset.highlighted = 'true';
                                    firstInput.classList.add('ring-2', 'ring-emerald-500', 'bg-emerald-950/40');
                                    setTimeout(() => {
                                        firstInput.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-950/40');
                                        delete firstInput.dataset.highlighted;
                                    }, 1200);
                                }
                            }
                        });

                        observer.observe(document.body, { childList: true, subtree: true });
                    }
                };
            }
        </script>
    @endpush
</x-filament-panels::page>
