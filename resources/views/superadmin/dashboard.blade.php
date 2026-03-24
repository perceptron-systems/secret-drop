@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.superadmin_dashboard_title'))

@section('content')
<div class="flex-1 px-4 pt-0 md:p-8 transition-colors">
    <div class="max-w-7xl mx-auto">
        <div class="sticky top-0 z-40 -mx-4 md:-mx-8 px-4 md:px-8 py-3 sm:py-4 mb-4 bg-gray-50/95 dark:bg-slate-900/95 backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white inline-flex items-center gap-2">
                        {{ __('messages.superadmin_dashboard_title') }}
                        <svg id="pollRing" class="shrink-0 -rotate-90" width="24" height="24" viewBox="0 0 28 28">
                            <circle cx="14" cy="14" r="12" fill="none" stroke="currentColor" class="text-gray-200 dark:text-slate-700" stroke-width="2.5" />
                            <circle id="pollRingProgress" cx="14" cy="14" r="12" fill="none" stroke="currentColor" class="text-amber-500" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="75.4" stroke-dashoffset="75.4" />
                        </svg>
                    </h1>
                    <p class="mt-0.5 sm:mt-1 text-sm sm:text-base text-gray-600 dark:text-slate-400">{{ __('messages.superadmin_dashboard_subtitle') }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <form method="GET" class="flex items-center gap-2" x-data>
                        <select name="period" x-on:change="$el.form.submit()" aria-label="{{ __('messages.a11y_period_selector') }}" class="px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500/50">
                            <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>{{ __('messages.period_7d') }}</option>
                            <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>{{ __('messages.period_30d') }}</option>
                            <option value="90d" {{ $period === '90d' ? 'selected' : '' }}>{{ __('messages.period_90d') }}</option>
                            <option value="1y" {{ $period === '1y' ? 'selected' : '' }}>{{ __('messages.period_1y') }}</option>
                            <option value="all" {{ $period === 'all' ? 'selected' : '' }}>{{ __('messages.period_all') }}</option>
                        </select>
                    </form>
                    <form action="{{ route('superadmin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-gray-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition">
                            {{ __('messages.admin_logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @php
            $totals = $stats['totals'];

            if ($avgFirstReadDelay === null) {
                $formattedDelay = '-';
            } elseif ($avgFirstReadDelay < 60) {
                $formattedDelay = number_format($avgFirstReadDelay, 0) . 's';
            } elseif ($avgFirstReadDelay < 3600) {
                $formattedDelay = number_format($avgFirstReadDelay / 60, 1) . 'm';
            } elseif ($avgFirstReadDelay < 86400) {
                $formattedDelay = number_format($avgFirstReadDelay / 3600, 1) . 'h';
            } else {
                $formattedDelay = number_format($avgFirstReadDelay / 86400, 1) . 'j';
            }

            $formatBytes = function (int|float $bytes) {
                if ($bytes >= 1073741824) {
                    return number_format($bytes / 1073741824, 1) . ' ' . __('messages.unit_gigabytes');
                }
                if ($bytes >= 1048576) {
                    return number_format($bytes / 1048576, 1) . ' ' . __('messages.unit_megabytes');
                }
                if ($bytes >= 1024) {
                    return number_format($bytes / 1024, 1) . ' ' . __('messages.unit_kilobytes');
                }
                return $bytes . ' ' . __('messages.unit_bytes');
            };

        @endphp
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <x-stat-card kpi="secrets_created" :value="number_format(($totals['secrets_created_text'] ?? 0) + ($totals['secrets_created_file'] ?? 0))" :label="__('messages.stat_secrets_created')" />
            <x-stat-card kpi="secrets_read" :value="number_format($totals['secrets_read'] ?? 0)" :label="__('messages.stat_secrets_read')" />
            <x-stat-card kpi="active_secrets" :value="number_format($activeSecrets)" :label="__('messages.stat_active_secrets')" />
            <x-stat-card kpi="read_rate" :value="$readRate !== null ? number_format($readRate, 1) . '%' : '-'" :label="__('messages.stat_read_rate')" />
            <x-stat-card kpi="avg_first_read" :value="$formattedDelay" :label="__('messages.stat_avg_first_read')" />
            <x-stat-card kpi="files_shared" :value="number_format($totals['secrets_created_file'] ?? 0)" :label="__('messages.stat_files_shared')" />
            <x-stat-card kpi="volume" :value="$formatBytes($totals['total_file_size_bytes'] ?? 0)" :label="__('messages.stat_volume')" />
            <x-stat-card kpi="disk_usage" :value="$formatBytes($currentDiskUsage)" :label="__('messages.stat_current_disk_usage')" />
            <x-card class="p-6 overflow-visible relative z-10">
                <div class="text-3xl font-bold text-gray-900 dark:text-white" data-kpi="creators">{{ number_format($creatorConcentration['unique_creators']) }} <span class="text-lg font-normal text-gray-500 dark:text-slate-400">G={{ number_format($creatorConcentration['gini'], 2) }}</span></div>
                <div class="flex items-center gap-1 mt-1">
                    <span class="text-sm text-gray-600 dark:text-slate-400">{{ __('messages.stat_unique_creators') }}</span>
                    <x-hint-tooltip id="giniHint" :text="__('messages.stat_gini_tooltip')" direction="below" />
                </div>
            </x-card>
            <x-card class="p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white" data-kpi="health">{{ $systemHealth['active_secrets'] }} / {{ $systemHealth['pending_cleanup'] }}</div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_health_active_short') }} / {{ __('messages.stat_health_cleanup_short') }}</div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secrets_created') }}</h2>
                <div class="h-56">
                    <canvas id="secretsCreatedChart" role="img" aria-label="{{ __('messages.chart_secrets_created') }}"></canvas>
                </div>
            </x-card>
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secrets_read') }}</h2>
                <div class="h-56">
                    <canvas id="secretsReadChart" role="img" aria-label="{{ __('messages.chart_secrets_read') }}"></canvas>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_types') }}</h2>
                <canvas id="secretTypesChart" height="200" role="img" aria-label="{{ __('messages.chart_secret_types') }}"></canvas>
            </x-card>
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_options') }}</h2>
                <canvas id="secretOptionsChart" height="200" role="img" aria-label="{{ __('messages.chart_secret_options') }}"></canvas>
            </x-card>
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_outcomes') }}</h2>
                <canvas id="secretOutcomesChart" height="200" role="img" aria-label="{{ __('messages.chart_secret_outcomes') }}"></canvas>
            </x-card>
        </div>

        <x-card class="p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_admin_activity') }}</h2>
            <div class="h-56">
                <canvas id="adminActivityChart" role="img" aria-label="{{ __('messages.chart_admin_activity') }}"></canvas>
            </div>
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ __('messages.chart_heatmap_created') }}</h2>
                <p class="text-xs text-gray-400 dark:text-slate-500 mb-4">UTC — heure serveur</p>
                <div id="heatmapCreated" class="heatmap-container"></div>
            </x-card>
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ __('messages.chart_heatmap_read') }}</h2>
                <p class="text-xs text-gray-400 dark:text-slate-500 mb-4">UTC — heure serveur</p>
                <div id="heatmapRead" class="heatmap-container"></div>
            </x-card>
        </div>

        {{-- Pageviews section --}}
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-10 mb-6">{{ __('messages.stat_pageviews_title') }}</h2>

        {{-- Pageview KPIs --}}
        @php
            $totalViews = $pageviews['total_human'];
            $pvDates = array_keys($pageviews['daily']);
            $createdInPeriod = 0;
            $metricsData = $stats['metrics'] ?? [];
            foreach ($pvDates as $date) {
                $createdInPeriod += ($metricsData['secrets_created_text'][$date] ?? 0)
                    + ($metricsData['secrets_created_file'][$date] ?? 0);
            }
            $conversionRate = $totalViews > 0 ? ($createdInPeriod / $totalViews) * 100 : 0;
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <x-stat-card kpi="pv_visitors" :value="number_format($pageviews['total_human'])" :label="__('messages.stat_visitors')" />
            <x-stat-card kpi="pv_bots" :value="number_format($pageviews['total_bot'])" :label="__('messages.stat_bots')" />
            <x-stat-card kpi="pv_countries" :value="count($pageviews['by_country'])" :label="__('messages.stat_countries')" />
            <x-stat-card kpi="pv_conversion" :value="$totalViews > 0 ? number_format($conversionRate, 1) . '%' : '-'" :label="__('messages.stat_conversion')" />
        </div>

        {{-- Daily visits chart --}}
        <x-card class="p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_pageviews_title') }}</h3>
            <div class="h-56">
                <canvas id="pageviewsChart" role="img" aria-label="{{ __('messages.stat_pageviews_title') }}"></canvas>
            </div>
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- By page --}}
            @php
                $pageTitleMap = [
                    'home' => __('messages.stat_page_home'),
                    'how-it-works' => __('messages.how_it_works_title'),
                    'use-cases' => __('messages.use_cases_title'),
                    'legal' => __('messages.legal_title'),
                    'secrets.show' => __('messages.view_secret_title'),
                    'secrets.download' => __('messages.stat_page_download'),
                    'admin.index' => __('messages.stat_page_admin_login'),
                    'admin.dashboard' => __('messages.stat_page_admin_dashboard'),
                    'superadmin.index' => __('messages.stat_page_superadmin_login'),
                    'superadmin.dashboard' => __('messages.stat_page_superadmin_dashboard'),
                    // Legacy underscore variants
                    'admin' => __('messages.stat_page_admin_login'),
                    'admin_dashboard' => __('messages.stat_page_admin_dashboard'),
                    'superadmin' => __('messages.stat_page_superadmin_login'),
                    'superadmin_dashboard' => __('messages.stat_page_superadmin_dashboard'),
                ];

                // Add localized slugs from all locales
                $slugTitleKeys = [
                    'how-it-works' => 'messages.how_it_works_title',
                    'use-cases' => 'messages.use_cases_title',
                    'legal' => 'messages.legal_title',
                ];
                foreach (\App\Support\LocaleConfig::SUPPORTED_LOCALES as $loc) {
                    foreach ($slugTitleKeys as $routeName => $titleKey) {
                        $slug = trans("routes.{$routeName}", [], $loc);
                        if ($slug !== "routes.{$routeName}" && ! isset($pageTitleMap[$slug])) {
                            $pageTitleMap[$slug] = __($titleKey);
                        }
                    }
                }
            @endphp
            @php
                $mergedByPage = [];
                foreach ($pageviews['by_page'] as $page => $counts) {
                    $title = $pageTitleMap[$page] ?? $page;
                    $mergedByPage[$title] ??= ['human' => 0, 'bot' => 0];
                    $mergedByPage[$title]['human'] += $counts['human'];
                    $mergedByPage[$title]['bot'] += $counts['bot'];
                }
                uasort($mergedByPage, fn ($a, $b) => $b['human'] <=> $a['human']);
            @endphp
            <x-card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_by_page') }}</h3>
                <div id="pollByPage" class="space-y-2">
                    @foreach($mergedByPage as $title => $counts)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-slate-300">{{ $title }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-900 dark:text-white font-medium">{{ number_format($counts['human']) }}</span>
                                <span class="text-gray-400 dark:text-slate-500 text-xs">{{ number_format($counts['bot']) }} bot</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- By country (top 15) --}}
            <x-card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_by_country') }}</h3>
                <div id="pollByCountry" class="space-y-2">
                    @foreach(array_slice($pageviews['by_country'], 0, 15, true) as $country => $count)
                        @php
                            $pct = $pageviews['total_human'] > 0 ? ($count / $pageviews['total_human']) * 100 : 0;
                        @endphp
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-slate-300 font-medium">{{ $country }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-20 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-violet-500 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-gray-900 dark:text-white font-medium w-10 text-right">{{ number_format($count) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- By hour --}}
            <x-card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ __('messages.stat_by_hour') }}</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mb-4">UTC</p>
                <div id="pollByHour">
                    @php
                        $maxHour = max(1, max($pageviews['by_hour']));
                        $barMaxPx = 96;
                    @endphp
                    <div class="flex items-end gap-0.5" style="height: {{ $barMaxPx }}px">
                        @foreach($pageviews['by_hour'] as $hour => $count)
                            @php $heightPx = max(2, (int) (($count / $maxHour) * $barMaxPx)); @endphp
                            <div
                                class="flex-1 bg-violet-500/80 dark:bg-violet-400/80 rounded-t-sm transition-colors hover:bg-violet-600 dark:hover:bg-violet-300"
                                style="height: {{ $heightPx }}px"
                                title="{{ $hour }}h: {{ $count }}"
                            ></div>
                        @endforeach
                    </div>
                    <div class="flex gap-0.5 mt-1">
                        @foreach($pageviews['by_hour'] as $hour => $count)
                            <div class="flex-1 text-center text-[8px] text-gray-400 dark:text-slate-500">
                                @if($hour % 6 === 0){{ $hour }}@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-card>

            {{-- By local hour --}}
            <x-card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ __('messages.stat_by_local_hour') }}</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mb-4">{{ __('messages.stat_local_hour_note') }}</p>
                <div id="pollByLocalHour">
                    @php
                        $maxLocalHour = max(1, max($pageviews['by_local_hour']));
                        $localBarMaxPx = 96;
                    @endphp
                    <div class="flex items-end gap-0.5" style="height: {{ $localBarMaxPx }}px">
                        @foreach($pageviews['by_local_hour'] as $hour => $count)
                            @php $heightPx = max(2, (int) (($count / $maxLocalHour) * $localBarMaxPx)); @endphp
                            <div
                                class="flex-1 bg-amber-500/80 dark:bg-amber-400/80 rounded-t-sm transition-colors hover:bg-amber-600 dark:hover:bg-amber-300"
                                style="height: {{ $heightPx }}px"
                                title="{{ $hour }}h: {{ $count }}"
                            ></div>
                        @endforeach
                    </div>
                    <div class="flex gap-0.5 mt-1">
                        @foreach($pageviews['by_local_hour'] as $hour => $count)
                            <div class="flex-1 text-center text-[8px] text-gray-400 dark:text-slate-500">
                                @if($hour % 6 === 0){{ $hour }}@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-card>

            {{-- By language --}}
            <x-card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_by_language') }}</h3>
                <div id="pollByLanguage" class="space-y-2">
                    @foreach($pageviews['by_language'] as $locale => $count)
                        @php
                            $pct = $pageviews['total_human'] > 0 ? ($count / $pageviews['total_human']) * 100 : 0;
                            $flag = \App\Support\LocaleConfig::FLAGS[$locale] ?? '';
                            $name = \App\Support\LocaleConfig::NATIVE_NAMES[$locale] ?? strtoupper($locale);
                        @endphp
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-slate-300 font-medium">{{ $flag }} {{ $name }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-20 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-500 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-gray-900 dark:text-white font-medium w-10 text-right">{{ number_format($count) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- Referrers --}}
            <x-card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_by_referrer') }}</h3>
                <div id="pollByReferrer">
                @if(count($referrers) > 0)
                    <div class="space-y-2">
                        @php
                            $maxRefCount = max(1, max(array_column($referrers, 'human')));
                        @endphp
                        @foreach(array_slice($referrers, 0, 20, true) as $domain => $counts)
                            @php
                                $pct = $maxRefCount > 0 ? ($counts['human'] / $maxRefCount) * 100 : 0;
                            @endphp
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700 dark:text-slate-300 font-mono truncate max-w-xs">{{ $domain }}</span>
                                <div class="flex items-center gap-3">
                                    <div class="w-24 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-gray-900 dark:text-white font-medium w-10 text-right">{{ number_format($counts['human']) }}</span>
                                    <span class="text-gray-400 dark:text-slate-500 text-xs w-14 text-right">{{ number_format($counts['bot']) }} bot</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-500">{{ __('messages.stat_no_data') }}</p>
                @endif
                </div>
            </x-card>
        </div>
    </div>
