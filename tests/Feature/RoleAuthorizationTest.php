<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Role::create(['id' => User::ROLE_ADMIN, 'role_name' => 'Admin']);
        Role::create(['id' => User::ROLE_CLIENT, 'role_name' => 'User']);
    }

    public function test_client_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->createUser(User::ROLE_CLIENT, 'client@example.com'))
            ->get('/admin/invoices')
            ->assertForbidden();
    }

    public function test_admin_cannot_access_client_portal(): void
    {
        $this->actingAs($this->createUser(User::ROLE_ADMIN, 'admin@example.com'))
            ->get('/client')
            ->assertForbidden();
    }

    public function test_each_role_can_access_its_own_area(): void
    {
        $this->actingAs($this->createUser(User::ROLE_ADMIN, 'admin-own@example.com'))
            ->get('/admin/invoices')
            ->assertSuccessful();

        $this->actingAs($this->createUser(User::ROLE_CLIENT, 'client-own@example.com'))
            ->get('/client')
            ->assertSuccessful();
    }

    public function test_dashboard_redirects_each_role_to_the_correct_portal(): void
    {
        $this->actingAs($this->createUser(User::ROLE_ADMIN, 'admin-dashboard@example.com'))
            ->get('/dashboard')
            ->assertRedirect('/admin');

        $this->actingAs($this->createUser(User::ROLE_CLIENT, 'client-dashboard@example.com'))
            ->get('/dashboard')
            ->assertRedirect('/client');
    }

    private function createUser(int $roleId, string $email): User
    {
        return User::create([
            'name' => 'Người dùng kiểm thử',
            'email' => $email,
            'phone' => '0900000000',
            'role_id' => $roleId,
            'password' => 'password',
        ]);
    }
}
