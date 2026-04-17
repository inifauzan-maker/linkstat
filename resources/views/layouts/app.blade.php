<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Landing Page'))</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen antialiased {{ trim($__env->yieldContent('body_class')) }}">
        @if (session('status'))
            <div class="fixed inset-x-0 top-4 z-50 mx-auto flex w-full max-w-xl justify-center px-4" data-flash>
                <div class="flash-message w-full rounded-full border border-emerald-300/20 bg-emerald-400/10 px-5 py-3 text-center text-sm font-semibold text-emerald-100 shadow-2xl backdrop-blur-xl">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="fixed inset-x-0 top-4 z-40 mx-auto flex w-full max-w-xl justify-center px-4">
                <div class="w-full rounded-[26px] border border-rose-300/20 bg-rose-400/10 p-4 text-sm text-rose-50 shadow-2xl backdrop-blur-xl">
                    <div class="font-semibold">Periksa input berikut:</div>
                    <ul class="mt-2 space-y-1 text-rose-100/90">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </body>
</html>
