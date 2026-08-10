<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $clientRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $adminRole = Role::create(['id' => 10, 'role_name' => 'Admin']);
        $this->clientRole = Role::create(['id' => 20, 'role_name' => 'User']);
        $this->admin = $this->user($adminRole, 'room-admin@example.test');
    }

    public function test_only_admin_can_access_room_pages_and_direct_mutations(): void
    {
        $room = $this->room();
        $this->get('/admin/rooms')->assertRedirect('/login');
        $this->post('/admin/rooms', [])->assertRedirect('/login');
        $client = $this->user($this->clientRole, 'room-client@example.test');
        $this->actingAs($client)->get('/admin/rooms')->assertForbidden();
        $this->post('/admin/rooms', $this->payload())->assertForbidden();
        $this->put("/admin/rooms/{$room->id}", $this->payload())->assertForbidden();
        $this->delete("/admin/rooms/{$room->id}")->assertForbidden();
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_admin_can_list_filter_and_view_rooms_while_invalid_filters_are_rejected(): void
    {
        $one = $this->room(['room_code' => 'FILTER-A', 'status' => Room::STATUS_AVAILABLE]);
        $two = $this->room(['room_code' => 'FILTER-B', 'status' => Room::STATUS_MAINTENANCE]);
        $this->actingAs($this->admin)->get('/admin/rooms?room_code=FILTER-A&status=available')
            ->assertOk()->assertSee($one->room_code)->assertDontSee($two->room_code);
        $this->get("/admin/rooms/{$one->id}")->assertOk()->assertSee($one->room_code);
        $this->get('/admin/rooms?status=invalid')->assertSessionHasErrors('status');
        $this->get('/admin/rooms/999999')->assertNotFound();
    }

    public function test_admin_can_create_room_with_capacity_amenities_and_image(): void
    {
        Storage::fake('public');
        $amenity = Amenity::create(['name' => 'Điều hòa']);
        $response = $this->actingAs($this->admin)->post('/admin/rooms', $this->payload([
            'amenities' => [$amenity->id],
            'image' => UploadedFile::fake()->image('room.jpg'),
        ]));
        $response->assertRedirect(route('admin.rooms.index'))->assertSessionHas('success');
        $room = Room::where('room_code', 'ROOM-NEW')->firstOrFail();
        $this->assertSame(6, $room->max_people);
        $this->assertDatabaseHas('amenity_room', ['room_id' => $room->id, 'amenity_id' => $amenity->id]);
        Storage::disk('public')->assertExists($room->thumbnail);
    }

    public function test_create_rejects_duplicate_invalid_boundaries_missing_amenity_and_bad_image_without_writes(): void
    {
        Storage::fake('public');
        $this->room(['room_code' => 'ROOM-NEW']);
        $response = $this->actingAs($this->admin)->post('/admin/rooms', $this->payload([
            'floor' => 0, 'price' => -1, 'area' => 0, 'max_people' => 2,
            'current_people' => 3, 'status' => 'unknown', 'amenities' => [999999],
            'image' => UploadedFile::fake()->create('room.pdf', 10, 'application/pdf'),
        ]));
        $response->assertSessionHasErrors(['room_code', 'floor', 'price', 'area', 'current_people', 'status', 'amenities.0', 'image']);
        $this->assertDatabaseCount('rooms', 1);
        $this->assertDatabaseCount('amenity_room', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_admin_can_update_room_and_replacing_image_removes_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('rooms/old.jpg', 'old');
        $room = $this->room(['thumbnail' => 'rooms/old.jpg']);
        $this->actingAs($this->admin)->put("/admin/rooms/{$room->id}", $this->payload([
            'room_code' => $room->room_code, 'image' => UploadedFile::fake()->image('new.jpg'),
        ]))->assertRedirect(route('admin.rooms.index'));
        $room->refresh();
        Storage::disk('public')->assertMissing('rooms/old.jpg');
        Storage::disk('public')->assertExists($room->thumbnail);
    }

    public function test_active_contract_prevents_inconsistent_room_update_and_deletion(): void
    {
        [$room, $contract] = $this->contract(Contract::STATUS_ACTIVE);
        $this->actingAs($this->admin)->get('/admin/rooms')
            ->assertOk()
            ->assertDontSee('action="'.route('admin.rooms.destroy', $room).'"', false);
        $this->actingAs($this->admin)->put("/admin/rooms/{$room->id}", $this->payload([
            'room_code' => $room->room_code, 'status' => Room::STATUS_AVAILABLE, 'current_people' => 0,
        ]))->assertSessionHasErrors(['status', 'current_people']);
        $this->delete("/admin/rooms/{$room->id}")->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => Room::STATUS_OCCUPIED]);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
    }

    public function test_occupied_status_cannot_be_assigned_manually_without_active_contract(): void
    {
        $this->actingAs($this->admin)->post('/admin/rooms', $this->payload([
            'status' => Room::STATUS_OCCUPIED,
            'current_people' => 1,
        ]))->assertSessionHasErrors('status');
        $this->assertDatabaseMissing('rooms', ['room_code' => 'ROOM-NEW']);

        $room = $this->room();
        $this->put("/admin/rooms/{$room->id}", $this->payload([
            'room_code' => $room->room_code,
            'status' => Room::STATUS_OCCUPIED,
            'current_people' => 1,
        ]))->assertSessionHasErrors('status');
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => Room::STATUS_AVAILABLE]);
    }

    public function test_historical_contract_is_preserved_and_empty_room_deletion_removes_image_and_is_not_repeatable(): void
    {
        [$historicalRoom, $contract] = $this->contract(Contract::STATUS_TERMINATED);
        $this->actingAs($this->admin)->delete("/admin/rooms/{$historicalRoom->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);

        Storage::fake('public');
        Storage::disk('public')->put('rooms/delete.jpg', 'image');
        $empty = $this->room(['room_code' => 'DELETE-ME', 'thumbnail' => 'rooms/delete.jpg']);
        $this->delete("/admin/rooms/{$empty->id}")->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('rooms', ['id' => $empty->id]);
        Storage::disk('public')->assertMissing('rooms/delete.jpg');
        $this->delete("/admin/rooms/{$empty->id}")->assertNotFound();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['room_code' => 'ROOM-NEW', 'floor' => 2, 'price' => 3500000,
            'area' => 25, 'max_people' => 6, 'current_people' => 0,
            'status' => Room::STATUS_AVAILABLE, 'description' => 'Phòng kiểm thử'], $overrides);
    }

    private function room(array $overrides = []): Room
    {
        static $sequence = 0;
        $sequence++;

        return Room::create(array_merge(['room_code' => 'ROOM-'.$sequence, 'floor' => 1,
            'price' => 3000000, 'area' => 20, 'max_people' => 4, 'current_people' => 0,
            'status' => Room::STATUS_AVAILABLE], $overrides));
    }

    private function user(Role $role, string $email): User
    {
        return User::create(['name' => 'User', 'email' => $email, 'phone' => '0900000000',
            'role_id' => $role->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
    }

    private function contract(string $status): array
    {
        $user = $this->user($this->clientRole, uniqid('tenant-', true).'@example.test');
        $tenant = Tenant::create(['user_id' => $user->id, 'full_name' => 'Tenant', 'gender' => 'other',
            'cccd' => uniqid(), 'phone' => '0912345678', 'email' => $user->email]);
        $room = $this->room(['status' => $status === Contract::STATUS_ACTIVE ? Room::STATUS_OCCUPIED : Room::STATUS_AVAILABLE,
            'current_people' => $status === Contract::STATUS_ACTIVE ? 1 : 0]);
        $contract = Contract::create(['contract_code' => uniqid('CONTRACT-'), 'room_id' => $room->id,
            'tenant_id' => $tenant->id, 'monthly_rent' => 3000000, 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'status' => $status]);

        return [$room, $contract];
    }
}
