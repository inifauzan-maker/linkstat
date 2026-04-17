@extends('layouts.app')

@php
    $themePreview = $landingPage->themeConfig();
    $timeline = $analytics['timeline']->values();
    $timelineCount = $timeline->count();
    $chartWidth = 760;
    $chartHeight = 280;
    $chartPaddingX = 28;
    $chartPaddingY = 22;
    $plotWidth = $chartWidth - ($chartPaddingX * 2);
    $plotHeight = $chartHeight - ($chartPaddingY * 2);
    $timelineSteps = max($timeline->count() - 1, 1);
    $chartMax = max(
        1,
        $timeline->max(fn ($day) => max($day['views'], $day['unique_visitors'], $day['cta_clicks'], $day['link_clicks']))
    );

    $timelineGeometry = $timeline->map(function (array $day, int $index) use (
        $timelineCount,
        $chartPaddingX,
        $chartPaddingY,
        $chartWidth,
        $plotWidth,
        $plotHeight,
        $timelineSteps,
        $chartMax
    ) {
        if ($timelineCount <= 1) {
            $x = $chartPaddingX + ($plotWidth / 2);
            $slotStartX = $chartPaddingX;
            $slotWidth = $plotWidth;
        } else {
            $distance = $plotWidth / $timelineSteps;
            $x = $chartPaddingX + ($distance * $index);
            $slotStartX = $index === 0 ? $chartPaddingX : $chartPaddingX + ($distance * ($index - 0.5));
            $slotEndX = $index === $timelineCount - 1 ? $chartWidth - $chartPaddingX : $chartPaddingX + ($distance * ($index + 0.5));
            $slotWidth = $slotEndX - $slotStartX;
        }

        $resolveY = fn (int|float $value) => $chartPaddingY + $plotHeight - (($value / $chartMax) * $plotHeight);

        return [
            ...$day,
            'index' => $index,
            'x' => round($x, 2),
            'slot_x' => round($slotStartX, 2),
            'slot_width' => round($slotWidth, 2),
            'y_views' => round($resolveY($day['views']), 2),
            'y_unique_visitors' => round($resolveY($day['unique_visitors']), 2),
            'y_cta_clicks' => round($resolveY($day['cta_clicks']), 2),
            'y_link_clicks' => round($resolveY($day['link_clicks']), 2),
        ];
    });

    $linePoints = function (string $key) use ($timelineGeometry) {
        return $timelineGeometry->map(function (array $day) use ($key) {
            return number_format($day['x'], 2, '.', '').','.number_format($day['y_'.$key], 2, '.', '');
        })->implode(' ');
    };

    $formatDelta = function (array $metric) {
        return match ($metric['direction']) {
            'new' => 'Baru',
            'flat' => 'Stabil',
            default => ($metric['delta'] > 0 ? '+' : '').number_format($metric['delta'], 1).'%',
        };
    };

    $deltaTone = function (array $metric) {
        return match ($metric['direction']) {
            'up' => 'border border-emerald-300/20 bg-emerald-400/10 text-emerald-100',
            'down' => 'border border-rose-300/20 bg-rose-400/10 text-rose-100',
            'new' => 'border border-cyan-300/20 bg-cyan-400/10 text-cyan-100',
            default => 'border border-white/10 bg-white/5 text-slate-200',
        };
    };

    $metricCards = [
        ['key' => 'views', 'label' => 'Page View', 'description' => $analytics['period_label'], 'decimals' => 0, 'suffix' => ''],
        ['key' => 'unique_visitors', 'label' => 'Visitor Unik', 'description' => 'Sesi unik dari page view', 'decimals' => 0, 'suffix' => ''],
        ['key' => 'cta_clicks', 'label' => 'CTA Click', 'description' => 'Klik tombol WhatsApp', 'decimals' => 0, 'suffix' => ''],
        ['key' => 'link_clicks', 'label' => 'Link Click', 'description' => 'Klik link tambahan', 'decimals' => 0, 'suffix' => ''],
        ['key' => 'conversion_rate', 'label' => 'CTA Rate', 'description' => 'CTA dibanding page view', 'decimals' => 1, 'suffix' => '%'],
    ];

    $chartSeries = [
        ['key' => 'views', 'label' => 'Page View', 'color' => '#fb923c'],
        ['key' => 'unique_visitors', 'label' => 'Visitor Unik', 'color' => '#a78bfa'],
        ['key' => 'cta_clicks', 'label' => 'CTA Click', 'color' => '#22d3ee'],
        ['key' => 'link_clicks', 'label' => 'Link Click', 'color' => '#fb7185'],
    ];

    $domainStatusTone = function (?string $status) {
        return match ($status) {
            'verified', 'valid' => 'border border-emerald-300/20 bg-emerald-400/10 text-emerald-100',
            'mismatch', 'expired', 'unreachable' => 'border border-rose-300/20 bg-rose-400/10 text-rose-100',
            'missing', 'pending', 'unavailable' => 'border border-amber-300/20 bg-amber-400/10 text-amber-100',
            default => 'border border-white/10 bg-white/5 text-slate-200',
        };
    };

    $domainStatusLabel = function (?string $status, string $type) {
        if ($type === 'dns') {
            return match ($status) {
                'verified' => 'Verified',
                'mismatch' => 'Mismatch',
                'missing' => 'Missing',
                'unavailable' => 'Unavailable',
                default => 'Belum dicek',
            };
        }

        return match ($status) {
            'valid' => 'Valid',
            'expired' => 'Expired',
            'unreachable' => 'Unreachable',
            'unavailable' => 'Unavailable',
            default => 'Belum dicek',
        };
    };
