@extends('layouts.app')

@section('title', 'Landing Page Bio dengan CTA WhatsApp')
@section('body_class', 'app-shell text-slate-100')

@section('content')
    <div class="relative isolate overflow-hidden">
        <div class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_top_left,rgba(255,155,77,0.22),transparent_18%),radial-gradient(circle_at_top_right,rgba(255,94,91,0.16),transparent_16%),radial-gradient(circle_at_bottom,rgba(34,211,238,0.14),transparent_26%)]"></div>
        <div class="absolute inset-x-0 top-0 -z-10 h-[38rem] bg-[linear-gradient(180deg,rgba(9,16,26,0.2),rgba(9,16,26,0))]"></div>
        <div class="absolute inset-0 -z-10 opacity-30 [background-image:linear-gradient(rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.04)_1px,transparent_1px)] [background-size:90px_90px] [mask-image:radial-gradient(circle_at_center,black,transparent_80%)]"></div>

        <header class="mx-auto w-full max-w-7xl px-6 pt-6 lg:px-10">
            <div class="panel-soft flex flex-col gap-4 px-5 py-4 md:flex-row md:items-center md:justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.28em] text-slate-100">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 font-display text-lg text-white shadow-[0_20px_60px_rgba(255,138,61,0.25)]">LP</span>
                    LinkPulse
                </a>

                <div class="hidden items-center gap-2 lg:flex">
                    <div class="rounded-full border border-white/10 bg-white/6 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-300">
                        Link Bio
                    </div>
                    <div class="rounded-full border border-white/10 bg-white/6 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-300">
                        CTA WhatsApp
                    </div>
                    <div class="rounded-full border border-white/10 bg-white/6 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-300">
                        Analytics
                    </div>
                </div>

                <nav class="flex flex-wrap items-center gap-3">
                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.users.index') : route('dashboard') }}" class="rounded-full border border-white/15 px-5 py-2.5 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/8">
                            {{ auth()->user()->isAdmin() ? 'Panel Admin' : 'Dashboard' }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full border border-white/15 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-white/30 hover:bg-white/8">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="btn-primary rounded-full px-5 py-2.5 text-sm font-semibold text-slate-950">
                            Mulai Gratis
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-6 pb-20 pt-8 lg:px-10 lg:pb-28 lg:pt-10">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.02fr)_minmax(410px,0.98fr)] lg:items-start xl:gap-14">
                <section class="space-y-8 pt-2 lg:pt-8">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/6 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-200">
                        Landing Page Bio + Analytics
                    </div>

                    <div class="space-y-5">
                        <div class="text-sm font-semibold uppercase tracking-[0.28em] text-orange-100/85">
                            Untuk edukasi, promo, dan closing yang lebih cepat
                        </div>
                        <h1 class="max-w-4xl font-display text-[clamp(3.45rem,8vw,6.15rem)] font-bold leading-[0.9] text-white">
                            Link bio yang rapi, fokus, dan langsung mengarah ke WhatsApp.
                        </h1>
                        <p class="max-w-2xl text-lg leading-8 text-slate-300">
                            Bangun halaman model Linktree atau Lynk.id yang terasa lebih serius untuk bisnis. User dapat link unik,
                            CTA utama ke WhatsApp, daftar link edukasi atau promo, dan analytics untuk membaca view, CTA click, dan performa source traffic.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        @auth
                            <a href="{{ auth()->user()->isAdmin() ? route('admin.users.index') : route('dashboard') }}" class="btn-primary rounded-full px-6 py-3.5 text-sm font-semibold text-slate-950 shadow-[0_22px_50px_rgba(255,138,61,0.35)]">
                                {{ auth()->user()->isAdmin() ? 'Buka Panel Admin' : 'Buka Dashboard' }}
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn-primary rounded-full px-6 py-3.5 text-sm font-semibold text-slate-950 shadow-[0_22px_50px_rgba(255,138,61,0.35)]">
                                Buat Landing Page
                            </a>
                            <a href="{{ route('login') }}" class="rounded-full border border-white/15 px-6 py-3.5 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/8">
                                Saya Sudah Punya Akun
                            </a>
                        @endauth
                    </div>

                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                        <div class="panel p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Alur yang langsung terasa</div>
                                    <div class="mt-2 font-display text-2xl font-bold text-white">Satu halaman untuk profil, CTA, dan promosi</div>
                                </div>
                                <div class="rounded-full border border-orange-300/20 bg-orange-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-orange-100">
                                    Siap tempel di bio
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-[22px] border border-white/10 bg-black/20 px-4 py-4">
                                    <div class="text-sm font-semibold text-white">CTA WhatsApp utama</div>
                                    <div class="mt-1 text-sm leading-6 text-slate-300">Chat bisa dibuka dengan pesan yang sudah dipersiapkan.</div>
                                </div>
                                <div class="rounded-[22px] border border-white/10 bg-black/20 px-4 py-4">
                                    <div class="text-sm font-semibold text-white">Link edukasi & promo</div>
                                    <div class="mt-1 text-sm leading-6 text-slate-300">Tambahkan banyak link tanpa membuat audiens bingung.</div>
                                </div>
                                <div class="rounded-[22px] border border-white/10 bg-black/20 px-4 py-4">
                                    <div class="text-sm font-semibold text-white">Analytics per source</div>
                                    <div class="mt-1 text-sm leading-6 text-slate-300">Lihat traffic dari Instagram, TikTok, atau direct secara terpisah.</div>
                                </div>
                                <div class="rounded-[22px] border border-white/10 bg-black/20 px-4 py-4">
                                    <div class="text-sm font-semibold text-white">Link unik tiap user</div>
                                    <div class="mt-1 text-sm leading-6 text-slate-300">Setiap akun langsung punya halaman publik siap share.</div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="panel-soft p-5">
                                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Keuntungan Utama</div>
                                <div class="mt-3 space-y-3">
                                    <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
                                        <div class="text-3xl font-bold text-white">1 link</div>
                                        <div class="mt-1 text-sm text-slate-300">untuk bio, campaign, dan CTA chat</div>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
                                        <div class="text-3xl font-bold text-white">3 aksi</div>
                                        <div class="mt-1 text-sm text-slate-300">view, CTA click, dan link click terekam</div>
                                    </div>
                                </div>
                            </div>

                            <div class="panel-soft p-5">
                                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Cocok Untuk</div>
                                <div class="mt-3 flex flex-wrap gap-2 text-sm text-slate-200">
                                    <span class="rounded-full border border-white/10 bg-white/6 px-3 py-2">Klinik</span>
                                    <span class="rounded-full border border-white/10 bg-white/6 px-3 py-2">Konsultan</span>
                                    <span class="rounded-full border border-white/10 bg-white/6 px-3 py-2">Creator</span>
                                    <span class="rounded-full border border-white/10 bg-white/6 px-3 py-2">UMKM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="relative lg:pt-6">
                    <div class="absolute -left-8 top-14 h-32 w-32 rounded-full bg-orange-400/20 blur-3xl"></div>
                    <div class="absolute -right-6 bottom-10 h-28 w-28 rounded-full bg-cyan-400/20 blur-3xl"></div>

                    <div class="panel relative overflow-hidden p-4 md:p-5">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,184,77,0.12),transparent_22%),linear-gradient(180deg,rgba(255,255,255,0.04),rgba(255,255,255,0))]"></div>
                        <div class="relative rounded-[32px] border border-white/10 bg-[linear-gradient(165deg,rgba(36,44,57,0.92),rgba(20,24,34,0.96))] p-5 md:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Preview Experience</div>
                                    <div class="mt-2 font-display text-2xl font-bold text-white">Bio page dan dashboard dalam satu frame</div>
                                </div>
                                <div class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100">
                                    Siap custom
                                </div>
                            </div>

                            <div class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px]">
                                <div class="rounded-[30px] border border-white/10 bg-[#101827] p-4 shadow-[0_24px_70px_rgba(0,0,0,0.32)]">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-[0.22em] text-orange-200/80">Preview Publik</div>
                                            <div class="mt-2 font-display text-xl font-bold text-white">dr.nadya-care</div>
                                        </div>
                                        <div class="rounded-full border border-emerald-300/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-100">Aktif</div>
                                    </div>

                                    <div class="mt-5 rounded-[28px] border border-white/10 px-5 py-6 text-white" style="background: radial-gradient(circle at top left, rgba(255, 183, 77, 0.22), transparent 30%), linear-gradient(160deg, #23141d 0%, #0f172a 45%, #09111c 100%);">
                                        <div class="mx-auto max-w-[20rem] space-y-4">
                                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border border-white/15 bg-white/8 font-display text-2xl font-bold shadow-[0_18px_40px_rgba(255,255,255,0.08)]">
                                                DN
                                            </div>
                                            <div class="text-center">
                                                <div class="font-display text-[1.95rem] font-bold leading-tight">Dr. Nadya Care</div>
                                                <div class="mt-1 text-sm font-semibold text-orange-100/90">Tips kulit sensitif & promo treatment</div>
                                                <p class="mt-3 text-sm leading-6 text-slate-200/80">
                                                    Edukasi singkat, jadwal konsultasi, dan promo bulanan dalam satu halaman.
                                                </p>
                                            </div>

                                            <a href="#" class="block rounded-[22px] px-5 py-4 text-center text-sm font-extrabold text-slate-950 shadow-[0_24px_60px_rgba(255,138,61,0.35)]" style="background: linear-gradient(135deg, #ffb84d 0%, #ff8a3d 42%, #ff5e5b 100%);">
                                                Chat via WhatsApp
                                            </a>

                                            <div class="space-y-3">
                                                <div class="rounded-[20px] border border-white/10 bg-white/8 px-4 py-4">
                                                    <div class="font-semibold">Promo Facial April</div>
                                                    <div class="mt-1 text-sm text-slate-200/80">Diskon 25% untuk pelanggan baru.</div>
                                                </div>
                                                <div class="rounded-[20px] border border-white/10 bg-white/8 px-4 py-4">
                                                    <div class="font-semibold">Panduan skincare pemula</div>
                                                    <div class="mt-1 text-sm text-slate-200/80">Checklist ringan yang mudah dipahami.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="panel-soft p-5">
                                        <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Dashboard Analytics</div>
                                        <div class="mt-4 grid grid-cols-2 gap-3">
                                            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                                                <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Page View</div>
                                                <div class="mt-2 text-3xl font-bold text-white">1,248</div>
                                            </div>
                                            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                                                <div class="text-xs uppercase tracking-[0.18em] text-slate-500">CTA Click</div>
                                                <div class="mt-2 text-3xl font-bold text-white">294</div>
                                            </div>
                                            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                                                <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Visitor</div>
                                                <div class="mt-2 text-3xl font-bold text-white">863</div>
                                            </div>
                                            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                                                <div class="text-xs uppercase tracking-[0.18em] text-slate-500">CTA Rate</div>
                                                <div class="mt-2 text-3xl font-bold text-white">23.6%</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel-soft p-5">
                                        <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Yang Bisa Diatur</div>
                                        <div class="mt-4 space-y-3 text-sm leading-6 text-slate-300">
                                            <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3">Slug unik halaman dan domain custom</div>
                                            <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3">Avatar, bio, headline, dan warna visual</div>
                                            <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3">Nomor WhatsApp, pesan CTA, dan daftar link</div>
                                        </div>
                                    </div>

                                    <div class="panel-soft p-5">
                                        <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Traffic Source</div>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-200">
                                                <span>instagram.com</span>
                                                <span class="font-semibold text-white">46%</span>
                                            </div>
                                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-200">
                                                <span>tiktok.com</span>
                                                <span class="font-semibold text-white">31%</span>
                                            </div>
                                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-200">
                                                <span>direct / unknown</span>
                                                <span class="font-semibold text-white">23%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
@endsection
