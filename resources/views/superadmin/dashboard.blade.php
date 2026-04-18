@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.superadmin_dashboard_title'))

@section('content')
<div class="flex-1 px-4 pt-0 md:p-8 transition-colors">
    <div class="max-w-7xl mx-auto">
        <div class="sticky top-0 z-40 -mx-4 md:-mx-8 px-4 md:px-8 py-3 sm:py-4 mb-4 bg-gray-50/95 dark:bg-slate-900/95 backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 sm:gap-4">
                <div class="flex items-center gap-4">
                    <div class="logo-icon flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-amber-500/0 to-orange-600 shrink-0" style="--accent-rgb: 217, 119, 6">
                        <x-icon.chart-bar class="w-7 h-7 text-white" />
                    </div>
                    <div>
                    <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white inline-flex items-center gap-2">
                        {{ __('messages.superadmin_dashboard_title') }}
                        <x-poll-ring color="text-amber-500" />
                    </h1>
                    <p class="mt-0.5 sm:mt-1 text-sm sm:text-base text-gray-600 dark:text-slate-400">
                        {{ __('messages.superadmin_dashboard_subtitle') }}
                        @if ($version = app_version())
                            <span class="ml-2 font-mono text-xs text-gray-400 dark:text-slate-500" title="{{ $version['date'] }}">{{ $version['hash'] }}</span>
                        @endif
                    </p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <select id="periodSelector" aria-label="{{ __('messages.a11y_period_selector') }}" style="--accent-rgb: 217, 119, 6" class="px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500/50">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>{{ __('messages.period_today') }}</option>
                        <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>{{ __('messages.period_7d') }}</option>
                        <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>{{ __('messages.period_30d') }}</option>
                        <option value="90d" {{ $period === '90d' ? 'selected' : '' }}>{{ __('messages.period_90d') }}</option>
                        <option value="1y" {{ $period === '1y' ? 'selected' : '' }}>{{ __('messages.period_1y') }}</option>
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>{{ __('messages.period_all') }}</option>
                    </select>
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

            $formatDelay = function (?float $seconds): string {
                if ($seconds === null) {
                    return '-';
                }
                if ($seconds < 60) {
                    return nfmt($seconds) . 's';
                }
                if ($seconds < 3600) {
                    return nfmt($seconds / 60, 1) . 'm';
                }
                if ($seconds < 86400) {
                    return nfmt($seconds / 3600, 1) . 'h';
                }
                return nfmt($seconds / 86400, 1) . 'j';
            };

            $formattedAvgDelay = $formatDelay($avgFirstReadDelay);
            $formattedMedianDelay = $formatDelay($medianFirstReadDelay);

            $formatBytes = function (int|float $bytes) {
                if ($bytes >= 1073741824) {
                    return nfmt($bytes / 1073741824, 1) . ' ' . __('messages.unit_gigabytes');
                }
                if ($bytes >= 1048576) {
                    return nfmt($bytes / 1048576, 1) . ' ' . __('messages.unit_megabytes');
                }
                if ($bytes >= 1024) {
                    return nfmt($bytes / 1024, 1) . ' ' . __('messages.unit_kilobytes');
                }
                return nfmt($bytes) . ' ' . __('messages.unit_bytes');
            };

        @endphp
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <x-stat-card kpi="secrets_created" :value="nfmt(($totals['secrets_created_text'] ?? 0) + ($totals['secrets_created_file'] ?? 0))" :label="__('messages.stat_secrets_created')" />
            <x-stat-card kpi="secrets_read" :value="nfmt($totals['secrets_read'] ?? 0)" :label="__('messages.stat_secrets_read')" />
            <x-stat-card kpi="active_secrets" :value="nfmt($systemHealth['active_secrets'])" :label="__('messages.stat_active_secrets')" />
            <x-stat-card kpi="read_rate" :value="$readRate !== null ? nfmt($readRate, 1) . '%' : '-'" :label="__('messages.stat_read_rate')" />
            <x-stat-card :label="__('messages.stat_first_read_delay')" :hint="__('messages.hint_first_read_delay')" hintId="hintFirstReadDelay">
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white" data-kpi="median_first_read">{{ $formattedMedianDelay }}</span>
                    <span class="text-sm text-gray-500 dark:text-slate-400">{{ __('messages.stat_median_abbr') }}</span>
                </div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-lg font-semibold text-gray-500 dark:text-slate-400" data-kpi="avg_first_read">{{ $formattedAvgDelay }}</span>
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ __('messages.stat_avg_abbr') }}</span>
                </div>
            </x-stat-card>
            <x-stat-card kpi="files_shared" :value="nfmt($totals['secrets_created_file'] ?? 0)" :label="__('messages.stat_files_shared')" />
            <x-stat-card kpi="volume" :value="$formatBytes($totals['total_file_size_bytes'] ?? 0)" :label="__('messages.stat_volume')" />
            <x-stat-card kpi="disk_usage" :value="$formatBytes($currentDiskUsage)" :label="__('messages.stat_current_disk_usage')" />
            <x-stat-card kpi="creators" :label="__('messages.stat_unique_creators')" :hint="__('messages.stat_gini_tooltip')" hintId="giniHint">
                <div class="text-3xl font-bold text-gray-900 dark:text-white" data-kpi="creators">{{ nfmt($creatorConcentration['unique_creators']) }} <span class="text-lg font-normal text-gray-500 dark:text-slate-400">G={{ nfmt($creatorConcentration['gini'], 2) }}</span></div>
            </x-stat-card>
            <x-card class="p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white" data-kpi="health">{{ $systemHealth['pending_cleanup'] }} / {{ $systemHealth['total_files'] }}</div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_health_cleanup_short') }} / {{ __('messages.stat_health_files_short') }}</div>
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
                <div class="h-48">
                    <canvas id="secretTypesChart" role="img" aria-label="{{ __('messages.chart_secret_types') }}"></canvas>
                </div>
            </x-card>
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_options') }}</h2>
                <div class="h-48">
                    <canvas id="secretOptionsChart" role="img" aria-label="{{ __('messages.chart_secret_options') }}"></canvas>
                </div>
            </x-card>
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_outcomes') }}</h2>
                <div class="h-48">
                    <canvas id="secretOutcomesChart" role="img" aria-label="{{ __('messages.chart_secret_outcomes') }}"></canvas>
                </div>
            </x-card>
        </div>

        <x-card class="p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_admin_activity') }}</h2>
            <div class="h-56">
                <canvas id="adminActivityChart" role="img" aria-label="{{ __('messages.chart_admin_activity') }}"></canvas>
            </div>
        </x-card>

        {{-- Monitoring section --}}
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-10 mb-6">{{ __('messages.monitoring_title') }}</h2>

        @php
            $fmtP95 = function (?float $ms): string {
                if ($ms === null) {
                    return '-';
                }
                if ($ms < 1000) {
                    return nfmt($ms) . ' ms';
                }
                return nfmt($ms / 1000, 1) . ' s';
            };
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <x-stat-card kpi="errors_4xx" :value="nfmt($errorStats['total_4xx'])" :label="__('messages.stat_errors_4xx')" :hint="__('messages.hint_errors_4xx')" hintId="hint4xx" />
            <x-stat-card kpi="errors_5xx" :value="nfmt($errorStats['total_5xx'])" :label="__('messages.stat_errors_5xx')" :hint="__('messages.hint_errors_5xx')" hintId="hint5xx" />
            <x-stat-card kpi="errors_422" :value="nfmt($errorStats['by_code'][422] ?? 0)" :label="__('messages.stat_errors_422')" :hint="__('messages.hint_errors_422')" hintId="hint422" />
            <x-stat-card kpi="errors_429" :value="nfmt($errorStats['by_code'][429] ?? 0)" :label="__('messages.stat_errors_429')" :hint="__('messages.hint_errors_429')" hintId="hint429" />
            <x-stat-card kpi="response_p95" :value="$fmtP95($responseTime['p95'])" :label="__('messages.stat_response_p95')" :hint="__('messages.hint_response_p95')" hintId="hintP95" hintPosition="end" />
            <x-stat-card :label="__('messages.stat_avg_secret_size')" :hint="__('messages.hint_avg_secret_size')" hintId="hintSize" hintPosition="end">
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white" data-kpi="avg_size_text">{{ $avgSecretSize['text'] !== null ? $formatBytes($avgSecretSize['text']) : '-' }}</span>
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ __('messages.stat_text') }}</span>
                </div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-lg font-semibold text-gray-500 dark:text-slate-400" data-kpi="avg_size_file">{{ $avgSecretSize['file'] !== null ? $formatBytes($avgSecretSize['file']) : '-' }}</span>
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ __('messages.stat_file') }}</span>
                </div>
            </x-stat-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_error_trends') }}</h2>
                <div class="h-56">
                    <canvas id="errorTrendsChart" role="img" aria-label="{{ __('messages.chart_error_trends') }}"></canvas>
                </div>
            </x-card>
            <x-card class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_error_breakdown') }}</h2>
                <div id="pollErrorCodes" class="space-y-2">
                    @php
                        $byCode = $errorStats['by_code'] ?? [];
                        $maxError = max(1, count($byCode) > 0 ? max($byCode) : 1);
                    @endphp
                    @forelse($byCode as $code => $count)
                        @php
                            $pct = ($count / $maxError) * 100;
                            $color = $code >= 500 ? 'bg-red-500' : ($code >= 429 ? 'bg-orange-500' : ($code >= 422 ? 'bg-amber-500' : 'bg-gray-500'));
                        @endphp
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-slate-300 font-mono font-medium w-10">{{ $code }}</span>
                            <div class="flex items-center gap-2 flex-1 ml-3">
                                <div class="flex-1 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full {{ $color }} rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-gray-900 dark:text-white font-medium w-12 text-right">{{ nfmt($count) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-slate-500">{{ __('messages.no_errors') }}</p>
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- 5xx by route --}}
            <x-card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_5xx_by_route') }}</h3>
                <div id="pollErrorRoutes" class="space-y-2">
                    @php
                        $byRoute = $errorStats['by_route'] ?? [];
                        $routeLabels = [
                            'home' => __('messages.stat_page_home'),
                            'secrets.store' => __('messages.stat_route_create'),
                            'secrets.show' => __('messages.stat_route_read'),
                            'secrets.fetch' => __('messages.stat_route_read') . ' (API)',
                            'secrets.confirmRead' => __('messages.stat_route_confirm_read'),
                            'secrets.download' => __('messages.stat_route_download'),
                            'admin.index' => 'Admin',
                            'admin.dashboard' => 'Admin dashboard',
                            'admin.poll' => 'Admin poll',
                            'admin.requestAccess' => 'Admin login',
                            'admin.verify' => 'Admin verify',
                            'admin.extend' => 'Admin extend',
                            'admin.revoke' => 'Admin revoke',
                            'superadmin.index' => 'Superadmin',
                            'superadmin.dashboard' => 'Superadmin dashboard',
                            'superadmin.poll' => 'Superadmin poll',
                            'superadmin.requestAccess' => 'Superadmin login',
                            'superadmin.verify' => 'Superadmin verify',
                        ];
                    @endphp
                    @forelse($byRoute as $route => $statuses)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-slate-300 truncate max-w-xs">{{ $routeLabels[$route] ?? $route }}</span>
                            <div class="flex items-center gap-2">
                                @foreach($statuses as $code => $count)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-mono font-medium">{{ $code }}</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ nfmt($count) }}</span>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-slate-500">{{ __('messages.no_errors') }}</p>
                    @endforelse
                </div>
            </x-card>

            {{-- P95 by route group --}}
            @php
                $groupLabels = [
                    'create' => __('messages.stat_route_create'),
                    'read' => __('messages.stat_route_read'),
                    'admin' => 'Admin',
                    'superadmin' => 'Superadmin',
                    'pages' => __('messages.stat_group_pages'),
                ];
            @endphp
            <x-card class="p-6">
                <div class="flex items-center gap-1 mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('messages.stat_p95_by_group') }}</h3>
                    <x-hint-tooltip id="hintP95Group" :text="__('messages.hint_response_p95')" direction="below" position="end" />
                </div>
                <div id="pollP95Groups" class="space-y-2">
                    @forelse($responseTime['by_group'] as $group => $p95)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-slate-300">{{ $groupLabels[$group] ?? $group }}</span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ $fmtP95($p95) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-slate-500">-</p>
                    @endforelse
                </div>
            </x-card>
        </div>

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
            <x-stat-card kpi="pv_visitors" :value="nfmt($pageviews['total_human'])" :label="__('messages.stat_visitors')" />
            <x-stat-card kpi="pv_bots" :value="nfmt($pageviews['total_bot'])" :label="__('messages.stat_bots')" />
            <x-stat-card kpi="pv_countries" :value="count($pageviews['by_country'])" :label="__('messages.stat_countries')" />
            <x-stat-card kpi="pv_conversion" :value="$totalViews > 0 ? nfmt($conversionRate, 1) . '%' : '-'" :label="__('messages.stat_conversion')" :hint="__('messages.hint_conversion')" />
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
                    'faq' => __('messages.faq_title'),
                    'secrets.show' => __('messages.view_secret_title'),
                    'secrets.download' => __('messages.stat_page_download'),
                    'admin.index' => __('messages.stat_page_admin_login'),
                    'admin.dashboard' => __('messages.stat_page_admin_dashboard'),
                    'superadmin.index' => __('messages.stat_page_superadmin_login'),
                    'superadmin.dashboard' => __('messages.stat_page_superadmin_dashboard'),
                    'admin.verify' => __('messages.stat_page_admin_verify'),
                    'admin.accessSent' => __('messages.stat_page_admin_access_sent'),
                    'superadmin.verify' => __('messages.stat_page_superadmin_verify'),
                    'superadmin.accessSent' => __('messages.stat_page_superadmin_access_sent'),
                    'page.show' => __('messages.stat_page_content'),
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
                    'faq' => 'messages.faq_title',
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
                                <span class="text-gray-900 dark:text-white font-medium">{{ nfmt($counts['human']) }}</span>
                                <span class="text-gray-400 dark:text-slate-500 text-xs">{{ nfmt($counts['bot']) }} bot</span>
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
                                <span class="text-gray-900 dark:text-white font-medium w-10 text-right">{{ nfmt($count) }}</span>
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
                                <span class="text-gray-900 dark:text-white font-medium w-10 text-right">{{ nfmt($count) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- By device --}}
            <x-card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_by_device') }}</h3>
                <div id="pollByDevice">
                @if(count($deviceStats) > 0)
                    @php
                        $totalDevices = max(1, array_sum($deviceStats));
                        $deviceIconComponents = ['desktop' => 'icon.desktop', 'mobile' => 'icon.mobile', 'tablet' => 'icon.tablet'];
                        $deviceLabels = ['desktop' => __('messages.stat_device_desktop'), 'mobile' => __('messages.stat_device_mobile'), 'tablet' => __('messages.stat_device_tablet')];
                    @endphp
                    <div class="space-y-3">
                        @foreach($deviceStats as $device => $count)
                            @php $pct = ($count / $totalDevices) * 100; @endphp
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="text-gray-700 dark:text-slate-300 font-medium flex items-center gap-1.5">
                                        @if(isset($deviceIconComponents[$device]))
                                            <x-dynamic-component :component="$deviceIconComponents[$device]" class="w-4 h-4" />
                                        @endif
                                        {{ $deviceLabels[$device] ?? $device }}
                                    </span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ nfmt($count) }} <span class="text-gray-400 dark:text-slate-500 text-xs">({{ nfmt($pct, 1) }}%)</span></span>
                                </div>
                                <div class="w-full h-2 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-500">{{ __('messages.stat_no_data') }}</p>
                @endif
                </div>
            </x-card>

            {{-- Bots --}}
            <x-card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_by_bot') }}</h3>
                <div id="pollByBot">
                @if(count($botStats) > 0)
                    <div class="space-y-2">
                        @php
                            $maxBotCount = max(1, max($botStats));
                        @endphp
                        @foreach(array_slice($botStats, 0, 20, true) as $botName => $count)
                            @php
                                $pct = ($count / $maxBotCount) * 100;
                            @endphp
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700 dark:text-slate-300 font-medium">{{ $botName }}</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-20 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-sky-500 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-gray-900 dark:text-white font-medium w-10 text-right">{{ nfmt($count) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-500">{{ __('messages.stat_no_data') }}</p>
                @endif
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
                                    <span class="text-gray-900 dark:text-white font-medium w-10 text-right">{{ nfmt($counts['human']) }}</span>
                                    <span class="text-gray-400 dark:text-slate-500 text-xs w-14 text-right">{{ nfmt($counts['bot']) }} bot</span>
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
        locale: '{{ app()->getLocale() }}',
        period: '{{ $period }}',
        stats: @json($stats),
        heatmapCreated: @json($heatmapCreated),
        heatmapRead: @json($heatmapRead),
        pageviewsDaily: @json($pageviews['daily']),
        avgFirstReadDelay: @json($avgFirstReadDelay),
        medianFirstReadDelay: @json($medianFirstReadDelay),
        currentDiskUsage: @json($currentDiskUsage),
        readRate: @json($readRate),
        creatorConcentration: @json($creatorConcentration),
        systemHealth: @json($systemHealth),
        referrers: @json($referrers),
        botStats: @json($botStats),
        deviceStats: @json($deviceStats),
        pageviews: @json($pageviews),
        pageTitleMap: @json($pageTitleMap),
        routeLabels: @json($routeLabels),
        groupLabels: @json($groupLabels),
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
        },
        deviceLabels: {
            desktop: '{{ __('messages.stat_device_desktop') }}',
            mobile: '{{ __('messages.stat_device_mobile') }}',
            tablet: '{{ __('messages.stat_device_tablet') }}'
        },
        errorStats: @json($errorStats),
        responseTime: @json($responseTime),
        avgSecretSize: @json($avgSecretSize),
        errorTranslations: {
            errors_4xx: '{{ __('messages.stat_errors_4xx') }}',
            errors_5xx: '{{ __('messages.stat_errors_5xx') }}',
            no_errors: '{{ __('messages.no_errors') }}'
        }
    };
</script>
@vite('resources/js/superadmin-dashboard.js')
@endsection
