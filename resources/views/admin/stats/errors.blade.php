<x-layouts.admin title="Error Stats — admin.ternis.link">
    @include('admin.stats._header', [
        'title' => 'Error Encounters Telemetry',
        'subtitle' => 'Analysis of application errors per day, HTTP status codes, failing routes, and exceptions.',
        'section' => 'errors',
        'params' => $params,
    ])

    {{-- Filter Bar Form --}}
    <form method="GET" action="{{ route('admin.stats.show', ['section' => 'errors']) }}" class="mb-6 rounded-2xl border border-neutral-200/90 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <input type="hidden" name="days" value="{{ $stats['days'] }}">
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 items-end">
            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">HTTP Code</label>
                <input type="number" name="code" value="{{ $params['code'] ?? '' }}" placeholder="e.g. 404, 500" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Method</label>
                <select name="method" class="w-full appearance-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 cursor-pointer">
                    <option value="">Any Method</option>
                    @foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $m)
                        <option value="{{ $m }}" @selected(($params['method'] ?? '') === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Path Contains</label>
                <input type="text" name="path" value="{{ $params['path'] ?? '' }}" placeholder="/api/v1/..." class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
            </div>

            <div>
                <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Host Contains</label>
                <input type="text" name="host" value="{{ $params['host'] ?? '' }}" placeholder="dash.ternis.link" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-neutral-900 px-3 py-2 text-xs font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 cursor-pointer">
                    Filter
                </button>
                @if (!empty($params['code']) || !empty($params['method']) || !empty($params['path']) || !empty($params['host']))
                    <a href="{{ route('admin.stats.show', ['section' => 'errors', 'days' => $stats['days']]) }}" class="rounded-lg border border-neutral-300 px-2.5 py-2 text-xs font-medium text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Stat Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Filtered Encounters</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['total_filtered']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">matching active parameters</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Errors Today</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['errors_today']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">since 00:00 UTC</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Client Errors (4xx)</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-amber-600 dark:text-amber-400">{{ number_format($stats['count_4xx']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">404s, 403s, 422s, 429s</p>
        </div>

        <div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Server Errors (5xx)</span>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-red-600 dark:text-red-400">{{ number_format($stats['count_5xx']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">500s, 502s, 503s</p>
        </div>
    </div>

    {{-- Daily Timeline Chart --}}
    <div class="mb-8">
        @include('admin.stats._timeline-chart', [
            'timeline' => $stats['timeline'],
            'title' => 'Errors Recorded Per Day',
            'color' => 'red',
        ])
    </div>

    {{-- Breakdown Tables --}}
    <div class="grid gap-6 lg:grid-cols-2 mb-8">
        {{-- By HTTP Status Code --}}
        <x-ui.card title="Errors by HTTP Status Code">
            <div class="space-y-3">
                @forelse ($stats['by_code'] as $c)
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span @class([
                                'rounded px-2 py-0.5 font-mono font-bold',
                                'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' => $c['code'] >= 500,
                                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' => $c['code'] >= 400 && $c['code'] < 500,
                                'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300' => $c['code'] < 400,
                            ])>
                                {{ $c['code'] }}
                            </span>
                            <span class="text-neutral-600 dark:text-neutral-400">
                                @if($c['code'] === 404) Not Found
                                @elseif($c['code'] === 500) Server Error
                                @elseif($c['code'] === 403) Forbidden
                                @elseif($c['code'] === 429) Too Many Requests
                                @elseif($c['code'] === 422) Validation Failed
                                @else HTTP {{ $c['code'] }}
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">{{ number_format($c['count']) }}</span>
                            <span class="w-12 text-right text-neutral-400">({{ $c['percentage'] }}%)</span>
                        </div>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-neutral-100 overflow-hidden dark:bg-neutral-800">
                        <div style="width: {{ $c['percentage'] }}%" class="h-full rounded-full {{ $c['code'] >= 500 ? 'bg-red-500' : 'bg-amber-500' }}"></div>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">No error encounters found.</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Top Failing Endpoints --}}
        <x-ui.card title="Top Failing Routes &amp; Endpoints">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Path</th>
                        <th>Method</th>
                        <th>Code</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stats['top_paths'] as $p)
                        <tr>
                            <td class="font-mono text-xs max-w-[200px] truncate" title="{{ $p->path }}">{{ $p->path }}</td>
                            <td><span class="rounded bg-neutral-100 px-1.5 py-0.5 text-[10px] font-mono dark:bg-neutral-800">{{ $p->method }}</span></td>
                            <td><span class="font-mono text-xs font-semibold">{{ $p->http_code }}</span></td>
                            <td class="font-bold text-neutral-900 dark:text-white">{{ number_format($p->count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-xs text-neutral-500 py-4">No failing paths recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>

    {{-- Recent Errors Table --}}
    <x-ui.card title="Recent Error Encounters">
        <x-ui.table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Code</th>
                    <th>Method</th>
                    <th>Host &amp; Path</th>
                    <th>Error Message</th>
                    <th>Actor</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stats['recent'] as $err)
                    <tr>
                        <td class="whitespace-nowrap text-xs text-neutral-500">{{ $err->created_at->format('M d, H:i:s') }}</td>
                        <td>
                            <span @class([
                                'rounded px-1.5 py-0.5 font-mono text-xs font-bold',
                                'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' => $err->http_code >= 500,
                                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' => $err->http_code < 500,
                            ])>
                                {{ $err->http_code }}
                            </span>
                        </td>
                        <td class="font-mono text-xs">{{ $err->method }}</td>
                        <td class="font-mono text-xs max-w-[200px] truncate" title="{{ $err->host }}{{ $err->path }}">
                            <span class="text-neutral-400">{{ $err->host }}</span>{{ $err->path }}
                        </td>
                        <td class="text-xs text-neutral-600 dark:text-neutral-400 max-w-[280px] truncate" title="{{ $err->error_message }}">
                            {{ $err->error_message }}
                        </td>
                        <td class="text-xs text-neutral-500">
                            {{ $err->user?->email ?? 'Guest' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-xs text-neutral-500 py-4">No error encounters in this timeframe.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
</x-layouts.admin>
