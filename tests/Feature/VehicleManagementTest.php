<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractTenant;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        Storage::fake('local');
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'phone' => '0900000001', 'role_id' => $adminRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
        $this->client = User::create(['name' => 'Client', 'email' => 'client@example.test', 'phone' => '0900000002', 'role_id' => $clientRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
        $this->tenant = Tenant::create(['user_id' => $this->client->id, 'full_name' => 'Client', 'date_of_birth' => '1995-01-01', 'gender' => 'other', 'cccd' => '079000000001', 'cccd_issue_date' => '2020-01-01', 'cccd_issue_place' => 'Hà Nội', 'phone' => $this->client->phone, 'email' => $this->client->email, 'address' => 'Hà Nội']);
        $this->activeContractWithResidents(1);
    }

    public function test_client_registers_vehicle_and_admin_approves_it(): void
    {
        $this->actingAs($this->client)->get('/client/vehicles')
            ->assertOk()
            ->assertSee('Phương tiện của tôi')
            ->assertSee('Ảnh phương tiện')
            ->assertDontSee('Ghi chú')
            ->assertDontSee('Màu sắc')
            ->assertDontSee('Ô tô')
            ->assertDontSee('Khác');
        $this->post('/client/vehicles', $this->payload())->assertRedirect()->assertSessionHasNoErrors();

        $vehicle = $this->tenant->vehicles()->sole();
        $this->assertSame(Vehicle::STATUS_PENDING, $vehicle->status);
        $this->assertSame($this->client->id, $vehicle->submitted_by);
        $reviewAlert = ContractLifecycleAlert::query()->where('type', 'vehicle_review')->sole();
        $this->assertSame($vehicle->id, $reviewAlert->vehicle_id);
        $this->assertSame($this->tenant->id, $reviewAlert->tenant_id);
        $this->assertNull($reviewAlert->resolved_at);

        $this->get(route('client.vehicles.index'))
            ->assertOk()
            ->assertSee('Hủy yêu cầu')
            ->assertDontSee('Lưu và gửi duyệt lại');
        $this->put(route('client.vehicles.update', $vehicle), $this->payload())
            ->assertStatus(409);

        $this->actingAs($this->admin)->put(route('admin.vehicles.review', $vehicle), ['status' => Vehicle::STATUS_APPROVED])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(Vehicle::STATUS_APPROVED, $vehicle->fresh()->status);
        $this->assertSame($this->admin->id, $vehicle->fresh()->reviewed_by);
        $this->assertNotNull($vehicle->fresh()->reviewed_at);
        $this->assertNotNull($reviewAlert->fresh()->resolved_at);

        $this->get(route('admin.tenants.show', $this->tenant))
            ->assertOk()
            ->assertDontSee('Lý do nếu từ chối');
        $this->actingAs($this->client)->get(route('client.vehicles.index'))
            ->assertOk()
            ->assertSee('Đổi phương tiện')
            ->assertSee('Thông tin mới sẽ được chuyển về trạng thái chờ quản trị viên duyệt lại.');
    }

    public function test_newly_activated_client_without_an_active_contract_can_open_vehicle_page(): void
    {
        $newClient = User::create([
            'name' => 'Khách vừa kích hoạt', 'email' => 'newly-activated@example.test',
            'phone' => '0900000099', 'role_id' => $this->client->role_id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        Tenant::create([
            'user_id' => $newClient->id, 'full_name' => $newClient->name,
            'cccd' => '079000000099', 'phone' => $newClient->phone, 'email' => $newClient->email,
        ]);

        $this->actingAs($newClient)->get(route('client.vehicles.index'))
            ->assertOk()
            ->assertSee('Phương tiện của tôi')
            ->assertSee('Chưa thể đăng ký phương tiện')
            ->assertSee('chưa có hợp đồng đã nhận phòng')
            ->assertDontSee('action="'.route('client.vehicles.store').'"', false);

        $this->post(route('client.vehicles.store'), $this->payload())
            ->assertSessionHasErrors('vehicle');
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_client_editing_approved_vehicle_sends_it_back_to_pending(): void
    {
        $vehicle = $this->tenant->vehicles()->create($this->payload() + ['status' => Vehicle::STATUS_APPROVED, 'reviewed_by' => $this->admin->id, 'reviewed_at' => now()]);

        $this->actingAs($this->client)->put(route('client.vehicles.update', $vehicle), $this->payload(['vehicle_name' => 'Yamaha Grande']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $vehicle->refresh();
        $this->assertSame('Yamaha Grande', $vehicle->vehicle_name);
        $this->assertSame(Vehicle::STATUS_PENDING, $vehicle->status);
        $this->assertNull($vehicle->reviewed_by);
        $this->assertDatabaseHas('contract_lifecycle_alerts', [
            'vehicle_id' => $vehicle->id,
            'type' => 'vehicle_review',
            'resolved_at' => null,
        ]);

        $this->get(route('client.vehicles.index'))
            ->assertOk()
            ->assertDontSee('Đổi phương tiện');
        $this->actingAs($this->admin)->get(route('admin.tenants.show', $this->tenant))
            ->assertOk()
            ->assertSee('Lý do nếu từ chối');
    }

    public function test_client_cannot_change_or_delete_another_tenants_vehicle(): void
    {
        $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'phone' => '0900000003', 'role_id' => $this->client->role_id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
        $otherTenant = Tenant::create(['user_id' => $other->id, 'full_name' => 'Other', 'cccd' => '079000000002', 'phone' => $other->phone, 'email' => $other->email]);
        $vehicle = $otherTenant->vehicles()->create($this->payload(['license_plate' => '30B-99999']));
        $vehicle->update(['vehicle_image' => 'vehicles/other.jpg']);
        Storage::disk('local')->put('vehicles/other.jpg', 'private-image');

        $this->actingAs($this->client)->put(route('client.vehicles.update', $vehicle), $this->payload())->assertNotFound();
        $this->delete(route('client.vehicles.destroy', $vehicle))->assertNotFound();
        $this->get(route('client.vehicles.image', $vehicle))->assertNotFound();
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
    }

    public function test_admin_must_give_reason_when_rejecting_vehicle(): void
    {
        $vehicle = $this->tenant->vehicles()->create($this->payload());
        $this->actingAs($this->admin)->from(route('admin.tenants.show', $this->tenant))
            ->put(route('admin.vehicles.review', $vehicle), ['status' => Vehicle::STATUS_REJECTED])
            ->assertSessionHasErrors('review_note');

        $this->put(route('admin.vehicles.review', $vehicle), ['status' => Vehicle::STATUS_REJECTED, 'review_note' => 'Biển số chưa rõ'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(Vehicle::STATUS_REJECTED, $vehicle->fresh()->status);
        $this->assertSame('Biển số chưa rõ', $vehicle->fresh()->review_note);
    }

    public function test_client_can_register_bicycle_without_license_plate(): void
    {
        $this->actingAs($this->client)->post('/client/vehicles', $this->payload([
            'vehicle_type' => 'bicycle',
            'vehicle_name' => 'Xe đạp Thống Nhất',
            'license_plate' => null,
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $vehicle = $this->tenant->vehicles()->sole();
        $this->assertSame('bicycle', $vehicle->vehicle_type);
        $this->assertNull($vehicle->license_plate);
    }

    public function test_client_cannot_register_removed_vehicle_types(): void
    {
        foreach (['car', 'other'] as $type) {
            $this->actingAs($this->client)->post('/client/vehicles', $this->payload([
                'vehicle_type' => $type,
            ]))->assertSessionHasErrors('vehicle_type');
        }

        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_client_can_upload_replace_and_remove_vehicle_image(): void
    {
        $this->actingAs($this->client)->post('/client/vehicles', $this->payload([
            'vehicle_image' => UploadedFile::fake()->image('vehicle.jpg'),
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $vehicle = $this->tenant->vehicles()->sole();
        $oldImage = $vehicle->vehicle_image;
        Storage::disk('local')->assertExists($oldImage);
        Storage::disk('public')->assertMissing($oldImage);
        $this->get(route('client.vehicles.image', $vehicle))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.vehicles.image', $vehicle))->assertOk();

        $this->actingAs($this->admin)->put(route('admin.vehicles.review', $vehicle), [
            'status' => Vehicle::STATUS_APPROVED,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($this->client)->put(route('client.vehicles.update', $vehicle), $this->payload([
            'vehicle_image' => UploadedFile::fake()->image('replacement.png'),
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $vehicle->refresh();
        Storage::disk('local')->assertMissing($oldImage);
        Storage::disk('local')->assertExists($vehicle->vehicle_image);

        $this->actingAs($this->admin)->put(route('admin.vehicles.review', $vehicle), [
            'status' => Vehicle::STATUS_APPROVED,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $currentImage = $vehicle->vehicle_image;
        $this->actingAs($this->client)->delete(route('client.vehicles.destroy', $vehicle), [
            'removal_reason' => 'Không còn gửi phương tiện này trong nhà trọ.',
        ])->assertRedirect();
        Storage::disk('local')->assertExists($currentImage);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => Vehicle::STATUS_REMOVED]);

        $removedAlert = ContractLifecycleAlert::query()->where('type', 'vehicle_removed')->sole();
        $this->assertSame($this->tenant->id, $removedAlert->tenant_id);
        $this->assertNull($removedAlert->resolved_at);
        $this->actingAs($this->admin)->get(route('admin.notifications.open', $removedAlert))
            ->assertRedirect(route('admin.tenants.show', $this->tenant));
        $this->assertNotNull($removedAlert->fresh()->resolved_at);
    }

    public function test_client_cancels_pending_request_and_can_submit_again(): void
    {
        $this->actingAs($this->client)->post('/client/vehicles', $this->payload())
            ->assertRedirect()->assertSessionHasNoErrors();

        $vehicle = $this->tenant->vehicles()->sole();
        $reviewAlert = ContractLifecycleAlert::query()->where('type', 'vehicle_review')->sole();

        $this->delete(route('client.vehicles.destroy', $vehicle), [
            'removal_reason' => 'Thông tin đăng ký ban đầu chưa chính xác.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => Vehicle::STATUS_CANCELLED]);
        $this->assertNotNull($reviewAlert->fresh()->resolved_at);
        $this->assertDatabaseMissing('contract_lifecycle_alerts', ['type' => 'vehicle_removed']);

        $this->post('/client/vehicles', $this->payload(['license_plate' => '30A-67890']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vehicles', ['license_plate' => '30A-67890', 'status' => Vehicle::STATUS_PENDING]);
    }

    public function test_client_can_restore_a_cancelled_vehicle_for_review(): void
    {
        $this->actingAs($this->client)->post(route('client.vehicles.store'), $this->payload())
            ->assertSessionHasNoErrors();
        $vehicle = $this->tenant->vehicles()->sole();

        $this->delete(route('client.vehicles.destroy', $vehicle), [
            'removal_reason' => 'Thông tin ban đầu chưa phù hợp nên tạm thời hủy yêu cầu.',
        ])->assertSessionHas('success');
        $this->assertNull($vehicle->fresh()->license_plate);

        $this->get(route('client.vehicles.index'))
            ->assertOk()
            ->assertSee('Phương tiện đã hủy hoặc đã gỡ')
            ->assertSee(route('client.vehicles.restore', $vehicle), false);

        $this->patch(route('client.vehicles.restore', $vehicle), [
            'restoration_reason' => 'Thông tin đã được kiểm tra và muốn gửi đăng ký lại.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'license_plate' => '30A-12345',
            'status' => Vehicle::STATUS_PENDING,
            'restored_by' => $this->client->id,
        ]);
        $this->assertNotNull($vehicle->fresh()->restored_at);
        $this->assertDatabaseHas('contract_lifecycle_alerts', [
            'vehicle_id' => $vehicle->id,
            'type' => 'vehicle_review',
            'resolved_at' => null,
        ]);
    }

    public function test_representative_selects_vehicle_owner_and_each_person_has_only_one_vehicle(): void
    {
        [$contract, $secondTenant] = $this->activeContractWithResidents(2);

        $this->actingAs($this->client)->get(route('client.vehicles.index'))
            ->assertOk()
            ->assertSee('Chủ xe')
            ->assertSee($this->tenant->full_name)
            ->assertSee($secondTenant->full_name);

        $this->post(route('client.vehicles.store'), $this->payload([
            'owner_tenant_id' => $secondTenant->id,
            'license_plate' => '30C-22222',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehicles', [
            'tenant_id' => $secondTenant->id,
            'license_plate' => '30C-22222',
            'submitted_by' => $this->client->id,
        ]);
        $this->get(route('client.vehicles.index'))
            ->assertOk()
            ->assertSee('Chủ xe: '.$secondTenant->full_name);

        $this->post(route('client.vehicles.store'), $this->payload([
            'owner_tenant_id' => $secondTenant->id,
            'license_plate' => '30C-33333',
        ]))->assertSessionHasErrors('owner_tenant_id');

        $this->assertSame(1, $secondTenant->vehicles()->count());
        $this->assertSame($contract->id, $secondTenant->contractMemberships()->sole()->contract_id);
    }

    public function test_room_cannot_reserve_more_vehicles_than_checked_in_residents(): void
    {
        [, $secondTenant, $thirdTenant] = $this->activeContractWithResidents(3);

        $this->tenant->vehicles()->create($this->payload(['license_plate' => '30D-10001']) + [
            'status' => Vehicle::STATUS_APPROVED,
        ]);
        // Dữ liệu cũ có thể đã có hơn một xe cho một người; giới hạn phòng vẫn phải chặn độc lập.
        $this->tenant->vehicles()->create($this->payload(['license_plate' => '30D-10002']) + [
            'status' => Vehicle::STATUS_PENDING,
        ]);
        $secondTenant->vehicles()->create($this->payload(['license_plate' => '30D-10003']) + [
            'status' => Vehicle::STATUS_APPROVED,
        ]);

        $this->actingAs($this->client)->post(route('client.vehicles.store'), $this->payload([
            'owner_tenant_id' => $thirdTenant->id,
            'license_plate' => '30D-10004',
        ]))->assertSessionHasErrors('vehicle');

        $this->assertDatabaseMissing('vehicles', ['license_plate' => '30D-10004']);
    }

    public function test_admin_cannot_approve_a_second_vehicle_for_the_same_person(): void
    {
        $first = $this->tenant->vehicles()->create($this->payload() + [
            'status' => Vehicle::STATUS_APPROVED,
        ]);
        $second = $this->tenant->vehicles()->create($this->payload(['license_plate' => '30E-22222']) + [
            'status' => Vehicle::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)->put(route('admin.vehicles.review', $second), [
            'status' => Vehicle::STATUS_APPROVED,
        ])->assertSessionHasErrors('status');

        $this->assertSame(Vehicle::STATUS_APPROVED, $first->fresh()->status);
        $this->assertSame(Vehicle::STATUS_PENDING, $second->fresh()->status);
    }

    public function test_settling_client_cannot_manage_or_register_vehicles(): void
    {
        $contract = $this->tenant->contracts()->latest('id')->firstOrFail();
        $contract->forceFill(['status' => Contract::STATUS_SETTLING])->save();
        $this->client->update(['status' => User::STATUS_SETTLING]);

        $this->actingAs($this->client)->get(route('client.vehicles.index'))
            ->assertRedirect(route('client.invoices.index'));
        $this->post(route('client.vehicles.store'), $this->payload())
            ->assertRedirect(route('client.invoices.index'));
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_existing_public_vehicle_images_are_moved_to_private_storage(): void
    {
        $path = 'vehicles/legacy.jpg';
        Storage::disk('public')->put($path, 'legacy-private-image');
        $vehicle = $this->tenant->vehicles()->create($this->payload() + [
            'vehicle_image' => $path,
            'status' => Vehicle::STATUS_APPROVED,
        ]);
        Storage::disk('public')->put('vehicles/orphan.jpg', 'orphan-private-image');

        $migration = require database_path('migrations/2026_08_25_000020_move_vehicle_images_to_private_storage.php');
        $migration->up();

        Storage::disk('public')->assertMissing($path);
        Storage::disk('local')->assertExists($vehicle->fresh()->vehicle_image);
        $this->assertSame('legacy-private-image', Storage::disk('local')->get($vehicle->fresh()->vehicle_image));
        $this->assertCount(1, Storage::disk('local')->files('vehicles/orphaned'));
    }

    private function activeContractWithResidents(int $residentCount): array
    {
        $room = Room::create([
            'room_code' => 'XE-'.$residentCount,
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'max_people' => $residentCount,
            'current_people' => $residentCount,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::create([
            'contract_code' => 'HD-XE-'.$residentCount,
            'room_id' => $room->id,
            'tenant_id' => $this->tenant->id,
            'representative_tenant_id' => $this->tenant->id,
            'monthly_rent' => 3000000,
            'number_of_people' => $residentCount,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);
        $contract->forceFill(['status' => Contract::STATUS_ACTIVE])->save();

        $tenants = [$this->tenant];
        for ($index = 2; $index <= $residentCount; $index++) {
            $tenants[] = Tenant::create([
                'full_name' => 'Người ở '.$index,
                'cccd' => '0790000000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'phone' => '09100000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            ]);
        }

        foreach ($tenants as $index => $tenant) {
            ContractTenant::create([
                'contract_id' => $contract->id,
                'tenant_id' => $tenant->id,
                'role' => $index === 0 ? ContractTenant::ROLE_REPRESENTATIVE : ContractTenant::ROLE_TENANT,
                'full_name' => $tenant->full_name,
                'status' => ContractTenant::STATUS_CHECKED_IN,
                'actual_move_in_at' => now()->subMonth(),
            ]);
        }

        return array_merge([$contract], array_slice($tenants, 1));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['vehicle_type' => 'motorcycle', 'vehicle_name' => 'Honda Vision', 'license_plate' => '30A-12345'], $overrides);
    }
}
