@extends('layouts.app')

@section('title', 'Master Admin - Tambah Pengguna')
@section('body_class', 'app-shell text-slate-100')

@section('content')
    <div class="mx-auto w-full max-w-5xl px-6 py-6 lg:px-10 lg:py-8">
        <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Master Admin</div>
                <h1 class="mt-3 font-display text-4xl font-bold text-white">Tambah pengguna baru</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                    User baru akan langsung dibuatkan landing page dasar agar bisa segera login dan mulai mengatur halaman mereka.
                </p>
            </div>

            <a href="{{ route('admin.users.index') }}" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/8">
                Kembali ke Daftar
            </a>
        </header>

        <div class="panel p-6">
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                @csrf

                @include('admin.users._form', ['roles' => $roles])

                <button type="submit" class="btn-primary w-full rounded-2xl px-5 py-4 text-sm font-extrabold text-slate-950">
                    Simpan Pengguna
                </button>
            </form>
        </div>
    </div>
@endsection
