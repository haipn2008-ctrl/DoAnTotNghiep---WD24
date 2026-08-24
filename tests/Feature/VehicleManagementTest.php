<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\ContractLifecycleAlert;
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
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'phone' => '0900000001', 'role_id' => $adminRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
        $this->client = User::create(['name' => 'Client', 'email' => 'client@example.test', 'phone' => '0900000002', 'role_id' => $clientRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
        $this->tenant = Tenant::create(['user_id' => $this->client->id, 'full_name' => 'Client', 'date_of_birth' => '1995-01-01', 'gender' => 'other', 'cccd' => '079000000001', 'cccd_issue_date' => '2020-01-01', 'cccd_issue_place' => 'Hà Nội', 'phone' => $this->client->phone, 'email' => $this->client->email, 'address' => 'Hà Nội']);
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

        $this->actingAs($this->client)->put(route('client.vehicles.update', $vehicle), $this->payload())->assertNotFound();
        $this->delete(route('client.vehicles.destroy', $vehicle))->assertNotFound();
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
        Storage::disk('public')->assertExists($oldImage);

        $this->actingAs($this->admin)->put(route('admin.vehicles.review', $vehicle), [
            'status' => Vehicle::STATUS_APPROVED,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($this->client)->put(route('client.vehicles.update', $vehicle), $this->payload([
            'vehicle_image' => UploadedFile::fake()->image('replacement.png'),
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $vehicle->refresh();
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($vehicle->vehicle_image);

        $this->actingAs($this->admin)->put(route('admin.vehicles.review', $vehicle), [
            'status' => Vehicle::STATUS_APPROVED,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $currentImage = $vehicle->vehicle_image;
        $this->actingAs($this->client)->delete(route('client.vehicles.destroy', $vehicle))->assertRedirect();
        Storage::disk('public')->assertMissing($currentImage);

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

        $this->delete(route('client.vehicles.destroy', $vehicle))
            ->assertRedirect()
            ->assertSessionHas('success', 'Đã hủy yêu cầu. Bạn có thể đăng ký lại phương tiện từ đầu.');

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertNotNull($reviewAlert->fresh()->resolved_at);
        $this->assertDatabaseMissing('contract_lifecycle_alerts', ['type' => 'vehicle_removed']);

        $this->post('/client/vehicles', $this->payload(['license_plate' => '30A-67890']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vehicles', ['license_plate' => '30A-67890', 'status' => Vehicle::STATUS_PENDING]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['vehicle_type' => 'motorcycle', 'vehicle_name' => 'Honda Vision', 'license_plate' => '30A-12345'], $overrides);
    }
}
