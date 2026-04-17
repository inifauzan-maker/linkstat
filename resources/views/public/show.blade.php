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

                    <a href="{{ $usingCustomDomain ? url('/cta') : route('public.pages.cta', $landingPage) }}" class="block rounded-[24px] px-5 py-4 text-center text-sm font-extrabold text-slate-950 shadow-[0_24px_70px_rgba(0,0,0,0.24)]" style="background: var(--page-button);">
                        {{ $landingPage->cta_label }}
                    </a>

                    <div class="space-y-3 pt-2">
                        @forelse ($landingPage->activeLinks as $link)
                            <a href="{{ $usingCustomDomain ? url('/links/'.$link->id) : route('public.pages.links', [$landingPage, $link]) }}" class="block rounded-[22px] border px-5 py-4 text-left transition hover:-translate-y-0.5" style="border-color: var(--page-border); background: var(--page-surface-soft);">
                                <div class="font-semibold" style="color: var(--page-text);">{{ $link->label }}</div>
                                @if ($link->description)
                                    <div class="mt-1 text-sm leading-6" style="color: var(--page-muted);">{{ $link->description }}</div>
                                @endif
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
