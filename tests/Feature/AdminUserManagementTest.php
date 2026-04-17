<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_redirected_to_admin_panel_after_login(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'Password123!',
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.users.index'));
    }

    public function test_app_host_root_redirects_admin_to_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/')
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_non_admin_cannot_access_admin_user_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'User Baru',
                'email' => 'userbaru@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => User::ROLE_USER,
                'is_active' => '1',
                'whatsapp_number' => '6281212345678',
            ])->assertRedirect(route('admin.users.index'));

        $createdUser = User::query()->where('email', 'userbaru@example.com')->firstOrFail();

        $this->assertDatabaseHas('landing_pages', [
            'user_id' => $createdUser->id,
            'whatsapp_number' => '6281212345678',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $createdUser), [
                'name' => 'User Baru Update',
                'email' => 'userbaru@example.com',
                'password' => '',
                'password_confirmation' => '',
                'role' => User::ROLE_ADMIN,
                'whatsapp_number' => '6281299999999',
                'is_active' => '1',
            ])->assertRedirect(route('admin.users.edit', $createdUser));

        $createdUser->refresh();

        $this->assertSame(User::ROLE_ADMIN, $createdUser->role);
        $this->assertSame('User Baru Update', $createdUser->name);
        $this->assertSame('6281299999999', $createdUser->landingPage->whatsapp_number);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $createdUser))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $createdUser->id,
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => 'Password123!',
        ]);

        $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'Password123!',
        ])
            ->assertSessionHasErrors('email')
            ->assertRedirect();

        $this->assertGuest();
    }
}