</div>

<script nonce="@nonce">
    window.superAdminData = {
        pollUrl: '{{ route('superadmin.poll', ['locale' => app()->getLocale()]) }}',
        pollInterval: 30000,
        period: '{{ $period }}',
        stats: @json($stats),
        heatmapCreated: @json($heatmapCreated),
        heatmapRead: @json($heatmapRead),
        pageviewsDaily: @json($pageviews['daily']),
        avgFirstReadDelay: @json($avgFirstReadDelay),
        currentDiskUsage: @json($currentDiskUsage),
        activeSecrets: @json($activeSecrets),
        readRate: @json($readRate),
        creatorConcentration: @json($creatorConcentration),
        systemHealth: @json($systemHealth),
        referrers: @json($referrers),
        pageviews: @json($pageviews),
        pageTitleMap: @json($pageTitleMap),
        localeMap: @json(\App\Support\LocaleConfig::FLAGS),
        localeNames: @json(\App\Support\LocaleConfig::NATIVE_NAMES),
        noDataText: '{{ __('messages.stat_no_data') }}',
        translations: {
            stat_text: '{{ __('messages.stat_text') }}',
            stat_file: '{{ __('messages.stat_file') }}',
            stat_reads: '{{ __('messages.stat_reads') }}',
            stat_passphrase: '{{ __('messages.stat_passphrase') }}',
            stat_max_views: '{{ __('messages.stat_max_views') }}',
            stat_split_mode: '{{ __('messages.stat_split_mode') }}',
            stat_read: '{{ __('messages.stat_read') }}',
            stat_expired_unread: '{{ __('messages.stat_expired_unread') }}',
            stat_revoked: '{{ __('messages.stat_revoked') }}',
            stat_max_reached: '{{ __('messages.stat_max_reached') }}',
            stat_magic_links_requested: '{{ __('messages.stat_magic_links_requested') }}',
            stat_magic_links_used: '{{ __('messages.stat_magic_links_used') }}',
            stat_secrets_extended: '{{ __('messages.stat_secrets_extended') }}',
            days: [
                '{{ __('messages.day_sunday') }}',
                '{{ __('messages.day_monday') }}',
                '{{ __('messages.day_tuesday') }}',
                '{{ __('messages.day_wednesday') }}',
                '{{ __('messages.day_thursday') }}',
                '{{ __('messages.day_friday') }}',
                '{{ __('messages.day_saturday') }}'
            ],
            stat_visitors: '{{ __('messages.stat_visitors') }}',
            stat_bots: '{{ __('messages.stat_bots') }}'
        }
    };
</script>
@vite('resources/js/superadmin-dashboard.js')
@endsection
