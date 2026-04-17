<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_keys(User::roles()))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = $this->buildIndexQuery($filters)->paginate(12)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => User::roles(),
            'stats' => [
                'total_users' => User::query()->count(),
                'active_users' => User::query()->where('is_active', true)->count(),
                'admin_users' => User::query()->where('role', User::ROLE_ADMIN)->count(),
                'inactive_users' => User::query()->where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => User::roles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'whatsapp_number' => ['required', 'string', 'max:25'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $user->landingPage()->create(
            LandingPage::defaultAttributes(
                $validated['name'],
                $validated['whatsapp_number'],
                $user->is_active
            )
        );

        return redirect()->route('admin.users.index')->with('status', 'Pengguna baru berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        $user->load('landingPage');

        return view('admin.users.edit', [
            'managedUser' => $user,
            'roles' => User::roles(),
            'publicUrl' => $user->landingPage ? $user->landingPage->preferredPublicUrl() : null,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'whatsapp_number' => ['required', 'string', 'max:25'],
        ]);

        $newRole = $validated['role'];
        $newStatus = $request->boolean('is_active');

        if ($user->is($request->user()) && ($newRole !== $user->role || $newStatus !== $user->is_active)) {
            return back()->withErrors([
                'role' => 'Master admin tidak bisa mengubah role atau status akunnya sendiri dari panel ini.',
            ]);
        }

        if ($user->isAdmin() && $newRole !== User::ROLE_ADMIN && User::query()->where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->withErrors([
                'role' => 'Sistem harus memiliki minimal satu master admin aktif.',
            ]);
        }

        if ($user->isAdmin() && ! $newStatus && User::query()->where('role', User::ROLE_ADMIN)->where('is_active', true)->count() <= 1) {
            return back()->withErrors([
                'is_active' => 'Anda tidak bisa menonaktifkan master admin aktif terakhir.',
            ]);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $newRole,
            'is_active' => $newStatus,
            ...($validated['password'] ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        $landingPage = $user->landingPage()->firstOrCreate([], [
            ...LandingPage::defaultAttributes($validated['name'], $validated['whatsapp_number'], $user->is_active),
            'is_active' => $user->is_active,
        ]);

        $landingPage->update([
            'whatsapp_number' => $validated['whatsapp_number'],
            'is_active' => $user->is_active ? $landingPage->is_active : false,
        ]);

        return redirect()->route('admin.users.edit', $user)->with('status', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors([
                'delete' => 'Master admin tidak bisa menghapus akunnya sendiri.',
            ]);
        }

        if ($user->isAdmin() && User::query()->where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->withErrors([
                'delete' => 'Sistem harus memiliki minimal satu master admin.',
            ]);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil dihapus.');
    }

    protected function buildIndexQuery(array $filters): Builder
    {
        return User::query()
            ->with('landingPage')
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
                $search = trim($filters['q']);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when(filled($filters['role'] ?? null), fn ($query) => $query->where('role', $filters['role']))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest();
    }
}
