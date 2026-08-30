<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Role;
use App\Models\Room;
use App\Models\TemporaryResidence;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemporaryResidenceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TemporaryResidence $temporaryResidence;

    private ContractTenant $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');

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
        $this->member = ContractTenant::create([
            'contract_id' => $contract->id,
            'tenant_id' => $tenant->id,
            'role' => ContractTenant::ROLE_REPRESENTATIVE,
            'full_name' => $tenant->full_name,
            'date_of_birth' => $tenant->date_of_birth,
            'identity_number' => $tenant->cccd,
            'phone' => $tenant->phone,
            'status' => ContractTenant::STATUS_CHECKED_IN,
            'actual_move_in_at' => '2026-01-01 09:00:00',
        ]);
        $this->temporaryResidence = TemporaryResidence::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'contract_tenant_id' => $this->member->id,
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

    public function test_update_uses_the_validity_period_shown_on_the_residence_document(): void
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
        )->assertRedirect(route('admin.temporary_residences.show', $this->temporaryResidence))
            ->assertSessionHasNoErrors();

        $fresh = $this->temporaryResidence->fresh();
        $this->assertSame('2030-01-01', $fresh->start_date->toDateString());
        $this->assertSame('2030-12-31', $fresh->end_date->toDateString());
        $this->assertSame('Ngày phải tiếp tục lấy từ hợp đồng.', $fresh->note);
    }

    public function test_landlord_can_upload_private_residence_evidence_for_a_checked_in_member(): void
    {
        $this->temporaryResidence->forceFill(['status' => 'expired'])->save();

        $this->actingAs($this->admin)->post(route('admin.temporary_residences.store'), [
            'contract_tenant_id' => $this->member->id,
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'reference_number' => 'TT-2026-0001',
            'evidence' => UploadedFile::fake()->image('giay-tam-tru.jpg'),
            'note' => 'Đã đối chiếu bản gốc.',
        ])->assertSessionHasNoErrors();

        $residence = TemporaryResidence::query()->latest('id')->firstOrFail();
        $this->assertSame($this->member->id, $residence->contract_tenant_id);
        $this->assertSame($this->admin->id, $residence->verified_by);
        $this->assertSame('active', $residence->status);
        $this->assertSame('TT-2026-0001', $residence->reference_number);
        $this->assertNotNull($residence->verified_at);
        Storage::disk('local')->assertExists($residence->evidence_path);

        $this->get(route('admin.temporary_residences.evidence', $residence))->assertOk();
        $this->get(route('admin.temporary_residences.index'))
            ->assertOk()
            ->assertSee('Giấy tạm trú của người thuê')
            ->assertSee('TT-2026-0001')
            ->assertSee('data-image-modal', false)
            ->assertSee('data-media-type="image"', false)
            ->assertDontSee('target="_blank"', false);
        $this->get(route('admin.temporary_residences.show', $residence))
            ->assertOk()
            ->assertSee('Đã đối chiếu bản gốc.')
            ->assertSee('Minh chứng giấy tạm trú')
            ->assertSee('data-media-type="image"', false)
            ->assertDontSee('target="_blank"', false);
        $this->get(route('admin.temporary_residences.create'))
            ->assertOk()
            ->assertSee('Tất cả người đang thuê đã có giấy tạm trú còn hiệu lực');
    }

    public function test_admin_residence_views_embed_pdf_and_report_a_missing_evidence_file(): void
    {
        Storage::disk('local')->put('temporary-residences/residence.pdf', 'pdf');
        $this->temporaryResidence->forceFill([
            'evidence_path' => 'temporary-residences/residence.pdf',
            'evidence_original_name' => 'giay-tam-tru.pdf',
            'evidence_mime_type' => 'application/pdf',
        ])->save();

        $this->actingAs($this->admin)
            ->get(route('admin.temporary_residences.show', $this->temporaryResidence))
            ->assertOk()
            ->assertSee('data-media-type="pdf"', false)
            ->assertDontSee('target="_blank"', false);

        Storage::disk('local')->delete('temporary-residences/residence.pdf');

        $this->get(route('admin.temporary_residences.show', $this->temporaryResidence))
            ->assertOk()
            ->assertSee('Tệp minh chứng không còn tồn tại trong hệ thống.');
    }

    public function test_residence_evidence_cannot_be_created_for_a_member_who_has_not_checked_in(): void
    {
        $this->temporaryResidence->forceFill(['status' => 'expired'])->save();
        $this->member->forceFill(['status' => ContractTenant::STATUS_APPROVED])->save();

        $this->actingAs($this->admin)->post(route('admin.temporary_residences.store'), [
            'contract_tenant_id' => $this->member->id,
            'start_date' => '2026-08-01',
            'evidence' => UploadedFile::fake()->image('invalid.jpg'),
        ])->assertSessionHasErrors('contract_tenant_id');

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_expired_residence_documents_are_closed_automatically(): void
    {
        $this->temporaryResidence->forceFill([
            'status' => 'active',
            'end_date' => today()->subDay(),
        ])->save();

        $this->artisan('temporary-residences:expire')->assertSuccessful();

        $this->assertSame('expired', $this->temporaryResidence->fresh()->status);
    }

    public function test_index_lists_every_checked_in_member_even_without_a_residence_document(): void
    {
        $tenant = Tenant::create([
            'full_name' => 'Người thuê chưa có giấy',
            'date_of_birth' => '1996-02-02',
            'gender' => 'female',
            'cccd' => '079000000100',
            'phone' => '0900000200',
        ]);
        ContractTenant::create([
            'contract_id' => $this->member->contract_id,
            'tenant_id' => $tenant->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => $tenant->full_name,
            'identity_number' => $tenant->cccd,
            'status' => ContractTenant::STATUS_CHECKED_IN,
            'actual_move_in_at' => now(),
        ]);

        $this->actingAs($this->admin)->get(route('admin.temporary_residences.index'))
            ->assertOk()
            ->assertSee('Nguyễn Văn Tạm Trú')
            ->assertSee('Người thuê chưa có giấy')
            ->assertSee('Chưa cập nhật')
            ->assertSee('Người đang thuê')
            ->assertSee('Chưa có/Cần cập nhật lại');
    }

    public function test_landlord_can_add_evidence_to_a_signed_legacy_residence_record(): void
    {
        $this->assertNotNull($this->temporaryResidence->signed_at);

        $this->actingAs($this->admin)->patch(
            route('admin.temporary_residences.evidence.update', $this->temporaryResidence),
            ['evidence' => UploadedFile::fake()->image('legacy-proof.jpg')]
        )->assertSessionHasNoErrors();

        $fresh = $this->temporaryResidence->fresh();
        $this->assertNotNull($fresh->signed_at);
        $this->assertSame($this->admin->id, $fresh->verified_by);
        Storage::disk('local')->assertExists($fresh->evidence_path);
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
