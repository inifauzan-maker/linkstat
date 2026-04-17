<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate([
            'email' => env('MASTER_ADMIN_EMAIL', 'admin@linkpulse.test'),
        ], [
            'name' => env('MASTER_ADMIN_NAME', 'Master Admin'),
            'password' => Hash::make(env('MASTER_ADMIN_PASSWORD', 'Admin12345!')),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $admin->landingPage()->firstOrCreate([], [
            ...LandingPage::defaultAttributes($admin->name, '628000000000', false),
            'bio' => 'Akun master admin internal.',
            'is_active' => false,
        ]);

        $user = User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->landingPage()->firstOrCreate([], LandingPage::defaultAttributes($user->name, '6281234567890'));
    }
}
