<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Role;
use App\Models\Room;
use App\Models\TemporaryResidence;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemporaryResidenceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TemporaryResidence $temporaryResidence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $adminRole = Role::create(['role_name' => 'Admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'temporary-residence-admin@example.test',
            'phone' => '0900000099',
            'role_id' => $adminRole->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'full_name' => 'Nguyễn Văn Tạm Trú',
            'date_of_birth' => '1995-01-01',
            'gender' => 'male',
            'cccd' => '079000000099',
            'phone' => '0900000199',
        ]);
        $room = Room::create([
            'room_code' => 'TTR-01',
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'max_people' => 4,
            'current_people' => 1,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-TTR-01',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'deposit_amount' => 3000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => Contract::STATUS_ACTIVE,
        ]);
        $this->temporaryResidence = TemporaryResidence::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'note' => 'Hồ sơ gốc',
            'signature' => 'data:image/png;base64,c2lnbmF0dXJl',
            'signed_at' => '2026-01-02 09:00:00',
        ]);
    }

    public function test_signed_record_cannot_be_edited_deleted_or_signed_again(): void
    {
        $trackedFields = [
            'tenant_id', 'contract_id', 'start_date', 'end_date', 'status', 'note', 'signature', 'signed_at',
        ];
        $original = collect($trackedFields)
            ->mapWithKeys(fn (string $field): array => [$field => $this->temporaryResidence->getRawOriginal($field)])
            ->all();

        $this->actingAs($this->admin)->put(
            route('admin.temporary_residences.update', $this->temporaryResidence),
            [
                'tenant_id' => $this->temporaryResidence->tenant_id,
                'contract_id' => $this->temporaryResidence->contract_id,
                'start_date' => '2026-02-01',
                'end_date' => '2026-11-30',
                'status' => 'cancelled',
                'note' => 'Nội dung bị thay đổi',
            ]
        )->assertSessionHasErrors('temporary_residence');

        $this->get(route('admin.temporary_residences.edit', $this->temporaryResidence))
            ->assertSessionHasErrors('temporary_residence');

        $this->patch(route('admin.temporary_residences.cancel', $this->temporaryResidence))
            ->assertSessionHasErrors('temporary_residence');

        $this->post(route('admin.temporary_residences.sign', $this->temporaryResidence), [
            'signature' => 'data:image/png;base64,bmV3LXNpZ25hdHVyZQ==',
        ])->assertSessionHasErrors('temporary_residence');

        $this->assertDatabaseHas('temporary_residences', ['id' => $this->temporaryResidence->id]);
        $fresh = $this->temporaryResidence->fresh();
        $actual = collect($trackedFields)
            ->mapWithKeys(fn (string $field): array => [$field => $fresh->getRawOriginal($field)])
            ->all();
        $this->assertSame($original, $actual);
    }

    public function test_unsigned_record_is_cancelled_with_audit_instead_of_deleted(): void
    {
        $this->temporaryResidence->update([
            'signature' => null,
            'signed_at' => null,
        ]);

        $this->actingAs($this->admin)->patch(
            route('admin.temporary_residences.cancel', $this->temporaryResidence),
            ['cancellation_reason' => 'Khách khai báo sai hồ sơ ban đầu.']
        )->assertRedirect(route('admin.temporary_residences.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('temporary_residences', [
            'id' => $this->temporaryResidence->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->admin->id,
            'cancellation_reason' => 'Khách khai báo sai hồ sơ ban đầu.',
        ]);
        $this->assertNotNull($this->temporaryResidence->fresh()->cancelled_at);

        $this->patch(route('admin.temporary_residences.cancel', $this->temporaryResidence), [
            'cancellation_reason' => 'Thử hủy hồ sơ thêm một lần nữa.',
        ])->assertSessionHasErrors('temporary_residence');
    }

    public function test_update_ignores_forged_dates_and_keeps_contract_period(): void
    {
        $this->temporaryResidence->update(['signature' => null, 'signed_at' => null]);

        $this->actingAs($this->admin)->put(
            route('admin.temporary_residences.update', $this->temporaryResidence),
            [
                'tenant_id' => $this->temporaryResidence->tenant_id,
                'contract_id' => $this->temporaryResidence->contract_id,
                'start_date' => '2030-01-01',
                'end_date' => '2030-12-31',
                'status' => 'active',
                'note' => 'Ngày phải tiếp tục lấy từ hợp đồng.',
            ]
        )->assertRedirect(route('admin.temporary_residences.index'))
            ->assertSessionHasNoErrors();

        $fresh = $this->temporaryResidence->fresh();
        $this->assertSame('2026-01-01', $fresh->start_date->toDateString());
        $this->assertSame('2026-12-31', $fresh->end_date->toDateString());
        $this->assertSame('Ngày phải tiếp tục lấy từ hợp đồng.', $fresh->note);
    }

    public function test_signature_must_be_a_bounded_valid_png_data_url(): void
    {
        $this->temporaryResidence->update(['signature' => null, 'signed_at' => null]);
        $route = route('admin.temporary_residences.sign', $this->temporaryResidence);

        $this->actingAs($this->admin)->post($route, [
            'signature' => 'data:text/html;base64,PHNjcmlwdD4=',
        ])->assertSessionHasErrors('signature');

        $this->post($route, [
            'signature' => 'data:image/png;base64,'.base64_encode('not-a-real-png'),
        ])->assertSessionHasErrors('signature');

        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $this->post($route, ['signature' => $png])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($png, $this->temporaryResidence->fresh()->signature);
        $this->assertNotNull($this->temporaryResidence->fresh()->signed_at);
    }
}
