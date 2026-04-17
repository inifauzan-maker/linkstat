@extends('layouts.app')

@section('title', 'Login')
@section('body_class', 'app-shell text-slate-100')

@section('content')
    <div class="mx-auto grid min-h-screen w-full max-w-6xl items-center gap-10 px-6 py-10 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="space-y-6">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/6 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-200">
                Login Dashboard
            </div>
            <h1 class="max-w-xl font-display text-5xl font-bold leading-[1] text-white">
                Kelola link bio dan performa CTA Anda dari satu dashboard.
            </h1>
            <p class="max-w-xl text-lg leading-8 text-slate-300">
                Masuk untuk mengedit landing page, mengganti nomor WhatsApp, dan memantau klik dari profil sosial media.
            </p>
        </section>

        <section class="panel mx-auto w-full max-w-xl p-8 lg:p-10">
            <div class="mb-8">
                <h2 class="font-display text-3xl font-bold text-white">Masuk</h2>
                <p class="mt-2 text-sm text-slate-400">Gunakan email dan password yang terdaftar.</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-200">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-input">
                    @error('email')
                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-200">Password</label>
                    <input id="password" type="password" name="password" required class="form-input">
                    @error('password')
                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-white/8 bg-white/5 px-4 py-3 text-sm text-slate-300">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-white/20 bg-transparent text-orange-400 focus:ring-orange-400/30">
                    Ingat sesi login saya
                </label>

                <button type="submit" class="btn-primary w-full rounded-2xl px-5 py-4 text-sm font-extrabold text-slate-950">
                    Masuk ke Dashboard
                </button>
            </form>

            <p class="mt-6 text-sm text-slate-400">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-semibold text-orange-200 hover:text-white">Daftar sekarang</a>
            </p>
        </section>
    </div>
@endsection
