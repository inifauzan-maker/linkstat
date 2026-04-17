@extends('layouts.app')

@section('title', 'Master Admin - Analytics')
@section('body_class', 'app-shell text-slate-100')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-6 py-6 lg:px-10 lg:py-8">
        <header class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Master Admin</div>
                <h1 class="mt-3 font-display text-4xl font-bold text-white lg:text-5xl">Perbandingan beberapa landing page</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                    Bandingkan performa landing page dari banyak user dalam satu dashboard, termasuk view, visitor unik, CTA click, link click, dan source utama.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.users.index') }}" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/8">
                    Kelola Pengguna
                </a>
                <a href="{{ route('dashboard') }}" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/8">
                    Dashboard Saya
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
            <form method="GET" action="{{ route('admin.analytics.index') }}" class="grid gap-4 xl:grid-cols-[1.2fr_0.9fr_0.8fr_0.8fr_auto_auto]">
                @if (! $isCustomRange && $selectedRange)
                    <input type="hidden" name="range" value="{{ $selectedRange }}">
                @endif
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul, slug, user, atau email..." class="form-input">

                <select name="source" class="form-input">
                    <option value="">Semua Source</option>
                    @foreach ($availableSources as $source)
                        <option value="{{ $source['label'] }}" @selected($selectedSource === $source['label'])>
                            {{ $source['label'] }} ({{ $source['count'] }})
                        </option>
                    @endforeach
                </select>

                <input type="date" name="start_date" value="{{ old('start_date', $customStartDate) }}" class="form-input">
                <input type="date" name="end_date" value="{{ old('end_date', $customEndDate) }}" class="form-input">

                <button type="submit" class="rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-400/20">
                    Terapkan
                </button>
                <a href="{{ route('admin.analytics.index', ['range' => $selectedRange ?? 7]) }}" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-center text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/8">
                    Reset
                </a>
            </form>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <div class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                    Periode {{ $periodLabel }}
                </div>

                @foreach ($rangeOptions as $value => $label)
                    <a href="{{ route('admin.analytics.index', ['range' => $value, 'source' => $selectedSource, 'q' => $filters['q'] ?? null]) }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $selectedRange === $value ? 'bg-white text-slate-950 shadow-[0_18px_40px_rgba(255,255,255,0.12)]' : 'border border-white/10 bg-white/5 text-slate-200 hover:border-white/20 hover:bg-white/8' }}">
                        {{ $label }}
                    </a>
                @endforeach

                @if ($selectedSource)
                    <div class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100">
                        Source: {{ $selectedSource }}
                    </div>
                @endif

                @if ($isCustomRange)
                    <div class="rounded-full border border-amber-300/20 bg-amber-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-amber-100">
                        Custom Range
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="panel p-5">
                <div class="text-sm text-slate-400">Landing Page Dibandingkan</div>
                <div class="mt-2 font-display text-4xl font-bold text-white">{{ number_format($aggregate['total_pages']) }}</div>
            </div>
            <div class="panel p-5">
                <div class="text-sm text-slate-400">Total Page View</div>
                <div class="mt-2 font-display text-4xl font-bold text-white">{{ number_format($aggregate['total_views']) }}</div>
            </div>
            <div class="panel p-5">
                <div class="text-sm text-slate-400">Total CTA Click</div>
                <div class="mt-2 font-display text-4xl font-bold text-white">{{ number_format($aggregate['total_cta_clicks']) }}</div>
            </div>
            <div class="panel p-5">
                <div class="text-sm text-slate-400">Rata-rata CTA Rate</div>
                <div class="mt-2 font-display text-4xl font-bold text-white">{{ number_format($aggregate['average_cta_rate'], 1) }}%</div>
            </div>
        </div>

        <div class="mb-8 grid gap-4 lg:grid-cols-2">
            <div class="panel p-5">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Top Performer</div>
                @if ($aggregate['top_page'])
                    <div class="mt-3 text-2xl font-bold text-white">{{ $aggregate['top_page']['landing_page']->title }}</div>
                    <div class="mt-2 text-sm text-slate-300">{{ $aggregate['top_page']['landing_page']->user->name }} · {{ number_format($aggregate['top_page']['summary']['views']) }} view</div>
                @else
                    <div class="mt-3 text-sm text-slate-400">Belum ada data untuk periode ini.</div>
                @endif
            </div>

            <div class="panel p-5">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Best Conversion</div>
                @if ($aggregate['best_conversion_page'])
                    <div class="mt-3 text-2xl font-bold text-white">{{ $aggregate['best_conversion_page']['landing_page']->title }}</div>
                    <div class="mt-2 text-sm text-slate-300">{{ $aggregate['best_conversion_page']['landing_page']->user->name }} · {{ number_format($aggregate['best_conversion_page']['summary']['conversion_rate'], 1) }}% CTA rate</div>
                @else
                    <div class="mt-3 text-sm text-slate-400">Belum ada konversi untuk periode ini.</div>
                @endif
            </div>
        </div>

        <div class="panel overflow-hidden p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-white/10 text-left text-xs uppercase tracking-[0.22em] text-slate-500">
                        <tr>
                            <th class="px-4 py-4 font-semibold">Landing Page</th>
                            <th class="px-4 py-4 font-semibold text-right">View</th>
                            <th class="px-4 py-4 font-semibold text-right">Visitor</th>
                            <th class="px-4 py-4 font-semibold text-right">CTA</th>
                            <th class="px-4 py-4 font-semibold text-right">Link</th>
                            <th class="px-4 py-4 font-semibold text-right">CTA Rate</th>
                            <th class="px-4 py-4 font-semibold">Source Utama</th>
                            <th class="px-4 py-4 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/6 text-slate-200">
                        @forelse ($comparisons as $comparison)
                            @php
                                $landingPage = $comparison['landing_page'];
                                $summary = $comparison['summary'];
                            @endphp
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-white">{{ $landingPage->title }}</div>
                                    <div class="mt-1 text-sm text-slate-400">{{ $landingPage->user->name }} · {{ $landingPage->slug }}</div>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-white">{{ number_format($summary['views']) }}</td>
                                <td class="px-4 py-4 text-right">{{ number_format($summary['unique_visitors']) }}</td>
                                <td class="px-4 py-4 text-right">{{ number_format($summary['cta_clicks']) }}</td>
                                <td class="px-4 py-4 text-right">{{ number_format($summary['link_clicks']) }}</td>
                                <td class="px-4 py-4 text-right">
                                    <span class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                        {{ number_format($summary['conversion_rate'], 1) }}%
                                    </span>
                                </td>
                                <td class="px-4 py-4">{{ $comparison['top_source'] }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.users.edit', $landingPage->user) }}" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:border-white/20 hover:bg-white/8">
                                            Kelola User
                                        </a>
                                        <a href="{{ $comparison['public_url'] }}" target="_blank" rel="noopener noreferrer" class="rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-400/20">
                                            Lihat Halaman
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-400">
                                    Tidak ada landing page yang cocok dengan filter saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