@endphp

@section('title', 'Dashboard Landing Page')
@section('body_class', 'app-shell text-slate-100')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-6 py-6 lg:px-10 lg:py-8">
        <header class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Dashboard</div>
                <h1 class="mt-3 font-display text-4xl font-bold text-white lg:text-5xl">Analytics landing page yang lebih detail</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                    Pantau tren harian, visitor unik, CTA WhatsApp, klik link tambahan, sumber traffic, dan conversion funnel dari satu dashboard.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" data-copy-text="{{ $publicUrl }}" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/8">
                    Copy Link Publik
                </button>
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer" class="btn-primary rounded-full px-5 py-3 text-sm font-semibold text-slate-950">
                    Lihat Halaman
                </a>
                <a href="{{ $exportCsvUrl }}" class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-5 py-3 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-400/20">
                    Export CSV
                </a>
                <a href="{{ $exportExcelUrl }}" class="rounded-full border border-emerald-300/20 bg-emerald-400/10 px-5 py-3 text-sm font-semibold text-emerald-100 transition hover:bg-emerald-400/20">
                    Export Excel
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/30 hover:bg-white/8">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <div class="panel-soft mb-8 p-5">
            <div class="grid gap-5 xl:grid-cols-[1fr_auto] xl:items-center">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                        Range Analytics
                    </div>
                    @foreach ($rangeOptions as $value => $label)
                        <a href="{{ route('dashboard', ['range' => $value, 'source' => $selectedSource]) }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $selectedRange === $value ? 'bg-white text-slate-950 shadow-[0_18px_40px_rgba(255,255,255,0.12)]' : 'border border-white/10 bg-white/5 text-slate-200 hover:border-white/20 hover:bg-white/8' }}">
                            {{ $label }}
                        </a>
                    @endforeach

                    @if ($isCustomRange)
                        <div class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100">
                            Custom Date Active
                        </div>
                    @endif
                </div>

                <form method="GET" action="{{ route('dashboard') }}" class="grid gap-3 lg:grid-cols-[0.95fr_0.95fr_1.1fr_auto_auto]">
                    @if (! $isCustomRange && $selectedRange)
                        <input type="hidden" name="range" value="{{ $selectedRange }}">
                    @endif
                    <input type="date" name="start_date" value="{{ old('start_date', $customStartDate) }}" class="form-input min-w-[10rem]">
                    <input type="date" name="end_date" value="{{ old('end_date', $customEndDate) }}" class="form-input min-w-[10rem]">
                    <select name="source" class="form-input min-w-[12rem]">
                        <option value="">Semua Source</option>
                        @foreach ($availableSources as $source)
                            <option value="{{ $source['label'] }}" @selected($selectedSource === $source['label'])>
                                {{ $source['label'] }} ({{ $source['count'] }})
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-400/20">
                        Terapkan
                    </button>
                    <a href="{{ route('dashboard', ['range' => $selectedRange ?? 7]) }}" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-center text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/8">
                        Reset
                    </a>
                </form>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-300">
                <div>
                    Periode aktif:
                    <span class="font-semibold text-white">{{ $analytics['period_label'] }}</span>
                </div>
                <div class="rounded-full border border-white/10 bg-black/20 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                    {{ $analytics['period_caption'] }}
                </div>
                <div class="text-xs uppercase tracking-[0.22em] text-slate-500">
                    Komparasi: {{ $analytics['previous_period_label'] }}
                </div>
                @if ($selectedSource)
                    <div class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100">
                        Source: {{ $selectedSource }}
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-8 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach ($metricCards as $card)
                @php
                    $metric = $analytics['metrics'][$card['key']];
                @endphp
                <div class="panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-slate-400">{{ $card['label'] }}</div>
                            <div class="mt-2 font-display text-4xl font-bold text-white">
                                {{ number_format($metric['value'], $card['decimals']) }}{{ $card['suffix'] }}
                            </div>
                        </div>
                        <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $deltaTone($metric) }}">
                            {{ $formatDelta($metric) }}
                        </div>
                    </div>
                    <div class="mt-3 text-xs uppercase tracking-[0.22em] text-slate-500">{{ $card['description'] }}</div>
                    <div class="mt-2 text-xs text-slate-400">
                        vs {{ $analytics['previous_period_label'] }}: {{ number_format($metric['previous'], $card['decimals']) }}{{ $card['suffix'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.08fr_0.92fr]">
            <section class="space-y-6">
                <div class="panel p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Trend Performance</div>
                            <h2 class="mt-2 font-display text-2xl font-bold text-white">Performa harian view, visitor, CTA, dan link click</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-400">
                                Grafik ini memperlihatkan perubahan performa per hari untuk {{ strtolower($analytics['period_label']) }}.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-[22px] border border-white/10 bg-black/20 px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.22em] text-slate-500">Avg View/Hari</div>
                                <div class="mt-2 text-2xl font-bold text-white">{{ number_format($analytics['averages']['views_per_day'], 1) }}</div>
                            </div>
                            <div class="rounded-[22px] border border-white/10 bg-black/20 px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.22em] text-slate-500">Avg CTA/Hari</div>
                                <div class="mt-2 text-2xl font-bold text-white">{{ number_format($analytics['averages']['cta_clicks_per_day'], 1) }}</div>
                            </div>
                            <div class="rounded-[22px] border border-white/10 bg-black/20 px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.22em] text-slate-500">Hari Terbaik</div>
                                <div class="mt-2 text-2xl font-bold text-white">{{ $analytics['best_day']['label'] ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 rounded-[28px] border border-white/10 bg-black/20 p-4">
                        <div class="relative" data-analytics-chart>
                            <div data-chart-tooltip class="pointer-events-none absolute hidden min-w-[15rem] rounded-[22px] border border-white/10 bg-slate-950/96 p-4 text-sm text-slate-100 shadow-[0_22px_60px_rgba(0,0,0,0.35)] backdrop-blur-xl"></div>
                            <div class="overflow-x-auto">
                                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="min-w-[720px]">
                            @foreach (range(0, 4) as $step)
                                @php
                                    $y = $chartPaddingY + (($plotHeight / 4) * $step);
                                    $labelValue = (int) round($chartMax - (($chartMax / 4) * $step));
                                @endphp
                                <line x1="{{ $chartPaddingX }}" y1="{{ $y }}" x2="{{ $chartWidth - $chartPaddingX }}" y2="{{ $y }}" stroke="rgba(255,255,255,0.09)" stroke-dasharray="4 8" />
                                <text x="0" y="{{ $y + 4 }}" fill="rgba(148,163,184,0.75)" font-size="12">{{ $labelValue }}</text>
                            @endforeach

                            <line data-chart-crosshair x1="{{ $chartPaddingX }}" y1="{{ $chartPaddingY }}" x2="{{ $chartPaddingX }}" y2="{{ $chartHeight - $chartPaddingY }}" stroke="rgba(255,255,255,0.28)" stroke-dasharray="6 8" opacity="0"></line>

                            @foreach ($timelineGeometry as $day)
                                <line x1="{{ $day['x'] }}" y1="{{ $chartPaddingY }}" x2="{{ $day['x'] }}" y2="{{ $chartHeight - $chartPaddingY }}" stroke="rgba(255,255,255,0.04)" />
                                <text x="{{ $day['x'] }}" y="{{ $chartHeight - 2 }}" text-anchor="middle" fill="rgba(148,163,184,0.75)" font-size="11">{{ $day['short_label'] }}</text>
                            @endforeach

                            <polyline fill="none" stroke="#fb923c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="{{ $linePoints('views') }}" />
                            <polyline fill="none" stroke="#a78bfa" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="{{ $linePoints('unique_visitors') }}" />
                            <polyline fill="none" stroke="#22d3ee" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="{{ $linePoints('cta_clicks') }}" />
                            <polyline fill="none" stroke="#fb7185" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="{{ $linePoints('link_clicks') }}" />

                            @foreach ($timelineGeometry as $day)
                                @foreach ($chartSeries as $series)
                                    <circle
                                        data-chart-marker
                                        data-day-index="{{ $day['index'] }}"
                                        data-base-radius="4.5"
                                        cx="{{ $day['x'] }}"
                                        cy="{{ $day['y_'.$series['key']] }}"
                                        r="4.5"
                                        fill="{{ $series['color'] }}"
                                        stroke="rgba(7, 17, 29, 0.9)"
                                        stroke-width="2"
                                    ></circle>
                                @endforeach

                                <rect
                                    data-chart-slot
                                    data-x="{{ $day['x'] }}"
                                    data-day-index="{{ $day['index'] }}"
                                    data-date="{{ $day['date'] }}"
                                    data-label="{{ $day['label'] }}"
                                    data-views="{{ $day['views'] }}"
                                    data-unique-visitors="{{ $day['unique_visitors'] }}"
                                    data-cta-clicks="{{ $day['cta_clicks'] }}"
                                    data-link-clicks="{{ $day['link_clicks'] }}"
                                    data-cta-rate="{{ number_format($day['cta_rate'], 1, '.', '') }}"
                                    tabindex="0"
                                    x="{{ $day['slot_x'] }}"
                                    y="{{ $chartPaddingY }}"
                                    width="{{ $day['slot_width'] }}"
                                    height="{{ $plotHeight }}"
                                    fill="transparent"
                                    class="cursor-crosshair focus:outline-none"
                                ></rect>
                            @endforeach
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                        <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2"><span class="h-3 w-3 rounded-full bg-orange-400"></span>Page View</div>
                        <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2"><span class="h-3 w-3 rounded-full bg-violet-400"></span>Visitor Unik</div>
                        <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2"><span class="h-3 w-3 rounded-full bg-cyan-400"></span>CTA Click</div>
                        <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2"><span class="h-3 w-3 rounded-full bg-rose-400"></span>Link Click</div>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-[24px] border border-white/10 bg-black/20">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="border-b border-white/10 text-left text-xs uppercase tracking-[0.22em] text-slate-500">
                                    <tr>
                                        <th class="px-4 py-4 font-semibold">Tanggal</th>
                                        <th class="px-4 py-4 font-semibold text-right">View</th>
                                        <th class="px-4 py-4 font-semibold text-right">Visitor</th>
                                        <th class="px-4 py-4 font-semibold text-right">CTA</th>
                                        <th class="px-4 py-4 font-semibold text-right">Link</th>
                                        <th class="px-4 py-4 font-semibold text-right">CTA Rate</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/6 text-slate-200">
                                    @foreach ($timeline->reverse()->values() as $day)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-white">{{ $day['label'] }}</td>
                                            <td class="px-4 py-3 text-right">{{ number_format($day['views']) }}</td>
                                            <td class="px-4 py-3 text-right">{{ number_format($day['unique_visitors']) }}</td>
                                            <td class="px-4 py-3 text-right">{{ number_format($day['cta_clicks']) }}</td>
                                            <td class="px-4 py-3 text-right">{{ number_format($day['link_clicks']) }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-cyan-200">{{ number_format($day['cta_rate'], 1) }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="panel p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Conversion Funnel</div>
                        <h2 class="mt-2 font-display text-2xl font-bold text-white">Perjalanan audience dari view ke klik</h2>
                        <div class="mt-6 space-y-4">
                            @foreach ($analytics['funnel'] as $step)
                                <div class="rounded-[22px] border border-white/10 bg-black/20 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <div class="font-semibold text-white">{{ $step['label'] }}</div>
                                            <div class="mt-1 text-sm text-slate-400">{{ number_format($step['conversion_from_views'], 1) }}% dari page view</div>
                                        </div>
                                        <div class="text-2xl font-bold text-white">{{ number_format($step['total']) }}</div>
                                    </div>
                                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/8">
                                        <div class="h-full rounded-full bg-[linear-gradient(90deg,#ffb84d_0%,#ff8a3d_45%,#22d3ee_100%)]" style="width: {{ max(6, $step['conversion_from_views']) }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="panel p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Traffic Source</div>
                        <h2 class="mt-2 font-display text-2xl font-bold text-white">Sumber traffic yang membawa audience</h2>
                        <div class="mt-6 space-y-4">
                            @forelse ($analytics['top_referrers'] as $source)
                                <div class="rounded-[22px] border border-white/10 bg-black/20 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="truncate font-semibold text-white">{{ $source['label'] }}</div>
                                        <div class="text-sm font-semibold text-slate-300">{{ number_format($source['count']) }} view</div>
                                    </div>
                                    <div class="mt-3 h-3 overflow-hidden rounded-full bg-white/8">
                                        <div class="h-full rounded-full bg-[linear-gradient(90deg,#22d3ee_0%,#a78bfa_100%)]" style="width: {{ max(8, $source['share']) }}%;"></div>
                                    </div>
                                    <div class="mt-2 text-xs uppercase tracking-[0.22em] text-slate-500">{{ number_format($source['share'], 1) }}% dari total page view</div>
                                </div>
                            @empty
                                <div class="rounded-[22px] border border-dashed border-white/15 bg-white/4 px-5 py-8 text-sm leading-7 text-slate-400">
                                    Belum ada sumber traffic yang tercatat. Saat halaman mulai dibuka dari Instagram, TikTok, atau platform lain, daftar ini akan terisi.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="panel p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Top Links</div>
                            <h2 class="mt-2 font-display text-2xl font-bold text-white">Link yang paling sering diklik audience</h2>
                        </div>

                        @if ($analytics['best_conversion_day'])
                            <div class="rounded-[20px] border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
                                <div class="font-semibold">CTA rate terbaik: {{ $analytics['best_conversion_day']['label'] }}</div>
                                <div class="mt-1 text-cyan-50/80">{{ number_format($analytics['best_conversion_day']['cta_rate'], 1) }}%</div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 space-y-3">
                        @forelse ($analytics['top_links'] as $link)
                            <div class="rounded-[22px] border border-white/10 bg-white/5 px-4 py-4">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0 pr-4">
                                        <div class="truncate font-semibold text-white">{{ $link->label }}</div>
                                        <div class="mt-1 truncate text-sm text-slate-400">{{ $link->url }}</div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="rounded-full border border-white/10 bg-black/20 px-4 py-2 text-sm font-semibold text-white">
                                            {{ number_format($link->clicks_count) }} klik
                                        </div>
                                        <div class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-100">
                                            {{ number_format($link->clicks_share, 1) }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-[22px] border border-dashed border-white/15 bg-white/4 px-5 py-6 text-sm leading-7 text-slate-400">
                                Belum ada link tambahan. Tambahkan link edukasi, promo, katalog, atau marketplace agar analytics link ikut terbaca.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <div class="panel overflow-hidden p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Link Publik</div>
                            <h2 class="mt-2 font-display text-2xl font-bold text-white">{{ $landingPage->slug }}</h2>
                        </div>
                        <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $landingPage->is_active ? 'border border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border border-amber-300/20 bg-amber-400/10 text-amber-100' }}">
                            {{ $landingPage->is_active ? 'Aktif' : 'Draft' }}
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-2 rounded-2xl border border-white/10 bg-black/20 p-3 text-sm text-slate-300">
                        <span class="truncate">{{ $publicUrl }}</span>
                        <button type="button" data-copy-text="{{ $publicUrl }}" class="rounded-full border border-white/10 px-3 py-1 text-xs font-semibold text-white hover:bg-white/8">
                            Copy
                        </button>
                    </div>

                    @if ($customDomainUrl)
                        <div class="mt-4 rounded-[22px] border border-cyan-300/20 bg-cyan-400/10 p-4 text-sm text-cyan-100">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold">Custom Domain</div>
                                <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $landingPage->custom_domain_connected_at ? 'border border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border border-amber-300/20 bg-amber-400/10 text-amber-100' }}">
                                    {{ $landingPage->custom_domain_connected_at ? 'Connected' : 'Pending' }}
                                </div>
                            </div>
                            <div class="mt-2 break-all text-cyan-50">{{ $customDomainUrl }}</div>
                            <div class="mt-3 text-xs uppercase tracking-[0.22em] text-cyan-50/70">Fallback slug: {{ $defaultPublicUrl }}</div>
                            <form method="POST" action="{{ $domainVerifyUrl }}" class="mt-4">
                                @csrf
                                <button type="submit" class="rounded-full border border-cyan-50/20 bg-cyan-50/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-50 transition hover:bg-cyan-50/20">
                                    Cek Ulang DNS & SSL
                                </button>
                            </form>
                        </div>
                    @endif

                    <div class="mt-6 rounded-[32px] border p-5 text-white shadow-[0_25px_80px_rgba(0,0,0,0.35)]" style="background: {{ $themePreview['background'] }}; border-color: {{ $themePreview['border'] }};">
                        <div class="mx-auto max-w-[19rem] space-y-4">
                            <div class="mx-auto flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border text-2xl font-bold" style="border-color: {{ $themePreview['border'] }}; background: {{ $themePreview['surface_soft'] }};">
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $landingPage->title }}" class="h-full w-full object-cover">
                                @else
                                    {{ strtoupper(substr($landingPage->title, 0, 1)) }}
                                @endif
                            </div>
                            <div class="text-center">
                                <div class="font-display text-2xl font-bold">{{ $landingPage->title }}</div>
                                @if ($landingPage->headline)
                                    <div class="mt-1 text-sm font-semibold" style="color: {{ $themePreview['accent_soft'] }};">{{ $landingPage->headline }}</div>
                                @endif
                                @if ($landingPage->bio)
                                    <p class="mt-3 text-sm leading-6" style="color: {{ $themePreview['muted'] }};">{{ $landingPage->bio }}</p>
                                @endif
                            </div>
                            <div class="rounded-[22px] px-5 py-4 text-center text-sm font-extrabold text-slate-950" style="background: {{ $themePreview['button'] }};">
                                {{ $landingPage->cta_label }}
                            </div>
                            <div class="space-y-3">
                                @forelse ($landingPage->links->where('is_active', true)->take(4) as $link)
                                    <div class="rounded-[20px] border px-4 py-4" style="border-color: {{ $themePreview['border'] }}; background: {{ $themePreview['surface_soft'] }};">
                                        <div class="font-semibold">{{ $link->label }}</div>
                                        @if ($link->description)
                                            <div class="mt-1 text-sm" style="color: {{ $themePreview['muted'] }};">{{ $link->description }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="rounded-[20px] border px-4 py-5 text-sm leading-6" style="border-color: {{ $themePreview['border'] }}; background: {{ $themePreview['surface_soft'] }}; color: {{ $themePreview['muted'] }};">
                                        Tambahkan link edukasi atau promo di form bawah agar tampil di halaman publik.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel p-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Pengaturan Halaman</div>
                    <h2 class="mt-2 font-display text-2xl font-bold text-white">Profil, CTA, dan tema</h2>

                    <form method="POST" action="{{ route('dashboard.page.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="slug" class="mb-2 block text-sm font-semibold text-slate-200">Slug unik</label>
                                <input id="slug" type="text" name="slug" value="{{ old('slug', $landingPage->slug) }}" class="form-input">
                                @error('slug')
                                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="title" class="mb-2 block text-sm font-semibold text-slate-200">Judul halaman</label>
                                <input id="title" type="text" name="title" value="{{ old('title', $landingPage->title) }}" class="form-input">
                                @error('title')
                                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="headline" class="mb-2 block text-sm font-semibold text-slate-200">Headline singkat</label>
                            <input id="headline" type="text" name="headline" value="{{ old('headline', $landingPage->headline) }}" class="form-input">
                            @error('headline')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="bio" class="mb-2 block text-sm font-semibold text-slate-200">Bio</label>
                            <textarea id="bio" name="bio" rows="4" class="form-input">{{ old('bio', $landingPage->bio) }}</textarea>
                            @error('bio')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="avatar_file" class="mb-2 block text-sm font-semibold text-slate-200">Upload avatar</label>
                                <input id="avatar_file" type="file" name="avatar_file" accept="image/png,image/jpeg,image/webp,image/gif" class="form-input file:mr-4 file:rounded-full file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-white/15">
                                <p class="mt-2 text-xs text-slate-500">Gunakan JPG, PNG, WEBP, atau GIF. Maksimal 2 MB.</p>
                                @error('avatar_file')
                                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="avatar_url" class="mb-2 block text-sm font-semibold text-slate-200">URL avatar eksternal</label>
                                <input id="avatar_url" type="url" name="avatar_url" value="{{ old('avatar_url', $manualAvatarUrl) }}" class="form-input" placeholder="https://example.com/avatar.jpg">
                                <p class="mt-2 text-xs text-slate-500">Opsional. Jika upload file baru, URL ini akan diabaikan.</p>
                                @error('avatar_url')
                                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if ($avatarUrl)
                            <div class="rounded-[24px] border border-white/10 bg-black/20 p-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-16 w-16 overflow-hidden rounded-full border border-white/10 bg-white/5">
                                        <img src="{{ $avatarUrl }}" alt="{{ $landingPage->title }}" class="h-full w-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-semibold text-white">Avatar aktif</div>
                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $landingPage->usesUploadedAvatar() ? 'Sumber: upload file' : 'Sumber: URL eksternal' }}
                                        </div>
                                    </div>
                                </div>

                                <label class="mt-4 flex items-center gap-3 rounded-2xl border border-white/8 bg-white/5 px-4 py-4 text-sm text-slate-300">
                                    <input type="checkbox" name="remove_avatar" value="1" class="h-4 w-4 rounded border-white/20 bg-transparent text-orange-400 focus:ring-orange-400/30">
                                    Hapus avatar saat ini
                                </label>
                            </div>
                        @endif

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="whatsapp_number" class="mb-2 block text-sm font-semibold text-slate-200">Nomor WhatsApp</label>
                                <input id="whatsapp_number" type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $landingPage->whatsapp_number) }}" class="form-input">
                                @error('whatsapp_number')
                                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="cta_label" class="mb-2 block text-sm font-semibold text-slate-200">Label tombol CTA</label>
                                <input id="cta_label" type="text" name="cta_label" value="{{ old('cta_label', $landingPage->cta_label) }}" class="form-input">
                                @error('cta_label')
                                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="whatsapp_message" class="mb-2 block text-sm font-semibold text-slate-200">Pesan WhatsApp default</label>
                            <input id="whatsapp_message" type="text" name="whatsapp_message" value="{{ old('whatsapp_message', $landingPage->whatsapp_message) }}" class="form-input">
                            @error('whatsapp_message')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="theme" class="mb-2 block text-sm font-semibold text-slate-200">Tema halaman</label>
                            <select id="theme" name="theme" class="form-input">
                                @foreach ($themes as $key => $theme)
                                    <option value="{{ $key }}" @selected(old('theme', $landingPage->theme) === $key)>{{ $theme['name'] }}</option>
                                @endforeach
                            </select>
                            @error('theme')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="custom_domain" class="mb-2 block text-sm font-semibold text-slate-200">Custom domain</label>
                            <input id="custom_domain" type="text" name="custom_domain" value="{{ old('custom_domain', $landingPage->custom_domain) }}" class="form-input" placeholder="promo.brandanda.com">
                            <p class="mt-2 text-xs leading-6 text-slate-500">
                                Masukkan domain tanpa `https://`. Arahkan `CNAME` atau proxy domain Anda ke `{{ $customDomainTarget }}`.
                            </p>
                            @error('custom_domain')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror

                            @if ($landingPage->hasCustomDomain())
                                <div class="mt-4 rounded-[22px] border {{ $landingPage->custom_domain_connected_at ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border-amber-300/20 bg-amber-400/10 text-amber-100' }} p-4 text-sm">
                                    <div class="font-semibold">
                                        {{ $landingPage->custom_domain_connected_at ? 'Custom domain sudah terhubung.' : 'Menunggu domain diarahkan ke aplikasi.' }}
                                    </div>
                                    <div class="mt-2 break-all">{{ $customDomainScheme }}://{{ $landingPage->custom_domain }}</div>
                                    @if ($landingPage->custom_domain_connected_at)
                                        <div class="mt-2 text-xs uppercase tracking-[0.22em] text-current/80">
                                            Terdeteksi: {{ $landingPage->custom_domain_connected_at->format('d M Y H:i') }}
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div class="rounded-[22px] border border-white/10 bg-black/20 p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">DNS Status</div>
                                                <div class="mt-2 text-lg font-bold text-white">{{ $domainStatusLabel($landingPage->custom_domain_dns_status, 'dns') }}</div>
                                            </div>
                                            <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $domainStatusTone($landingPage->custom_domain_dns_status) }}">
                                                {{ $domainStatusLabel($landingPage->custom_domain_dns_status, 'dns') }}
                                            </div>
                                        </div>

                                        <div class="mt-4 space-y-2 text-sm text-slate-300">
                                            <div>Target aplikasi: <span class="font-semibold text-white">{{ $customDomainTarget }}</span></div>
                                            <div>Target DNS terdeteksi: <span class="font-semibold text-white">{{ $landingPage->custom_domain_dns_target ?? '-' }}</span></div>
                                            <div class="text-slate-400">{{ $landingPage->custom_domain_dns_message ?? 'Belum ada hasil verifikasi DNS.' }}</div>
                                        </div>

                                        @if ($landingPage->custom_domain_dns_checked_at)
                                            <div class="mt-3 text-xs uppercase tracking-[0.22em] text-slate-500">
                                                Dicek: {{ $landingPage->custom_domain_dns_checked_at->format('d M Y H:i') }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="rounded-[22px] border border-white/10 bg-black/20 p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">SSL Status</div>
                                                <div class="mt-2 text-lg font-bold text-white">{{ $domainStatusLabel($landingPage->custom_domain_ssl_status, 'ssl') }}</div>
                                            </div>
                                            <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $domainStatusTone($landingPage->custom_domain_ssl_status) }}">
                                                {{ $domainStatusLabel($landingPage->custom_domain_ssl_status, 'ssl') }}
                                            </div>
                                        </div>

                                        <div class="mt-4 space-y-2 text-sm text-slate-300">
                                            <div>Issuer: <span class="font-semibold text-white">{{ $landingPage->custom_domain_ssl_issuer ?? '-' }}</span></div>
                                            <div>
                                                Berlaku sampai:
                                                <span class="font-semibold text-white">
                                                    {{ $landingPage->custom_domain_ssl_expires_at?->format('d M Y H:i') ?? '-' }}
                                                </span>
                                            </div>
                                            <div class="text-slate-400">{{ $landingPage->custom_domain_ssl_message ?? 'Belum ada hasil verifikasi SSL.' }}</div>
                                        </div>

                                        @if ($landingPage->custom_domain_ssl_checked_at)
                                            <div class="mt-3 text-xs uppercase tracking-[0.22em] text-slate-500">
                                                Dicek: {{ $landingPage->custom_domain_ssl_checked_at->format('d M Y H:i') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <label class="flex items-center gap-3 rounded-2xl border border-white/8 bg-white/5 px-4 py-4 text-sm text-slate-300">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $landingPage->is_active)) class="h-4 w-4 rounded border-white/20 bg-transparent text-orange-400 focus:ring-orange-400/30">
                            Aktifkan halaman publik
                        </label>

                        <button type="submit" class="btn-primary w-full rounded-2xl px-5 py-4 text-sm font-extrabold text-slate-950">
                            Simpan Perubahan
                        </button>
                    </form>
                </div>
            </aside>
        </div>

        <section class="panel mt-6 p-6">
            <div class="grid gap-6 lg:grid-cols-[0.88fr_1.12fr]">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Tambah Link</div>
                    <h2 class="mt-2 font-display text-2xl font-bold text-white">Link edukasi, promo, atau marketplace</h2>
                    <form method="POST" action="{{ route('dashboard.links.store') }}" class="mt-6 space-y-4">
                        @csrf

                        <div>
                            <label for="label" class="mb-2 block text-sm font-semibold text-slate-200">Judul link</label>
                            <input id="label" type="text" name="label" value="{{ old('label') }}" class="form-input">
                        </div>

                        <div>
                            <label for="description" class="mb-2 block text-sm font-semibold text-slate-200">Deskripsi singkat</label>
                            <input id="description" type="text" name="description" value="{{ old('description') }}" class="form-input">
                        </div>

                        <div>
                            <label for="url" class="mb-2 block text-sm font-semibold text-slate-200">URL tujuan</label>
                            <input id="url" type="url" name="url" value="{{ old('url') }}" class="form-input">
                        </div>

                        <div>
                            <label for="sort_order" class="mb-2 block text-sm font-semibold text-slate-200">Urutan</label>
                            <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-input">
                        </div>

                        <button type="submit" class="btn-primary w-full rounded-2xl px-5 py-4 text-sm font-extrabold text-slate-950">
                            Tambah Link Baru
                        </button>
                    </form>
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Daftar Link</div>
                    <h2 class="mt-2 font-display text-2xl font-bold text-white">Kelola link yang tampil di halaman publik</h2>

                    <div class="mt-6 space-y-4">
                        @forelse ($landingPage->links as $link)
                            <form method="POST" action="{{ route('dashboard.links.update', $link) }}" class="rounded-[24px] border border-white/10 bg-white/5 p-5">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Judul</label>
                                        <input type="text" name="label" value="{{ old('label', $link->label) }}" class="form-input">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Urutan</label>
                                        <input type="number" name="sort_order" value="{{ old('sort_order', $link->sort_order) }}" class="form-input">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Deskripsi</label>
                                    <input type="text" name="description" value="{{ old('description', $link->description) }}" class="form-input">
                                </div>

                                <div class="mt-4">
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">URL</label>
                                    <input type="url" name="url" value="{{ old('url', $link->url) }}" class="form-input">
                                </div>

                                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <label class="flex items-center gap-3 rounded-2xl border border-white/8 bg-black/20 px-4 py-3 text-sm text-slate-300">
                                        <input type="checkbox" name="is_active" value="1" @checked($link->is_active) class="h-4 w-4 rounded border-white/20 bg-transparent text-orange-400 focus:ring-orange-400/30">
                                        Tampilkan di landing page
                                    </label>

                                    <div class="flex gap-3">
                                        <button type="submit" class="rounded-2xl border border-white/15 px-4 py-3 text-sm font-semibold text-white hover:border-white/30 hover:bg-white/8">
                                            Simpan Link
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('dashboard.links.destroy', $link) }}" class="-mt-1 flex justify-end">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-full border border-rose-300/20 bg-rose-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-rose-100 hover:bg-rose-400/20">
                                    Hapus Link
                                </button>
                            </form>
                        @empty
                            <div class="rounded-[24px] border border-dashed border-white/15 bg-white/4 px-5 py-8 text-sm leading-7 text-slate-400">
                                Belum ada link tambahan. Gunakan form di kiri untuk menambahkan link edukasi, promo, marketplace, katalog, atau booking.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
