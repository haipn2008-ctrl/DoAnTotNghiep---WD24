<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_render_successfully(): void
    {
        $admin = $this->createUser('Admin', 1);

        $this->actingAs($admin);

        $pages = [
            '/admin',
            '/admin/overview',
            '/admin/overview/revenue-chart',
            '/admin/overview/revenue-stats',
            '/admin/overview/room-stats',
            '/admin/overview/fill-rate',
            '/admin/rooms',
            '/admin/rooms/create',
            '/admin/tenants',
            '/admin/tenants/create',
            '/admin/contracts',
            '/admin/contracts/create',
            '/admin/utilities',
            '/admin/utilities/create',
            '/admin/invoices',
            '/admin/invoices/generate',
            '/admin/invoices/payments',
            '/admin/invoices/export',
            '/admin/invoices/payments/export',
            '/admin/users',
            '/admin/users/create',
            '/admin/roles',
            '/admin/settings/electricity',
        ];

        foreach ($pages as $page) {
            $this->get($page)->assertSuccessful();
        }
    }

    public function test_client_dashboard_renders_without_a_tenant_profile(): void
    {
        $client = $this->createUser('Client', 2);

        $this->actingAs($client)
            ->get('/client')
            ->assertSuccessful();
    }

    private function createUser(string $name, int $roleId): User
    {
        $role = Role::create([
            'id' => $roleId,
            'role_name' => $name,
        ]);

        return User::create([
            'name' => $name,
            'email' => strtolower($name).'@example.com',
            'phone' => '0900000000',
            'role_id' => $role->id,
            'password' => 'password',
        ]);
    }
}
