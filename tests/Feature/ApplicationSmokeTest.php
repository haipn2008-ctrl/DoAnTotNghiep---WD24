<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

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

    public function test_room_list_uses_tailwind_pagination(): void
    {
        $admin = $this->createUser('Admin', 1);

        foreach (range(1, 11) as $number) {
            Room::create([
                'room_code' => sprintf('TEST-%02d', $number),
                'floor' => 1,
                'price' => 2000000,
                'area' => 25,
                'max_people' => 4,
                'current_people' => 0,
                'status' => Room::STATUS_AVAILABLE,
            ]);
        }

        $this->actingAs($admin)
            ->get('/admin/rooms')
            ->assertSuccessful()
            ->assertSee('aria-label="Pagination Navigation"', false)
            ->assertDontSee('class="pagination"', false);
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
