@php
    $managedUser = $managedUser ?? null;
    $isEditing = $managedUser !== null;
    $isSelf = $isEditing && auth()->id() === $managedUser->id;
    $landingPage = $managedUser?->landingPage;
    $currentWhatsApp = old('whatsapp_number', $landingPage?->whatsapp_number ?? '62');
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="mb-2 block text-sm font-semibold text-slate-200">Nama</label>
        <input id="name" type="text" name="name" value="{{ old('name', $managedUser->name ?? '') }}" required class="form-input">
    </div>

    <div>
        <label for="email" class="mb-2 block text-sm font-semibold text-slate-200">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $managedUser->email ?? '') }}" required class="form-input">
    </div>
</div>

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="password" class="mb-2 block text-sm font-semibold text-slate-200">{{ $isEditing ? 'Password baru' : 'Password' }}</label>
        <input id="password" type="password" name="password" {{ $isEditing ? '' : 'required' }} class="form-input">
        <p class="mt-2 text-xs text-slate-500">{{ $isEditing ? 'Kosongkan jika tidak ingin mengganti password.' : 'Gunakan password yang kuat.' }}</p>
    </div>

    <div>
        <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-200">Konfirmasi Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" {{ $isEditing ? '' : 'required' }} class="form-input">
    </div>
</div>

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="role" class="mb-2 block text-sm font-semibold text-slate-200">Role</label>
        <select id="role" name="role" class="form-input" @disabled($isSelf)>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $managedUser->role ?? \App\Models\User::ROLE_USER) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($isSelf)
            <input type="hidden" name="role" value="{{ old('role', $managedUser->role) }}">
            <p class="mt-2 text-xs text-amber-200">Role akun sendiri dikunci untuk mencegah kehilangan akses admin.</p>
        @endif
    </div>

    <div>
        <label for="whatsapp_number" class="mb-2 block text-sm font-semibold text-slate-200">Nomor WhatsApp</label>
        <input id="whatsapp_number" type="text" name="whatsapp_number" value="{{ $currentWhatsApp }}" required class="form-input">
        <p class="mt-2 text-xs text-slate-500">Dipakai untuk landing page bawaan user.</p>
    </div>
</div>

<label class="flex items-center gap-3 rounded-2xl border border-white/8 bg-white/5 px-4 py-4 text-sm text-slate-300">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $managedUser->is_active ?? true)) @disabled($isSelf) class="h-4 w-4 rounded border-white/20 bg-transparent text-orange-400 focus:ring-orange-400/30">
    Akun aktif
</label>

@if ($isSelf)
    <input type="hidden" name="is_active" value="{{ old('is_active', $managedUser->is_active) ? '1' : '0' }}">
@endif
