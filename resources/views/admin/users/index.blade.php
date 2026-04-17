@extends('layouts.app')

@section('title', 'Master Admin - Pengguna')
@section('body_class', 'app-shell text-slate-100')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-6 py-6 lg:px-10 lg:py-8">
        <header class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Master Admin</div>
                <h1 class="mt-3 font-display text-4xl font-bold text-white lg:text-5xl">Kelola pengguna aplikasi</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                    Buat akun user baru, ubah role dan status akun, lihat landing page publik mereka, atau hapus akun yang tidak dipakai.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.analytics.index') }}" class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-5 py-3 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-400/20">
                    Bandingkan Analytics
                </a>
                <a href="{{ route('admin.users.create') }}" class="btn-primary rounded-full px-5 py-3 text-sm font-semibold text-slate-950">
                    Tambah Pengguna
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

        <div class="mb-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="panel p-5">
                <div class="text-sm text-slate-400">Total Pengguna</div>
                <div class="mt-2 font-display text-4xl font-bold text-white">{{ number_format($stats['total_users']) }}</div>
            </div>
            <div class="panel p-5">
                <div class="text-sm text-slate-400">Pengguna Aktif</div>
                <div class="mt-2 font-display text-4xl font-bold text-white">{{ number_format($stats['active_users']) }}</div>
            </div>
            <div class="panel p-5">
                <div class="text-sm text-slate-400">Master Admin</div>
                <div class="mt-2 font-display text-4xl font-bold text-white">{{ number_format($stats['admin_users']) }}</div>
            </div>
            <div class="panel p-5">
                <div class="text-sm text-slate-400">Nonaktif</div>
                <div class="mt-2 font-display text-4xl font-bold text-white">{{ number_format($stats['inactive_users']) }}</div>
            </div>
        </div>

        <div class="panel p-6">
            <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-4 lg:grid-cols-[1.4fr_0.8fr_0.8fr_auto]">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama atau email..." class="form-input">

                <select name="role" class="form-input">
                    <option value="">Semua Role</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['role'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>Aktif</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Nonaktif</option>
                </select>

                <div class="flex gap-3">
                    <button type="submit" class="rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-400/20">
                        Filter
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/8">
                        Reset
                    </a>
                </div>
            </form>

            <div class="mt-6 overflow-hidden rounded-[24px] border border-white/10 bg-black/20">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-white/10 text-left text-xs uppercase tracking-[0.22em] text-slate-500">
                            <tr>
                                <th class="px-4 py-4 font-semibold">Pengguna</th>
                                <th class="px-4 py-4 font-semibold">Role</th>
                                <th class="px-4 py-4 font-semibold">Status</th>
                                <th class="px-4 py-4 font-semibold">Landing Page</th>
                                <th class="px-4 py-4 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/6 text-slate-200">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-white">{{ $user->name }}</div>
                                        <div class="mt-1 text-sm text-slate-400">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $user->isAdmin() ? 'border border-cyan-300/20 bg-cyan-400/10 text-cyan-100' : 'border border-white/10 bg-white/5 text-slate-200' }}">
                                            {{ $roles[$user->role] ?? ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $user->is_active ? 'border border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border border-rose-300/20 bg-rose-400/10 text-rose-100' }}">
                                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($user->landingPage)
                                            <div class="font-semibold text-white">{{ $user->landingPage->slug }}</div>
                                            <a href="{{ $user->landingPage->preferredPublicUrl() }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-sm text-cyan-200 hover:text-white">
                                                Lihat halaman publik
                                            </a>
                                        @else
                                            <span class="text-slate-500">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:border-white/20 hover:bg-white/8">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">
                                        Tidak ada pengguna yang cocok dengan filter saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
