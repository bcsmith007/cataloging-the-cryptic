<x-filament-widgets::widget>
    @php
        $data = $this->getAppsData();
        $apps = $data['apps'];
    @endphp
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6 shadow-sm space-y-5">
        {{-- Header & Quick Action Buttons --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span>Cracking the Cryptic Apps Management</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Add new apps or update store links, thumbnails, and descriptions. Changes reflect instantly on the public <a href="{{ route('apps.index') }}" target="_blank" class="text-emerald-400 underline hover:text-emerald-300">/apps</a> directory.
                </p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <a
                    href="{{ route('filament.admin.resources.ctc-apps.create') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500 text-slate-950 hover:bg-amber-400 transition-colors shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add New App</span>
                </a>

                <a
                    href="{{ route('filament.admin.resources.ctc-apps.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 border border-slate-700 text-slate-200 hover:bg-slate-700 hover:text-white transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span>Manage All Apps ({{ $data['total'] }})</span>
                </a>
            </div>
        </div>

        {{-- Quick Overview List --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs divide-y divide-slate-800">
                <thead>
                    <tr class="text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-2.5 px-3">App Name</th>
                        <th class="py-2.5 px-3">Developer</th>
                        <th class="py-2.5 px-3">Platforms</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @forelse ($apps as $app)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-2.5 px-3 font-medium text-white flex items-center gap-2">
                                <span>{{ $app->name }}</span>
                                @if (filled($app->badge))
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-400">
                                        {{ $app->badge }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-slate-400">{{ $app->developer }}</td>
                            <td class="py-2.5 px-3">
                                <div class="flex items-center gap-1.5">
                                    @if (filled($app->apple_store_url))
                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-800 border border-slate-700 text-slate-300" title="Apple App Store">iOS</span>
                                    @endif
                                    @if (filled($app->google_play_url))
                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-800 border border-slate-700 text-slate-300" title="Google Play Store">Android</span>
                                    @endif
                                    @if (filled($app->steam_url))
                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-800 border border-slate-700 text-slate-300" title="Steam">Steam</span>
                                    @endif
                                    @if (filled($app->web_url))
                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-950 border border-emerald-800 text-emerald-300" title="Web App">Web</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2.5 px-3">
                                @if ($app->is_active)
                                    <span class="inline-flex items-center gap-1 text-emerald-400 text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        <span>Active</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-500 text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                        <span>Hidden</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                <a
                                    href="{{ route('filament.admin.resources.ctc-apps.edit', ['record' => $app]) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold bg-slate-800 border border-slate-700 text-amber-400 hover:bg-slate-700 hover:text-amber-300 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Edit</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-500">No applications registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-widgets::widget>
