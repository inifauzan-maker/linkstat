@extends('layouts.app')

@section('title', $landingPage->title)
@section('body_class', 'theme-page text-white')

@section('content')
    <div class="min-h-screen px-4 py-8" style="--page-background: {{ $theme['background'] }}; --page-surface: {{ $theme['surface'] }}; --page-surface-soft: {{ $theme['surface_soft'] }}; --page-text: {{ $theme['text'] }}; --page-muted: {{ $theme['muted'] }}; --page-accent: {{ $theme['accent'] }}; --page-accent-soft: {{ $theme['accent_soft'] }}; --page-border: {{ $theme['border'] }}; --page-button: {{ $theme['button'] }};">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-3xl items-center justify-center">
            <div class="w-full max-w-md rounded-[38px] border p-6 shadow-[0_32px_110px_rgba(0,0,0,0.42)] backdrop-blur-xl" style="border-color: var(--page-border); background: var(--page-surface);">
                <div class="space-y-5 text-center">
                    <div class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.26em]" style="border-color: var(--page-border); color: var(--page-muted); background: var(--page-surface-soft);">
                        Link Bio + WhatsApp
                    </div>

                    <div class="mx-auto flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border text-3xl font-bold" style="border-color: var(--page-border); background: var(--page-surface-soft);">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $landingPage->title }}" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr($landingPage->title, 0, 1)) }}
                        @endif
                    </div>

                    <div>
                        <h1 class="font-display text-4xl font-bold" style="color: var(--page-text);">{{ $landingPage->title }}</h1>
                        @if ($landingPage->headline)
                            <div class="mt-2 text-sm font-semibold" style="color: var(--page-accent-soft);">{{ $landingPage->headline }}</div>
                        @endif
                        @if ($landingPage->bio)
                            <p class="mx-auto mt-4 max-w-sm text-sm leading-7" style="color: var(--page-muted);">{{ $landingPage->bio }}</p>
                        @endif
                    </div>

                    <a href="{{ $usingCustomDomain ? url('/cta') : route('public.pages.cta', $landingPage) }}" class="group flex items-center justify-center gap-3 rounded-[24px] px-5 py-4 text-center text-sm font-extrabold text-slate-950 shadow-[0_24px_70px_rgba(0,0,0,0.24)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_28px_80px_rgba(0,0,0,0.32)]" style="background: var(--page-button);">
                        <span>{{ $landingPage->cta_label }}</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border border-black/10 bg-white/25 transition duration-200 group-hover:translate-x-0.5">
                            <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                                <path d="M5.75 10h8.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                                <path d="M10.75 5L15.75 10L10.75 15" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                            </svg>
                        </span>
                    </a>

                    <div class="space-y-3 pt-2">
                        @forelse ($landingPage->activeLinks as $link)
                            <a href="{{ $usingCustomDomain ? url('/links/'.$link->id) : route('public.pages.links', [$landingPage, $link]) }}" class="group relative flex flex-col items-center justify-center overflow-hidden rounded-[22px] border px-5 py-4 text-center transition duration-200 hover:-translate-y-1 hover:shadow-[0_20px_55px_rgba(0,0,0,0.2)]" style="border-color: var(--page-border); background: var(--page-surface-soft);">
                                <span class="pointer-events-none absolute inset-x-0 top-0 h-px bg-white/20"></span>
                                <span class="pointer-events-none absolute inset-0 opacity-0 transition duration-200 group-hover:opacity-100" style="background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0) 70%);"></span>
                                <div class="relative w-full text-center text-[1.05rem] font-semibold" style="color: var(--page-text);">{{ $link->label }}</div>
                                @if ($link->description)
                                    <div class="relative mt-1 max-w-[18rem] text-sm leading-6" style="color: var(--page-muted);">{{ $link->description }}</div>
                                @endif
                                <div class="relative mt-4 inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] transition duration-200 group-hover:bg-white/10" style="border-color: var(--page-border); color: var(--page-accent-soft); background: rgba(255,255,255,0.04);">
                                    Buka Link
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full border border-white/10 bg-black/10 transition duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5">
                                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5">
                                            <path d="M7 13L13 7" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" />
                                            <path d="M8 7H13V12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" />
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-[22px] border px-5 py-5 text-sm leading-7" style="border-color: var(--page-border); background: var(--page-surface-soft); color: var(--page-muted);">
                                Belum ada link tambahan. Hubungi via WhatsApp untuk mendapatkan informasi terbaru.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
