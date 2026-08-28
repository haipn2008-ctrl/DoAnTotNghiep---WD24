<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomImage;
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

    public function test_empty_room_evidence_log_is_collapsed_until_admin_clicks_add(): void
    {
        $room = $this->room(['room_code' => 'EMPTY-EVIDENCE-LOG']);

        $this->actingAs($this->admin)->get(route('admin.rooms.show', $room))
            ->assertOk()
            ->assertSee('Chưa có nhật ký.')
            ->assertSee('data-room-evidence-toggle', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('data-room-evidence-form class="hidden grid', false)
            ->assertSee('Thêm nhật ký')
            ->assertDontSee('Chưa có ảnh hiện trạng.');
    }

    public function test_room_list_has_direct_export_and_live_filter_controls_without_filter_buttons(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.rooms.index'));

        $response->assertOk()
            ->assertSee('data-room-filter', false)
            ->assertSee('data-room-search', false)
            ->assertSee('data-room-status', false)
            ->assertSee('data-room-export', false)
            ->assertSee('href="'.route('admin.rooms.export').'"', false)
            ->assertDontSee('>Lọc</button>', false)
            ->assertDontSee('>Làm mới</a>', false);

        $this->get('/admin/rooms/export/download')->assertNotFound();
    }

    public function test_ajax_room_search_and_status_filter_return_only_matching_rows(): void
    {
        $available = $this->room(['room_code' => 'LIVE-AVAILABLE', 'status' => Room::STATUS_AVAILABLE]);
        $maintenance = $this->room(['room_code' => 'LIVE-MAINTENANCE', 'status' => Room::STATUS_MAINTENANCE]);

        $this->actingAs($this->admin)
            ->get(route('admin.rooms.index', [
                'room_code' => 'AVAILABLE',
                'status' => Room::STATUS_AVAILABLE,
            ]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee($available->room_code)
            ->assertDontSee($maintenance->room_code)
            ->assertDontSee('data-room-filter', false);

        $this->get(route('admin.rooms.index', ['status' => Room::STATUS_MAINTENANCE]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()
            ->assertSee($maintenance->room_code)
            ->assertDontSee($available->room_code);
    }

    public function test_admin_can_create_room_with_locked_empty_state_inventory_and_multiple_evidence_images(): void
    {
        Storage::fake('public');
        $amenity = Amenity::create(['name' => 'Điều hòa', 'is_quantifiable' => true]);
        $response = $this->actingAs($this->admin)->post('/admin/rooms', $this->payload([
            'inventory' => [$amenity->id => [
                'selected' => 1, 'quantity' => 2, 'condition' => 'damaged',
                'note' => 'Dàn lạnh bị móp nhẹ ở góc phải.',
            ]],
            'images' => [
                UploadedFile::fake()->image('overview.jpg'),
                UploadedFile::fake()->image('air-conditioner.jpg'),
            ],
        ]));
        $response->assertRedirect(route('admin.rooms.index'))->assertSessionHas('success');
        $room = Room::where('room_code', 'ROOM-NEW')->firstOrFail();
        $this->assertSame(6, $room->max_people);
        $this->assertSame(Room::STATUS_AVAILABLE, $room->status);
        $this->assertSame(0, $room->current_people);
        $this->assertDatabaseHas('utility_readings', [
            'room_id' => $room->id,
            'contract_id' => null,
            'reading_type' => 'baseline',
            'electricity_old' => 125,
            'electricity_new' => 125,
            'water_old' => 18,
            'water_new' => 18,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('amenity_room', [
            'room_id' => $room->id, 'amenity_id' => $amenity->id,
            'quantity' => 2, 'condition' => 'damaged', 'note' => 'Dàn lạnh bị móp nhẹ ở góc phải.',
        ]);
        $this->assertSame(0, $room->amenities()->utilities()->count());
        $this->assertDatabaseCount('room_images', 2);
        $this->assertDatabaseHas('room_images', [
            'room_id' => $room->id, 'evidence_type' => RoomImage::TYPE_BASELINE,
            'uploaded_by' => $this->admin->id,
        ]);
        Storage::disk('public')->assertExists($room->thumbnail);
    }

    public function test_create_room_selects_all_active_assets_by_default_and_hides_mattress(): void
    {
        $assetCount = Amenity::query()->active()->assets()->count();
        $response = $this->actingAs($this->admin)->get('/admin/rooms/create');

        $response->assertOk()->assertDontSee('Nệm');
        $this->assertSame($assetCount, substr_count($response->getContent(), 'checked data-inventory-checkbox'));

        $this->post('/admin/rooms', $this->payload())->assertRedirect(route('admin.rooms.index'));
        $room = Room::where('room_code', 'ROOM-NEW')->firstOrFail();
        $this->assertSame($assetCount, $room->amenities()->assets()->count());
    }

    public function test_damaged_room_asset_requires_its_own_note(): void
    {
        $amenity = Amenity::create(['name' => 'Tủ kiểm thử ghi chú', 'is_quantifiable' => true]);

        $this->actingAs($this->admin)->post('/admin/rooms', $this->payload([
            'inventory' => [$amenity->id => [
                'selected' => 1,
                'quantity' => 1,
                'condition' => 'damaged',
                'note' => '',
            ]],
        ]))->assertSessionHasErrors("inventory.{$amenity->id}.note");

        $this->assertDatabaseMissing('rooms', ['room_code' => 'ROOM-NEW']);
    }

    public function test_create_rejects_duplicate_invalid_boundaries_missing_amenity_and_bad_image_without_writes(): void
    {
        Storage::fake('public');
        $this->room(['room_code' => 'ROOM-NEW']);
        $response = $this->actingAs($this->admin)->post('/admin/rooms', $this->payload([
            'floor' => 0, 'price' => -1, 'area' => 0, 'max_people' => 2,
            'current_people' => 3, 'status' => 'occupied', 'amenities' => [999999],
            'images' => [UploadedFile::fake()->create('room.pdf', 10, 'application/pdf')],
        ]));
        $response->assertSessionHasErrors(['room_code', 'floor', 'price', 'area', 'current_people', 'status', 'amenities.0', 'images.0']);
        $this->assertDatabaseCount('rooms', 1);
        $this->assertDatabaseCount('amenity_room', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_create_requires_non_negative_initial_meter_readings(): void
    {
        $this->actingAs($this->admin)->post('/admin/rooms', $this->payload([
            'initial_electricity' => -1,
            'initial_water' => null,
        ]))->assertSessionHasErrors(['initial_electricity', 'initial_water']);

        $this->assertDatabaseCount('rooms', 0);
        $this->assertDatabaseCount('utility_readings', 0);
    }

    public function test_room_with_only_baseline_reading_can_still_be_deleted(): void
    {
        $this->actingAs($this->admin)->post('/admin/rooms', $this->payload())
            ->assertRedirect(route('admin.rooms.index'));

        $room = Room::where('room_code', 'ROOM-NEW')->firstOrFail();
        $this->assertDatabaseHas('utility_readings', [
            'room_id' => $room->id,
            'reading_type' => 'baseline',
        ]);

        $this->delete("/admin/rooms/{$room->id}")
            ->assertRedirect(route('admin.rooms.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
        $this->assertDatabaseMissing('utility_readings', ['room_id' => $room->id]);
    }

    public function test_inactive_amenities_are_hidden_and_cannot_be_submitted(): void
    {
        $removed = Amenity::create(['name' => 'Ban công', 'is_quantifiable' => false, 'is_active' => false]);
        Amenity::updateOrCreate(['name' => 'Wi-Fi'], ['category' => Amenity::CATEGORY_UTILITY, 'is_quantifiable' => false, 'is_active' => true]);
        Amenity::updateOrCreate(['name' => 'Tủ lạnh'], ['category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true, 'is_active' => true]);

        $this->actingAs($this->admin)->get('/admin/rooms/create')
            ->assertOk()
            ->assertDontSee('Ban công')
            ->assertSee('Tài sản trong phòng')
            ->assertDontSee('Wi-Fi')
            ->assertSee('Tủ lạnh')
            ->assertSee('Chọn tất cả tài sản')
            ->assertSee('data-inventory-toggle-all', false)
            ->assertDontSee('data-inventory-toggle-group', false)
            ->assertSee('js-image-preview-input', false)
            ->assertSee('images-preview', false);

        $this->post('/admin/rooms', $this->payload([
            'inventory' => [$removed->id => ['selected' => 1, 'quantity' => 1, 'condition' => 'normal']],
        ]))->assertSessionHasErrors("inventory.{$removed->id}");
        $this->assertDatabaseMissing('rooms', ['room_code' => 'ROOM-NEW']);
    }

    public function test_admin_update_appends_evidence_and_keeps_old_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('rooms/old.jpg', 'old');
        $room = $this->room(['thumbnail' => 'rooms/old.jpg']);
        $this->actingAs($this->admin)->put("/admin/rooms/{$room->id}", $this->payload([
            'room_code' => $room->room_code,
            'status' => Room::STATUS_AVAILABLE,
            'images' => [UploadedFile::fake()->image('new.jpg')],
        ]))->assertRedirect(route('admin.rooms.show', $room));
        $room->refresh();
        Storage::disk('public')->assertExists('rooms/old.jpg');
        Storage::disk('public')->assertExists($room->thumbnail);
        $this->assertDatabaseHas('room_images', ['room_id' => $room->id, 'evidence_type' => RoomImage::TYPE_BASELINE]);
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

    public function test_admin_can_append_contract_linked_checkout_evidence_but_cannot_link_another_room_contract(): void
    {
        Storage::fake('public');
        [$room, $contract] = $this->contract(Contract::STATUS_ACTIVE);

        $this->actingAs($this->admin)->post(route('admin.rooms.evidence.store', $room), [
            'evidence_type' => RoomImage::TYPE_CHECKOUT,
            'contract_id' => $contract->id,
            'taken_at' => '2000-01-01 00:00:00',
            'caption' => 'Ảnh đối chiếu khi trả phòng',
            'images' => [UploadedFile::fake()->image('checkout-1.jpg'), UploadedFile::fake()->image('checkout-2.jpg')],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseCount('room_images', 2);
        $this->assertDatabaseHas('room_images', [
            'room_id' => $room->id, 'contract_id' => $contract->id,
            'evidence_type' => RoomImage::TYPE_CHECKOUT, 'uploaded_by' => $this->admin->id,
        ]);
        $firstImage = RoomImage::firstOrFail();
        $this->assertNotNull($firstImage->sha256);
        $this->assertTrue($firstImage->taken_at->greaterThan(now()->subMinute()));

        [$anotherRoom, $anotherContract] = $this->contract(Contract::STATUS_ACTIVE);
        $this->post(route('admin.rooms.evidence.store', $room), [
            'evidence_type' => RoomImage::TYPE_CHECKOUT,
            'contract_id' => $anotherContract->id,
            'images' => [UploadedFile::fake()->image('wrong-room.jpg')],
        ])->assertSessionHasErrors('contract_id');
        $this->assertDatabaseCount('room_images', 2);
        $this->assertNotSame($room->id, $anotherRoom->id);
    }

    public function test_after_return_evidence_requires_contract_and_deprecated_types_or_client_upload_are_rejected(): void
    {
        Storage::fake('public');
        $room = $this->room();
        $payload = [
            'evidence_type' => RoomImage::TYPE_CHECKOUT,
            'images' => [UploadedFile::fake()->image('handover.jpg')],
        ];

        $this->actingAs($this->admin)->post(route('admin.rooms.evidence.store', $room), $payload)
            ->assertSessionHasErrors('contract_id');
        $this->post(route('admin.rooms.evidence.store', $room), [
            ...$payload, 'evidence_type' => RoomImage::TYPE_MAINTENANCE,
        ])->assertSessionHasErrors('evidence_type');

        $client = $this->user($this->clientRole, 'evidence-client@example.test');
        $this->actingAs($client)->post(route('admin.rooms.evidence.store', $room), [
            ...$payload, 'evidence_type' => RoomImage::TYPE_BASELINE,
        ])->assertForbidden();
        $this->delete('/admin/rooms/'.$room->id.'/evidence/1')->assertNotFound();
        $this->assertDatabaseCount('room_images', 0);
    }

    public function test_room_detail_lists_checked_in_members_without_requiring_tenant_accounts(): void
    {
        [$room, $contract] = $this->contract(Contract::STATUS_ACTIVE);
        $representative = $contract->tenant;
        $contract->forceFill([
            'representative_tenant_id' => $representative->id,
            'number_of_people' => 2,
        ])->save();
        ContractTenant::create([
            'contract_id' => $contract->id, 'tenant_id' => $representative->id,
            'role' => ContractTenant::ROLE_REPRESENTATIVE, 'full_name' => $representative->full_name,
            'phone' => $representative->phone, 'status' => ContractTenant::STATUS_CHECKED_IN,
            'actual_move_in_at' => now()->subMonth(),
        ]);
        $member = Tenant::create([
            'user_id' => null,
            'full_name' => 'Nguyễn Thành Viên',
            'date_of_birth' => '1990-01-01',
            'cccd' => '012345678999',
            'phone' => '0987654321',
        ]);
        $this->assertNull($member->user_id);
        ContractTenant::create([
            'contract_id' => $contract->id, 'tenant_id' => $member->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => 'Nguyễn Thành Viên', 'phone' => '0987654321',
            'relationship' => 'Bạn', 'status' => ContractTenant::STATUS_CHECKED_IN,
            'actual_move_in_at' => now()->subMonth(),
        ]);
        $room->forceFill(['current_people' => 2])->save();

        $this->actingAs($this->admin)->get(route('admin.rooms.show', $room))
            ->assertOk()
            ->assertSee('Người thuê đại diện · Tài khoản liên hệ')
            ->assertSee($representative->full_name)
            ->assertSee('Nguyễn Thành Viên')
            ->assertSee(route('admin.tenants.show', $representative), false)
            ->assertSee(route('admin.tenants.show', $member), false)
            ->assertSee('2 người');
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

    public function test_historical_room_is_retired_and_excluded_from_operational_use(): void
    {
        [$room, $contract] = $this->contract(Contract::STATUS_TERMINATED);

        $this->actingAs($this->admin)->patch(route('admin.rooms.retire', $room), [
            'retirement_reason' => 'Phòng được chuyển đổi sang mục đích sử dụng khác.',
        ])->assertRedirect(route('admin.rooms.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => Room::STATUS_RETIRED,
            'retired_by' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'room_id' => $room->id]);
        $this->get(route('admin.rooms.edit', $room))->assertStatus(409);
        $this->get(route('admin.contracts.create'))->assertDontSee($room->room_code);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['room_code' => 'ROOM-NEW', 'floor' => 2, 'price' => 3500000,
            'area' => 25, 'max_people' => 6, 'initial_electricity' => 125,
            'initial_water' => 18, 'description' => 'Phòng kiểm thử'], $overrides);
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
        static $tenantSequence = 0;
        $tenantSequence++;
        $user = $this->user($this->clientRole, uniqid('tenant-', true).'@example.test');
        $tenant = Tenant::create(['user_id' => $user->id, 'full_name' => 'Tenant', 'gender' => 'other',
            'cccd' => uniqid(), 'phone' => '091234'.str_pad((string) $tenantSequence, 4, '0', STR_PAD_LEFT), 'email' => $user->email]);
        $room = $this->room(['status' => $status === Contract::STATUS_ACTIVE ? Room::STATUS_OCCUPIED : Room::STATUS_AVAILABLE,
            'current_people' => $status === Contract::STATUS_ACTIVE ? 1 : 0]);
        $contract = Contract::query()->forceCreate(['contract_code' => uniqid('CONTRACT-'), 'room_id' => $room->id,
            'tenant_id' => $tenant->id, 'monthly_rent' => 3000000, 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'status' => $status]);

        return [$room, $contract];
    }
}
