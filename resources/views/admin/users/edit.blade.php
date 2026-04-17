@extends('layouts.app')

@section('title', 'Master Admin - Edit Pengguna')
@section('body_class', 'app-shell text-slate-100')

@section('content')
    <div class="mx-auto w-full max-w-6xl px-6 py-6 lg:px-10 lg:py-8">
        <header class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Master Admin</div>
                <h1 class="mt-3 font-display text-4xl font-bold text-white">Edit pengguna</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                    Kelola data akun, role, status, dan WhatsApp landing page untuk pengguna ini.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($publicUrl)
                    <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer" class="btn-primary rounded-full px-5 py-3 text-sm font-semibold text-slate-950">
                        Lihat Halaman Publik
                    </a>
                @endif
                <a href="{{ route('admin.users.index') }}" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/8">
                    Kembali ke Daftar
                </a>
            </div>
        </header>

        <div class="grid gap-6 xl:grid-cols-[0.92fr_1.08fr]">
            <aside class="space-y-6">
                <div class="panel p-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Ringkasan Pengguna</div>
                    <div class="mt-4 space-y-4">
                        <div class="rounded-[22px] border border-white/10 bg-black/20 p-4">
                            <div class="text-sm text-slate-400">Nama</div>
                            <div class="mt-1 text-xl font-bold text-white">{{ $managedUser->name }}</div>
                        </div>
                        <div class="rounded-[22px] border border-white/10 bg-black/20 p-4">
                            <div class="text-sm text-slate-400">Email</div>
                            <div class="mt-1 text-xl font-bold text-white">{{ $managedUser->email }}</div>
                        </div>
                        <div class="rounded-[22px] border border-white/10 bg-black/20 p-4">
                            <div class="text-sm text-slate-400">Role / Status</div>
                            <div class="mt-3 flex flex-wrap gap-3">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $managedUser->isAdmin() ? 'border border-cyan-300/20 bg-cyan-400/10 text-cyan-100' : 'border border-white/10 bg-white/5 text-slate-200' }}">
                                    {{ $roles[$managedUser->role] ?? ucfirst($managedUser->role) }}
                                </span>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $managedUser->is_active ? 'border border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border border-rose-300/20 bg-rose-400/10 text-rose-100' }}">
                                    {{ $managedUser->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-[22px] border border-white/10 bg-black/20 p-4">
                            <div class="text-sm text-slate-400">Landing Page</div>
                            @if ($managedUser->landingPage)
                                <div class="mt-1 text-xl font-bold text-white">{{ $managedUser->landingPage->slug }}</div>
                                <div class="mt-2 text-sm text-slate-400">
                                    CTA WhatsApp: {{ $managedUser->landingPage->whatsapp_number }}
                                </div>
                            @else
                                <div class="mt-1 text-sm text-slate-400">Belum memiliki landing page.</div>
                            @endif
                        </div>
                    </div>
                </div>

                @if (! $managedUser->is(auth()->user()))
                    <div class="panel p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Bahaya</div>
                        <h2 class="mt-2 font-display text-2xl font-bold text-white">Hapus akun pengguna</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-400">
                            Tindakan ini akan menghapus akun, landing page, link, dan analytics milik pengguna tersebut secara permanen.
                        </p>

                        <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" class="mt-5">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-2xl border border-rose-300/20 bg-rose-400/10 px-5 py-4 text-sm font-extrabold text-rose-100 transition hover:bg-rose-400/20">
                                Hapus Pengguna
                            </button>
                        </form>
                    </div>
                @endif
            </aside>

            <section class="panel p-6">
                <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    @include('admin.users._form', ['roles' => $roles, 'managedUser' => $managedUser])

                    <button type="submit" class="btn-primary w-full rounded-2xl px-5 py-4 text-sm font-extrabold text-slate-950">
                        Simpan Perubahan
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
