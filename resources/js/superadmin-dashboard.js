import Chart from 'chart.js/auto';
import { startRing, resetRing } from './utils/poll-ring.js';

const charts = {};
let pollTimer = null;

// ── Helpers ──────────────────────────────────────────────────────────────

function getLocale() {
    return window.superAdminData?.locale || 'en';
}

function fmt(n) {
    return new Intl.NumberFormat(getLocale()).format(n);
}

function fmtDec(n, decimals = 1) {
    return new Intl.NumberFormat(getLocale(), { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(n);
}

function fmtBytes(bytes) {
    if (bytes >= 1073741824) {
        return fmtDec(bytes / 1073741824) + ' GB';
    }
    if (bytes >= 1048576) {
        return fmtDec(bytes / 1048576) + ' MB';
    }
    if (bytes >= 1024) {
        return fmtDec(bytes / 1024) + ' KB';
    }
    return fmt(Math.round(bytes)) + ' B';
}

function fmtDelay(s) {
    if (s === null) {
        return '-';
    }
    if (s < 60) {
        return fmt(Math.round(s)) + 's';
    }
    if (s < 3600) {
        return fmtDec(s / 60) + 'm';
    }
    if (s < 86400) {
        return fmtDec(s / 3600) + 'h';
    }
    return fmtDec(s / 86400) + 'j';
}

function fmtMs(ms) {
    if (ms === null || ms === undefined) {
        return '-';
    }
    if (ms < 1000) {
        return fmt(Math.round(ms)) + ' ms';
    }
    return fmtDec(ms / 1000) + ' s';
}

function kpi(name, value) {
    const el = document.querySelector(`[data-kpi="${name}"]`);
    if (el) {
        el.textContent = value;
    }
}

function kpiHtml(name, html) {
    const el = document.querySelector(`[data-kpi="${name}"]`);
    if (el) {
        el.innerHTML = html;
    }
}

function dateRange(start, end) {
    const labels = [];
    const d = new Date(start + 'T00:00:00Z');
    const e = new Date(end + 'T00:00:00Z');
    while (d <= e) {
        labels.push(d.toISOString().split('T')[0]);
        d.setUTCDate(d.getUTCDate() + 1);
    }
    return labels;
}

function metricData(metrics, metric, labels) {
    const m = metrics[metric] || {};
    return labels.map(d => m[d] || 0);
}

function toOptionPct(t) {
    const total = (t.secrets_created_text || 0) + (t.secrets_created_file || 0);
    if (total === 0) {
        return [0, 0, 0];
    }
    return [
        +((t.secrets_with_passphrase || 0) / total * 100).toFixed(1),
        +((t.secrets_with_max_views || 0) / total * 100).toFixed(1),
        +((t.secrets_split_mode || 0) / total * 100).toFixed(1),
    ];
}

// ── KPI update ───────────────────────────────────────────────────────────

function updateKpis(data) {
    const t = data.stats.totals;
    const created = (t.secrets_created_text || 0) + (t.secrets_created_file || 0);

    kpi('secrets_created', fmt(created));
    kpi('secrets_read', fmt(t.secrets_read || 0));
    kpi('active_secrets', fmt(data.systemHealth?.active_secrets ?? 0));
    kpi('read_rate', data.readRate !== null ? fmtDec(data.readRate) + '%' : '-');
    kpi('avg_first_read', fmtDelay(data.avgFirstReadDelay));
    kpi('median_first_read', fmtDelay(data.medianFirstReadDelay));
    kpi('files_shared', fmt(t.secrets_created_file || 0));
    kpi('volume', fmtBytes(t.total_file_size_bytes || 0));
    kpi('disk_usage', fmtBytes(data.currentDiskUsage));

    const cc = data.creatorConcentration;
    kpiHtml('creators',
        `${fmt(cc.unique_creators)} <span class="text-lg font-normal text-gray-500 dark:text-slate-400">G=${fmtDec(cc.gini, 2)}</span>`
    );

    const h = data.systemHealth;
    kpi('health', `${h.pending_cleanup} / ${h.total_files}`);

    const err = data.errorStats || {};
    const byCode = err.by_code || {};
    kpi('errors_4xx', fmt(err.total_4xx || 0));
    kpi('errors_5xx', fmt(err.total_5xx || 0));
    kpi('errors_422', fmt(byCode[422] || 0));
    kpi('errors_429', fmt(byCode[429] || 0));
    kpi('errors_total', fmt((err.total_4xx || 0) + (err.total_5xx || 0)));

    kpi('response_p95', fmtMs(data.responseTime?.p95));
    kpi('avg_size_text', data.avgSecretSize?.text !== null ? fmtBytes(data.avgSecretSize.text) : '-');
    kpi('avg_size_file', data.avgSecretSize?.file !== null ? fmtBytes(data.avgSecretSize.file) : '-');

    const pv = data.pageviews;
    kpi('pv_visitors', fmt(pv.total_human));
    kpi('pv_bots', fmt(pv.total_bot));
    kpi('pv_countries', Object.keys(pv.by_country).length);

    const pvDates = Object.keys(pv.daily);
    let createdInPeriod = 0;
    const metrics = data.stats.metrics || {};
    pvDates.forEach(date => {
        createdInPeriod += (metrics.secrets_created_text?.[date] || 0)
            + (metrics.secrets_created_file?.[date] || 0);
    });
    const conv = pv.total_human > 0 ? (createdInPeriod / pv.total_human) * 100 : 0;
    kpi('pv_conversion', pv.total_human > 0 ? fmtDec(conv) + '%' : '-');
}

// ── Chart helpers ────────────────────────────────────────────────────────

function updateLineChart(key, labels, datasets) {
    const chart = charts[key];
    if (!chart) {
        return;
    }
    chart.data.labels = labels;
    datasets.forEach((ds, i) => {
        if (chart.data.datasets[i]) {
            chart.data.datasets[i].data = ds;
        }
    });
    chart.update('none');
}

function updateDoughnutChart(key, dataArr) {
    const chart = charts[key];
    if (!chart) {
        return;
    }
    chart.data.datasets[0].data = dataArr;
    chart.update('none');
}

function updateBarChart(key, dataArr) {
    const chart = charts[key];
    if (!chart) {
        return;
    }
    chart.data.datasets[0].data = dataArr;
    chart.update('none');
}

// ── Chart update (in-place, no destroy) ──────────────────────────────────

function updateCharts(data) {
    const stats = data.stats;
    const t = stats.totals;
    const labels = dateRange(stats.start_date, stats.end_date);
    const m = stats.metrics;

    updateLineChart('created', labels, [
        metricData(m, 'secrets_created_text', labels),
        metricData(m, 'secrets_created_file', labels),
    ]);

    updateLineChart('read', labels, [
        metricData(m, 'secrets_read', labels),
    ]);

    updateDoughnutChart('types', [t.secrets_created_text || 0, t.secrets_created_file || 0]);

    updateBarChart('options', toOptionPct(t));

    updateDoughnutChart('outcomes', [
        t.secrets_read || 0,
        t.secrets_expired_unread || 0,
        t.secrets_revoked || 0,
        t.secrets_max_views_reached || 0,
    ]);

    updateLineChart('admin', labels, [
        metricData(m, 'magic_links_requested', labels),
        metricData(m, 'magic_links_used', labels),
        metricData(m, 'secrets_extended', labels),
    ]);

    // Error trends
    updateLineChart('errors', labels, [
        metricData(m, 'http_errors_4xx', labels),
        metricData(m, 'http_errors_5xx', labels),
    ]);

    // Pageviews daily
    const daily = data.pageviews.daily;
    const pvDates = Object.keys(daily).sort();
    updateLineChart('pageviews', pvDates, [
        pvDates.map(d => daily[d]?.human || 0),
        pvDates.map(d => daily[d]?.bot || 0),
    ]);

    // Heatmaps (innerHTML-based, safe to re-render)
    const tr = window.superAdminData.translations;
    if (data.heatmapCreated) {
        renderHeatmap('heatmapCreated', data.heatmapCreated, 'violet', tr);
    }
    if (data.heatmapRead) {
        renderHeatmap('heatmapRead', data.heatmapRead, 'green', tr);
    }

    // Server-rendered lists
    updateLists(data);
}

// ── List renderers (by page, country, language, hours, referrers) ────────

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function updateLists(data) {
    const sd = window.superAdminData;
    const pv = data.pageviews;

    // By page (merged by title)
    const el = document.getElementById('pollByPage');
    if (el) {
        const map = sd.pageTitleMap || {};
        const merged = {};
        for (const [page, counts] of Object.entries(pv.by_page)) {
            const title = map[page] || page;
            if (!merged[title]) {
                merged[title] = { human: 0, bot: 0 };
            }
            merged[title].human += counts.human;
            merged[title].bot += counts.bot;
        }
        const sorted = Object.entries(merged).sort((a, b) => b[1].human - a[1].human);
        el.innerHTML = sorted.map(([title, c]) =>
            `<span class="text-gray-700 dark:text-slate-300">${esc(title)}</span>
            <span class="text-right text-gray-900 dark:text-white font-medium tabular-nums">${fmt(c.human)}</span>
            <span class="text-right text-gray-400 dark:text-slate-500 text-xs tabular-nums">${fmt(c.bot)} bot</span>`
        ).join('');
    }

    // By country (top 15)
    const elC = document.getElementById('pollByCountry');
    if (elC) {
        const sorted = Object.entries(pv.by_country).sort((a, b) => b[1] - a[1]).slice(0, 15);
        const total = pv.total_human || 1;
        elC.innerHTML = sorted.map(([country, count]) => {
            const pct = Math.min((count / total) * 100, 100);
            return `<div class="flex items-center justify-between text-sm">
                <span class="text-gray-700 dark:text-slate-300 font-medium">${esc(country)}</span>
                <div class="flex items-center gap-2">
                    <div class="w-20 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-violet-500 rounded-full" style="width: ${pct}%"></div>
                    </div>
                    <span class="text-gray-900 dark:text-white font-medium w-10 text-right">${fmt(count)}</span>
                </div>
            </div>`;
        }).join('');
    }

    // By language
    const elL = document.getElementById('pollByLanguage');
    if (elL) {
        const flags = sd.localeMap || {};
        const names = sd.localeNames || {};
        const total = pv.total_human || 1;
        const sorted = Object.entries(pv.by_language).sort((a, b) => b[1] - a[1]);
        elL.innerHTML = sorted.map(([locale, count]) => {
            const pct = Math.min((count / total) * 100, 100);
            const flag = flags[locale] || '';
            const name = names[locale] || locale.toUpperCase();
            return `<div class="flex items-center justify-between text-sm">
                <span class="text-gray-700 dark:text-slate-300 font-medium">${flag} ${esc(name)}</span>
                <div class="flex items-center gap-2">
                    <div class="w-20 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 rounded-full" style="width: ${pct}%"></div>
                    </div>
                    <span class="text-gray-900 dark:text-white font-medium w-10 text-right">${fmt(count)}</span>
                </div>
            </div>`;
        }).join('');
    }

    // By hour (bar chart)
    renderBarList('pollByHour', pv.by_hour, 'bg-violet-500/80 dark:bg-violet-400/80');
    renderBarList('pollByLocalHour', pv.by_local_hour, 'bg-amber-500/80 dark:bg-amber-400/80');

    // By device
    const elD = document.getElementById('pollByDevice');
    if (elD) {
        const devices = data.deviceStats;
        const entries = Object.entries(devices).sort((a, b) => b[1] - a[1]);
        if (entries.length === 0) {
            elD.innerHTML = `<p class="text-sm text-gray-500 dark:text-slate-500">${esc(sd.noDataText || '')}</p>`;
        } else {
            const totalDevices = Math.max(1, entries.reduce((s, e) => s + e[1], 0));
            const labels = sd.deviceLabels || {};
            const svgIcons = {
                desktop: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>',
                mobile: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>',
                tablet: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>',
            };
            elD.innerHTML = '<div class="space-y-3">' + entries.map(([device, count]) => {
                const pct = (count / totalDevices) * 100;
                const icon = svgIcons[device] || '';
                const label = labels[device] || device;
                return `<div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-700 dark:text-slate-300 font-medium flex items-center gap-1.5">${icon} ${esc(label)}</span>
                        <span class="text-gray-900 dark:text-white font-medium">${fmt(count)} <span class="text-gray-400 dark:text-slate-500 text-xs">(${fmtDec(pct)}%)</span></span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full" style="width: ${Math.min(pct, 100)}%"></div>
                    </div>
                </div>`;
            }).join('') + '</div>';
        }
    }

    // By bot
    const elB = document.getElementById('pollByBot');
    if (elB) {
        const bots = data.botStats;
        const entries = Object.entries(bots).sort((a, b) => b[1] - a[1]).slice(0, 20);
        if (entries.length === 0) {
            elB.innerHTML = `<p class="text-sm text-gray-500 dark:text-slate-500">${esc(sd.noDataText || '')}</p>`;
        } else {
            const maxBot = Math.max(1, Math.max(...entries.map(e => e[1])));
            elB.innerHTML = '<div class="space-y-2">' + entries.map(([botName, count]) => {
                const pct = Math.min((count / maxBot) * 100, 100);
                return `<div class="flex items-center justify-between text-sm">
                    <span class="text-gray-700 dark:text-slate-300 font-medium">${esc(botName)}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-20 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-sky-500 rounded-full" style="width: ${pct}%"></div>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium w-10 text-right">${fmt(count)}</span>
                    </div>
                </div>`;
            }).join('') + '</div>';
        }
    }

    // Referrers
    const elR = document.getElementById('pollByReferrer');
    if (elR) {
        const refs = data.referrers;
        const entries = Object.entries(refs).sort((a, b) => b[1].human - a[1].human).slice(0, 20);
        if (entries.length === 0) {
            elR.innerHTML = `<p class="text-sm text-gray-500 dark:text-slate-500">${esc(sd.noDataText || '')}</p>`;
        } else {
            const maxRef = Math.max(1, Math.max(...entries.map(e => e[1].human)));
            elR.innerHTML = '<div class="space-y-2">' + entries.map(([domain, c]) => {
                const pct = Math.min((c.human / maxRef) * 100, 100);
                return `<div class="flex items-center justify-between text-sm">
                    <span class="text-gray-700 dark:text-slate-300 font-mono truncate max-w-xs">${esc(domain)}</span>
                    <div class="flex items-center gap-3">
                        <div class="w-24 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: ${pct}%"></div>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium w-10 text-right">${fmt(c.human)}</span>
                        <span class="text-gray-400 dark:text-slate-500 text-xs w-14 text-right">${fmt(c.bot)} bot</span>
                    </div>
                </div>`;
            }).join('') + '</div>';
        }
    }

    // Error codes (list only received codes)
    const elErr = document.getElementById('pollErrorCodes');
    if (elErr) {
        const byCode = (data.errorStats || {}).by_code || {};
        const entries = Object.entries(byCode)
            .map(([code, count]) => ({ code, count }))
            .sort((a, b) => b.count - a.count);

        if (entries.length === 0) {
            const et = window.superAdminData?.errorTranslations || {};
            elErr.innerHTML = `<p class="text-sm text-gray-400 dark:text-slate-500">${et.no_errors || 'No errors'}</p>`;
        } else {
            const maxErr = Math.max(1, ...entries.map(e => e.count));
            elErr.innerHTML = entries.map(({ code, count }) => {
                const pct = Math.min((count / maxErr) * 100, 100);
                const color = code >= 500 ? 'bg-red-500' : (code >= 429 ? 'bg-orange-500' : (code >= 422 ? 'bg-amber-500' : 'bg-gray-500'));
                return `<div class="flex items-center justify-between text-sm">
                    <span class="text-gray-700 dark:text-slate-300 font-mono font-medium w-10">${code}</span>
                    <div class="flex items-center gap-2 flex-1 ml-3">
                        <div class="flex-1 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full ${color} rounded-full" style="width: ${pct}%"></div>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium w-12 text-right">${fmt(count)}</span>
                    </div>
                </div>`;
            }).join('');
        }
    }

    // 5xx by route
    const elRoutes = document.getElementById('pollErrorRoutes');
    if (elRoutes) {
        const byRoute = (data.errorStats || {}).by_route || {};
        const entries = Object.entries(byRoute).sort((a, b) => {
            const totalA = Object.values(a[1]).reduce((s, v) => s + v, 0);
            const totalB = Object.values(b[1]).reduce((s, v) => s + v, 0);
            return totalB - totalA;
        });
        if (entries.length === 0) {
            const et = window.superAdminData?.errorTranslations || {};
            elRoutes.innerHTML = `<p class="text-sm text-gray-400 dark:text-slate-500">${et.no_errors || 'No errors'}</p>`;
        } else {
            const rl = window.superAdminData?.routeLabels || {};
            elRoutes.innerHTML = entries.map(([route, statuses]) => {
                const label = rl[route] || route;
                const codes = Object.entries(statuses).map(([code, count]) =>
                    `<span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-mono font-medium">${code}</span>
                     <span class="text-gray-900 dark:text-white font-medium">${fmt(count)}</span>`
                ).join(' ');
                return `<div class="flex items-center justify-between text-sm">
                    <span class="text-gray-700 dark:text-slate-300 truncate max-w-xs">${esc(label)}</span>
                    <div class="flex items-center gap-2">${codes}</div>
                </div>`;
            }).join('');
        }
    }

    // P95 by route group
    const elP95 = document.getElementById('pollP95Groups');
    if (elP95) {
        const byGroup = (data.responseTime || {}).by_group || {};
        const entries = Object.entries(byGroup);
        if (entries.length === 0) {
            elP95.innerHTML = '<p class="text-sm text-gray-400 dark:text-slate-500">-</p>';
        } else {
            const gl = window.superAdminData?.groupLabels || {};
            elP95.innerHTML = entries.map(([group, p95]) =>
                `<div class="flex items-center justify-between text-sm">
                    <span class="text-gray-700 dark:text-slate-300">${esc(gl[group] || group)}</span>
                    <span class="text-gray-900 dark:text-white font-medium">${fmtMs(p95)}</span>
                </div>`
            ).join('');
        }
    }
}

function renderBarList(id, hourData, barClass) {
    const el = document.getElementById(id);
    if (!el) {
        return;
    }
    const maxPx = 96;
    const maxVal = Math.max(1, ...Object.values(hourData));
    let bars = `<div class="flex items-end gap-0.5" style="height: ${maxPx}px">`;
    let labels = '<div class="flex gap-0.5 mt-1">';
    for (let h = 0; h < 24; h++) {
        const count = hourData[h] || 0;
        const height = Math.max(2, Math.round((count / maxVal) * maxPx));
        bars += `<div class="flex-1 ${barClass} rounded-t-sm" style="height: ${height}px" title="${h}h: ${count}"></div>`;
        labels += `<div class="flex-1 text-center text-[8px] text-gray-400 dark:text-slate-500">${h % 6 === 0 ? h : ''}</div>`;
    }
    bars += '</div>';
    labels += '</div>';
    el.innerHTML = bars + labels;
}

// ── Heatmap renderer ─────────────────────────────────────────────────────

function renderHeatmap(containerId, heatmapData, color, translations) {
    const container = document.getElementById(containerId);
    if (!container) {
        return;
    }

    const dayOrder = [1, 2, 3, 4, 5, 6, 0];
    const allDays = translations.days || ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    const days = dayOrder.map(i => allDays[i]);

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    let maxValue = 0;
    for (let day = 0; day < 7; day++) {
        for (let hour = 0; hour < 24; hour++) {
            const val = heatmapData[day]?.[hour] || 0;
            if (val > maxValue) {
                maxValue = val;
            }
        }
    }

    function cls(val, max, c) {
        if (val === 0) {
            return 'heatmap-empty';
        }
        const level = Math.min(6, Math.max(1, Math.ceil((max > 0 ? val / max : 0) * 6)));
        return `heatmap-${c}-${level}`;
    }

    let html = '<div class="overflow-x-auto"><table class="w-full border-collapse text-xs">';
    html += '<tr><td class="w-8"></td>';
    for (let i = 0; i < 7; i++) {
        html += `<td class="text-center text-gray-600 dark:text-slate-400 pb-1 font-medium">${esc(days[i])}</td>`;
    }
    html += '</tr>';

    for (let hour = 0; hour < 24; hour++) {
        html += `<tr><td class="text-right pr-2 text-gray-500 dark:text-slate-500 text-[10px]">${hour}h</td>`;
        for (let i = 0; i < 7; i++) {
            const di = dayOrder[i];
            const val = heatmapData[di]?.[hour] || 0;
            const title = `${esc(days[i])} ${hour}h: ${val}`;
            html += `<td class="p-px"><div class="h-3 rounded-sm flex items-center justify-center text-[8px] leading-none ${cls(val, maxValue, color)}" title="${title}">${val > 0 ? val : ''}</div></td>`;
        }
        html += '</tr>';
    }

    html += '</table></div>';
    container.innerHTML = html;
}

// ── Initial chart creation ───────────────────────────────────────────────

function initDashboard() {
    Object.values(charts).forEach(c => c.destroy());
    for (const k in charts) {
        delete charts[k];
    }

    const data = window.superAdminData;
    if (!data) {
        return;
    }

    const { stats, heatmapCreated, heatmapRead, translations } = data;
    const t = stats.totals;

    const isDark = document.documentElement.classList.contains('dark');
    Chart.defaults.color = isDark ? '#94a3b8' : '#6b7280';
    Chart.defaults.borderColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    const scales = {
        x: { display: true, grid: { display: false } },
        y: { beginAtZero: true, ticks: { stepSize: 1 } }
    };

    const labels = dateRange(stats.start_date, stats.end_date);
    const m = stats.metrics;

    charts.created = new Chart(document.getElementById('secretsCreatedChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: translations.stat_text, data: metricData(m, 'secrets_created_text', labels), borderColor: '#8b5cf6', backgroundColor: 'rgba(139, 92, 246, 0.1)', fill: true, tension: 0.3 },
                { label: translations.stat_file, data: metricData(m, 'secrets_created_file', labels), borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)', fill: true, tension: 0.3 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales }
    });

    charts.read = new Chart(document.getElementById('secretsReadChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{ label: translations.stat_reads, data: metricData(m, 'secrets_read', labels), borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true, tension: 0.3 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales }
    });

    charts.types = new Chart(document.getElementById('secretTypesChart'), {
        type: 'doughnut',
        data: {
            labels: [translations.stat_text, translations.stat_file],
            datasets: [{ data: [t.secrets_created_text || 0, t.secrets_created_file || 0], backgroundColor: ['#8b5cf6', '#f59e0b'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    const optPct = toOptionPct(t);
    charts.options = new Chart(document.getElementById('secretOptionsChart'), {
        type: 'bar',
        data: {
            labels: [translations.stat_passphrase, translations.stat_max_views, translations.stat_split_mode],
            datasets: [{ data: optPct, backgroundColor: ['#ec4899', '#84cc16', '#f97316'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => fmtDec(ctx.parsed.y) + ' %' } } }, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (v) => fmtDec(v) + ' %' } } } }
    });

    charts.outcomes = new Chart(document.getElementById('secretOutcomesChart'), {
        type: 'doughnut',
        data: {
            labels: [translations.stat_read, translations.stat_expired_unread, translations.stat_revoked, translations.stat_max_reached],
            datasets: [{ data: [t.secrets_read || 0, t.secrets_expired_unread || 0, t.secrets_revoked || 0, t.secrets_max_views_reached || 0], backgroundColor: ['#10b981', '#6b7280', '#ef4444', '#f59e0b'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    charts.admin = new Chart(document.getElementById('adminActivityChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: translations.stat_magic_links_requested, data: metricData(m, 'magic_links_requested', labels), borderColor: '#8b5cf6', tension: 0.3 },
                { label: translations.stat_magic_links_used, data: metricData(m, 'magic_links_used', labels), borderColor: '#10b981', tension: 0.3 },
                { label: translations.stat_secrets_extended, data: metricData(m, 'secrets_extended', labels), borderColor: '#06b6d4', tension: 0.3 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales }
    });

    // Error trends chart
    const et = data.errorTranslations || {};
    if (document.getElementById('errorTrendsChart')) {
        charts.errors = new Chart(document.getElementById('errorTrendsChart'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: et.errors_4xx || '4xx', data: metricData(m, 'http_errors_4xx', labels), borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)', fill: true, tension: 0.3 },
                    { label: et.errors_5xx || '5xx', data: metricData(m, 'http_errors_5xx', labels), borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', fill: true, tension: 0.3 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales }
        });
    }

    if (heatmapCreated) {
        renderHeatmap('heatmapCreated', heatmapCreated, 'violet', translations);
    }
    if (heatmapRead) {
        renderHeatmap('heatmapRead', heatmapRead, 'green', translations);
    }

    // Pageviews daily
    const daily = data.pageviewsDaily;
    if (daily && document.getElementById('pageviewsChart')) {
        const pvDates = Object.keys(daily).sort();
        charts.pageviews = new Chart(document.getElementById('pageviewsChart'), {
            type: 'line',
            data: {
                labels: pvDates,
                datasets: [
                    { label: translations.stat_visitors || 'Visitors', data: pvDates.map(d => daily[d]?.human || 0), borderColor: '#8b5cf6', backgroundColor: 'rgba(139, 92, 246, 0.1)', fill: true, tension: 0.3 },
                    { label: translations.stat_bots || 'Bots', data: pvDates.map(d => daily[d]?.bot || 0), borderColor: '#94a3b8', backgroundColor: 'rgba(148, 163, 184, 0.1)', fill: true, tension: 0.3 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales }
        });
    }

    initPeriodSelector();
    initSectionNav();
    startPolling();
}

// ── Section nav (sticky anchor bar with scrollspy) ─────────────────────

function initSectionNav() {
    const links = document.querySelectorAll('.dashboard-nav-link');
    if (links.length === 0) {
        return;
    }

    const sections = [];
    const linkByTarget = new Map();
    links.forEach((a) => {
        const id = a.dataset.navTarget;
        const el = document.getElementById(id);
        if (el) {
            linkByTarget.set(id, a);
            sections.push(el);
        }
    });

    if (sections.length === 0) {
        return;
    }

    function setActive(id) {
        linkByTarget.forEach((a, target) => {
            const isActive = target === id;
            a.classList.toggle('border-amber-500', isActive);
            a.classList.toggle('text-gray-900', isActive);
            a.classList.toggle('dark:text-white', isActive);
            a.classList.toggle('border-transparent', !isActive);
            a.classList.toggle('text-gray-600', !isActive);
            a.classList.toggle('dark:text-slate-400', !isActive);
            if (isActive) {
                a.setAttribute('aria-current', 'location');
            } else {
                a.removeAttribute('aria-current');
            }
        });
    }

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            const visible = entries
                .filter((e) => e.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
            if (visible.length > 0) {
                setActive(visible[0].target.id);
            }
        }, {
            rootMargin: '-160px 0px -55% 0px',
            threshold: 0,
        });
        sections.forEach((s) => observer.observe(s));
    }

    links.forEach((a) => {
        a.addEventListener('click', () => setActive(a.dataset.navTarget));
    });

    const initialId = (window.location.hash || '').slice(1);
    setActive(linkByTarget.has(initialId) ? initialId : sections[0].id);
}

// ── Period selector (no page reload) ────────────────────────────────────

function initPeriodSelector() {
    const select = document.getElementById('periodSelector');
    if (!select) {
        return;
    }

    select.addEventListener('change', function () {
        const data = window.superAdminData;
        data.period = this.value;

        const url = new URL(window.location);
        url.searchParams.set('period', this.value);
        history.replaceState(null, '', url);

        poll();
        startPolling();
    });
}

// ── Polling ──────────────────────────────────────────────────────────────

function startPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
    }

    const data = window.superAdminData;
    if (!data || !data.pollUrl) {
        return;
    }

    const interval = data.pollInterval || 30000;
    startRing(interval);
    pollTimer = setInterval(() => poll(), interval);
}

async function poll() {
    const data = window.superAdminData;
    if (!data || !data.pollUrl) {
        return;
    }

    const url = new URL(data.pollUrl, window.location.origin);
    url.searchParams.set('period', data.period);

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (response.status === 401) {
            clearInterval(pollTimer);
            resetRing();
            window.location.href = window.location.pathname.replace('/dashboard', '');
            return;
        }

        if (!response.ok) {
            return;
        }

        const newData = await response.json();

        // Update shared state
        data.stats = newData.stats;
        data.heatmapCreated = newData.heatmapCreated;
        data.heatmapRead = newData.heatmapRead;
        data.pageviewsDaily = newData.pageviews.daily;
        data.avgFirstReadDelay = newData.avgFirstReadDelay;
        data.medianFirstReadDelay = newData.medianFirstReadDelay;
        data.currentDiskUsage = newData.currentDiskUsage;
        data.systemHealth = newData.systemHealth;
        data.readRate = newData.readRate;
        data.creatorConcentration = newData.creatorConcentration;
        data.referrers = newData.referrers;
        data.pageviews = newData.pageviews;
        data.botStats = newData.botStats;
        data.deviceStats = newData.deviceStats;
        data.errorStats = newData.errorStats;
        data.responseTime = newData.responseTime;
        data.avgSecretSize = newData.avgSecretSize;

        // In-place updates (no scroll jump)
        updateKpis(newData);
        updateCharts(newData);

        // Restart ring
        const interval = data.pollInterval || 30000;
        startRing(interval);
    } catch {
        // Network error — skip this cycle, ring will restart on next poll
    }
}

// ── Boot ─────────────────────────────────────────────────────────────────

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}
