@extends('layouts.app')

@section('title', 'Daftar')
@section('body_class', 'app-shell text-slate-100')

@section('content')
    <div class="mx-auto grid min-h-screen w-full max-w-6xl items-center gap-10 px-6 py-10 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="space-y-6">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/6 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-200">
                Mulai Landing Page
            </div>
            <h1 class="max-w-xl font-display text-5xl font-bold leading-[1] text-white">
                Buat link bio dengan tombol WhatsApp dan analytics-nya.
            </h1>
            <p class="max-w-xl text-lg leading-8 text-slate-300">
                Setelah daftar, Anda langsung mendapatkan dashboard untuk mengatur slug unik, profil landing page, CTA, dan daftar link promosi atau edukasi.
            </p>
        </section>

        <section class="panel mx-auto w-full max-w-xl p-8 lg:p-10">
            <div class="mb-8">
                <h2 class="font-display text-3xl font-bold text-white">Buat Akun</h2>
                <p class="mt-2 text-sm text-slate-400">Data ini akan dipakai untuk membuat landing page pertama Anda.</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-slate-200">Nama / Brand</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="form-input">
                    @error('name')
                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-200">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required class="form-input">
                    @error('email')
                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="whatsapp_number" class="mb-2 block text-sm font-semibold text-slate-200">Nomor WhatsApp</label>
                    <input id="whatsapp_number" type="text" name="whatsapp_number" value="{{ old('whatsapp_number', '62') }}" required class="form-input">
                    <p class="mt-2 text-xs text-slate-500">Gunakan format negara, misalnya `62812xxxxxx`.</p>
                    @error('whatsapp_number')
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

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-200">Konfirmasi Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required class="form-input">
                </div>

                <button type="submit" class="btn-primary w-full rounded-2xl px-5 py-4 text-sm font-extrabold text-slate-950">
                    Buat Akun dan Landing Page
                </button>
            </form>

            <p class="mt-6 text-sm text-slate-400">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-semibold text-orange-200 hover:text-white">Masuk di sini</a>
            </p>
        </section>
    </div>
@endsection
