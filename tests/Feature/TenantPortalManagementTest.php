<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SupportRequest;
use App\Models\TemporaryResidence;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantPortalManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_dashboard_shows_only_owned_recent_and_open_data_with_correct_counts(): void
    {
        [$client, $tenant, $contract, $room] = $this->createClientContext('DASH');
        [, , $otherContract, $otherRoom] = $this->createClientContext('DASHOTHER');
        $this->createInvoice($contract, $room, 'OWN-PAID', 6, Invoice::STATUS_PAID);
        foreach (range(1, 7) as $month) {
            $this->createInvoice($contract, $room, 'OWN-OPEN-'.$month, $month, Invoice::STATUS_UNPAID, 2025);
        }
        $this->createInvoice($otherContract, $otherRoom, 'OTHER-PRIVATE', 8, Invoice::STATUS_UNPAID);
        SupportRequest::create([
            'user_id' => $client->id,
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'category' => 'repair',
            'subject' => 'Đang xử lý',
            'description' => 'Yêu cầu của khách.',
            'status' => SupportRequest::STATUS_IN_PROGRESS,
        ]);

        $this->actingAs($client)->get('/client')
            ->assertSuccessful()
            ->assertViewHas('activeContract', fn ($value) => $value->is($contract))
            ->assertViewHas('activeContracts', fn ($value) => $value->count() === 1 && $value->first()->is($contract))
            ->assertViewHas('recentInvoice', fn ($value) => $value->contract_id === $contract->id)
            ->assertViewHas('openInvoices', fn ($value) => $value->count() === 5
                && $value->every(fn ($invoice) => $invoice->contract_id === $contract->id))
            ->assertViewHas('supportRequests', 1)
            ->assertDontSee('OTHER-PRIVATE');
    }

    public function test_my_rooms_lists_every_active_room_and_selects_the_requested_contract(): void
    {
        [$client, $tenant, $firstContract, $firstRoom] = $this->createClientContext('MULTIROOM');
        $secondRoom = $this->createRoom('PORTAL-MULTIROOM-02');
        $secondContract = $this->createContract(
            $tenant,
            $secondRoom,
            'HD-PORTAL-MULTIROOM-02',
            Contract::STATUS_ACTIVE,
        );
        [, , $otherContract] = $this->createClientContext('MULTIROOMOTHER');

        $this->actingAs($client)->get(route('client.room.show'))
            ->assertSuccessful()
            ->assertViewHas('contracts', fn ($contracts) => $contracts->count() === 2
                && $contracts->pluck('id')->contains($firstContract->id)
                && $contracts->pluck('id')->contains($secondContract->id))
            ->assertSee('2 phòng đang thuê')
            ->assertSee($firstRoom->room_code)
            ->assertSee($secondRoom->room_code)
            ->assertSee(route('client.room.members.index', ['contract' => $firstContract->id]), false)
            ->assertSee(route('client.room.members.index', ['contract' => $secondContract->id]), false)
            ->assertDontSee($otherContract->room->room_code);

        $this->get(route('client.room.members.index', ['contract' => $secondContract->id]))
            ->assertSuccessful()
            ->assertViewHas('contract', fn ($contract) => $contract->is($secondContract))
            ->assertSee($secondRoom->room_code);
        $this->get(route('client.room.members.index', ['contract' => $otherContract->id]))
            ->assertNotFound();

        $this->get('/client')
            ->assertSuccessful()
            ->assertSee('2 phòng')
            ->assertSee($firstRoom->room_code)
            ->assertSee($secondRoom->room_code);
    }

    public function test_settling_account_keeps_history_invoice_and_account_access_without_dead_navigation(): void
    {
        [$client, , $contract] = $this->createClientContext('SETTLING');
        $contract->forceFill([
            'status' => Contract::STATUS_SETTLING,
            'actual_move_out_at' => now(),
        ])->save();
        $client->update(['status' => User::STATUS_SETTLING]);

        $this->actingAs($client)->get('/client')->assertRedirect('/client/settlement');
        $this->actingAs($client)->get('/client/settlement')->assertSuccessful()
            ->assertDontSee('href="'.route('client.room.show').'"', false)
            ->assertDontSee('href="'.route('client.utilities.index').'"', false)
            ->assertSee('href="'.route('client.support.index').'"', false)
            ->assertDontSee('href="'.route('client.vehicles.index').'"', false)
            ->assertDontSee('href="'.route('client.extension-requests.index').'"', false)
            ->assertDontSee('href="'.route('client.termination-requests.index').'"', false)
            ->assertSee('href="'.route('client.notifications.index').'"', false)
            ->assertSee('href="'.route('client.account.edit').'"', false)
            ->assertSee('href="'.route('client.settlement.index').'"', false)
            ->assertSee('Quyết toán & hoàn cọc', false)
            ->assertSee('Tài khoản đang được duy trì để hoàn tất quyết toán.');
        $this->actingAs($client)->get('/client/contracts')->assertSuccessful();
        $this->actingAs($client)->get('/client/invoices')->assertSuccessful();
        $this->actingAs($client)->get('/client/account')->assertSuccessful();
        $this->actingAs($client)->get('/client/room')->assertRedirect('/client/invoices');
        $this->actingAs($client)->get('/client/utilities')->assertRedirect('/client/invoices');
        $this->actingAs($client)->get('/client/support')->assertSuccessful();
    }

    public function test_client_can_view_only_their_own_temporary_residence_evidence_from_account(): void
    {
        [$client, $tenant, $contract] = $this->createClientContext('RESIDENCE');
        [, $otherTenant, $otherContract] = $this->createClientContext('OTHERRESIDENCE');

        $member = ContractTenant::create([
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
        $otherMember = ContractTenant::create([
            'contract_id' => $otherContract->id,
            'tenant_id' => $otherTenant->id,
            'role' => ContractTenant::ROLE_REPRESENTATIVE,
            'full_name' => $otherTenant->full_name,
            'date_of_birth' => $otherTenant->date_of_birth,
            'identity_number' => $otherTenant->cccd,
            'phone' => $otherTenant->phone,
            'status' => ContractTenant::STATUS_CHECKED_IN,
            'actual_move_in_at' => '2026-01-01 09:00:00',
        ]);

        Storage::disk('local')->put('temporary-residences/own.pdf', 'own residence');
        Storage::disk('local')->put('temporary-residences/own.jpg', 'own residence image');
        Storage::disk('local')->put('temporary-residences/other.pdf', 'other residence');

        $residence = TemporaryResidence::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'contract_tenant_id' => $member->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'reference_number' => 'TT-OWN-001',
            'status' => 'active',
            'evidence_path' => 'temporary-residences/own.pdf',
            'evidence_original_name' => 'giay-tam-tru.pdf',
            'evidence_mime_type' => 'application/pdf',
        ]);
        $otherResidence = TemporaryResidence::create([
            'tenant_id' => $otherTenant->id,
            'contract_id' => $otherContract->id,
            'contract_tenant_id' => $otherMember->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
            'evidence_path' => 'temporary-residences/other.pdf',
            'evidence_original_name' => 'private.pdf',
            'evidence_mime_type' => 'application/pdf',
        ]);
        $imageResidence = TemporaryResidence::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'contract_tenant_id' => $member->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'reference_number' => 'TT-IMAGE-001',
            'status' => 'expired',
            'evidence_path' => 'temporary-residences/own.jpg',
            'evidence_original_name' => 'giay-tam-tru.jpg',
            'evidence_mime_type' => 'image/jpeg',
        ]);

        $this->actingAs($client)->get(route('client.account.edit'))
            ->assertSuccessful()
            ->assertSee('Giấy tạm trú của tôi')
            ->assertSee('TT-OWN-001')
            ->assertSee('data-media-type="pdf"', false)
            ->assertSee('data-media-type="image"', false)
            ->assertSee('data-image-modal-document', false)
            ->assertDontSee('target="_blank"', false)
            ->assertSee(route('client.account.temporary-residences.evidence', $residence))
            ->assertDontSee('private.pdf');

        $this->get(route('client.account.temporary-residences.evidence', $residence))
            ->assertSuccessful()
            ->assertHeader('content-type', 'application/pdf');
        $this->get(route('client.account.temporary-residences.evidence', $imageResidence))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/jpeg');
        $this->get(route('client.account.temporary-residences.evidence', $otherResidence))
            ->assertNotFound();

        $this->get(route('client.room.members.index'))
            ->assertSuccessful()
            ->assertSee('Bạn');
        $this->get(route('client.room.members.show', $member))
            ->assertSuccessful()
            ->assertSee('Giấy tạm trú')
            ->assertSee('TT-OWN-001')
            ->assertSee('data-media-type="pdf"', false)
            ->assertSee('data-media-type="image"', false)
            ->assertDontSee('target="_blank"', false)
            ->assertSee(route('client.room.members.temporary-residences.evidence', [$member, $residence]));
        $this->get(route('client.room.members.temporary-residences.evidence', [$member, $residence]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'application/pdf');
        $this->get(route('client.room.members.temporary-residences.evidence', [$member, $otherResidence]))
            ->assertNotFound();
    }

    public function test_support_menu_links_to_requests_and_public_landlord_information(): void
    {
        [$client] = $this->createClientContext('LANDLORDINFO');
        Setting::currentOrCreate()->update([
            'property_name' => 'Nhà trọ An Tâm',
            'property_address' => '12 Nguyễn Trãi',
            'landlord_name' => 'Nguyễn Chủ Trọ',
            'landlord_phone' => '0901 234 567',
            'landlord_address' => 'Quận 1, TP.HCM',
            'landlord_identity_number' => '001080000001',
        ]);

        $this->actingAs($client)->get('/client')
            ->assertSuccessful()
            ->assertSee('Gửi yêu cầu hỗ trợ')
            ->assertSee('href="'.route('client.support.index').'"', false)
            ->assertSee('Thông tin chủ trọ')
            ->assertSee('href="'.route('client.landlord-information').'"', false);

        $this->get(route('client.landlord-information'))
            ->assertSuccessful()
            ->assertSee('Nguyễn Chủ Trọ')
            ->assertSee('0901 234 567')
            ->assertSee('href="tel:0901234567"', false)
            ->assertSee('Nhà trọ An Tâm')
            ->assertDontSee('001080000001');
    }

    public function test_contract_history_and_private_file_are_visible_only_to_owner(): void
    {
        [$owner, $tenant, $activeContract] = $this->createClientContext('CONTRACTOWNER');
        [$other] = $this->createClientContext('CONTRACTOTHER');
        $historyRoom = $this->createRoom('HISTORY-ROOM');
        $history = $this->createContract($tenant, $historyRoom, 'HISTORY-CONTRACT', Contract::STATUS_TERMINATED);
        $history->update(['contract_file' => 'contracts/history.pdf']);
        Storage::disk('local')->put('contracts/history.pdf', 'private-contract');

        $this->actingAs($owner)->get('/client/contracts')
            ->assertSuccessful()->assertSee($activeContract->contract_code)->assertSee('HISTORY-CONTRACT');
        $this->actingAs($owner)->get('/client/contracts/'.$history->id)
            ->assertSuccessful()->assertSee('HISTORY-CONTRACT');
        $this->actingAs($owner)->get('/client/contracts/'.$history->id.'/file')->assertSuccessful();
        $this->actingAs($other)->get('/client/contracts/'.$history->id)->assertNotFound();
        $this->actingAs($other)->get('/client/contracts/'.$history->id.'/file')->assertNotFound();
    }

    public function test_room_and_utility_data_are_scoped_to_contract_room_and_period(): void
    {
        [$client, , $contract, $room] = $this->createClientContext('UTILITYOWNER');
        [, , $otherContract, $otherRoom] = $this->createClientContext('UTILITYOTHER');
        $contract->update(['start_date' => '2026-03-15', 'end_date' => '2026-08-20']);
        $inside = $this->createReading($room, 3, 2026, 120, 'utility-readings/electricity/inside.jpg');
        $this->createReading($room, 2, 2026, 999);
        $this->createReading($room, 9, 2026, 888);
        $other = $this->createReading($otherRoom, 5, 2026, 777, 'utility-readings/electricity/other.jpg');
        Storage::disk('local')->put($inside->electricity_image, 'inside-image');
        Storage::disk('local')->put($other->electricity_image, 'other-image');

        $this->actingAs($client)->get('/client/room')
            ->assertSuccessful()->assertSee($room->room_code)->assertDontSee($otherRoom->room_code);
        $this->actingAs($client)->get('/client/utilities')
            ->assertSuccessful()->assertSeeText('120 kWh')->assertDontSeeText('999 kWh')
            ->assertDontSeeText('888 kWh')->assertDontSeeText('777 kWh');
        $this->actingAs($client)->get('/client/utilities/'.$inside->id.'/electricity-image')->assertSuccessful();
        $this->actingAs($client)->get('/client/utilities/'.$other->id.'/electricity-image')->assertNotFound();
        $this->actingAs($client)->get('/client/utilities/'.$inside->id.'/gas-image')->assertNotFound();
        $this->actingAs($client)->get('/client/utilities?year=1999')->assertSessionHasErrors('year');
    }

    public function test_room_baseline_is_not_exposed_as_a_tenant_period_reading(): void
    {
        [$client, , $contract, $room] = $this->createClientContext('BASELINEHIDDEN');
        $contract->update(['start_date' => '2026-08-25', 'end_date' => '2027-08-24']);

        $baseline = UtilityReading::create([
            'room_id' => $room->id,
            'month' => 8,
            'year' => 2026,
            'reading_type' => 'baseline',
            'record_date' => '2026-08-25',
            'electricity_old' => 100,
            'electricity_new' => 100,
            'water_old' => 10,
            'water_new' => 10,
            'status' => UtilityReading::STATUS_CONFIRMED,
        ]);
        $handover = UtilityReading::create([
            'room_id' => $room->id,
            'contract_id' => $contract->id,
            'month' => 8,
            'year' => 2026,
            'reading_type' => 'handover',
            'record_date' => '2026-08-25',
            'electricity_old' => 100,
            'electricity_new' => 100,
            'water_old' => 10,
            'water_new' => 10,
            'status' => UtilityReading::STATUS_CONFIRMED,
        ]);

        $this->actingAs($client)->get('/client/utilities')
            ->assertSuccessful()
            ->assertViewHas('readings', function ($readings) use ($handover) {
                return $readings->getCollection()->pluck('id')->all() === [$handover->id];
            })
            ->assertSeeText('Chỉ số bàn giao')
            ->assertDontSeeText('Kỳ tháng 8/2026');

        $this->actingAs($client)
            ->get('/client/utilities/'.$baseline->id.'/electricity-image')
            ->assertNotFound();
    }

    public function test_client_sees_interim_reading_as_a_reconciliation_checkpoint_without_invoice_prompt(): void
    {
        [$client, , $contract, $room] = $this->createClientContext('CHECKPOINT');
        $checkpoint = UtilityReading::create([
            'room_id' => $room->id,
            'contract_id' => $contract->id,
            'month' => 8,
            'year' => 2026,
            'reading_type' => 'interim',
            'record_date' => '2026-08-16',
            'electricity_old' => 120,
            'electricity_new' => 145,
            'water_old' => 20,
            'water_new' => 27,
            'status' => UtilityReading::STATUS_CONFIRMED,
        ]);

        $this->actingAs($client)
            ->get(route('client.utilities.index'))
            ->assertOk()
            ->assertViewHas('readings', fn ($readings) => $readings->getCollection()->contains('id', $checkpoint->id))
            ->assertSee('Mốc giữa kỳ')
            ->assertSee('Mốc đối chiếu')
            ->assertSee('Chốt ngày 16/08/2026')
            ->assertSee('Chỉ số 120 → 145')
            ->assertSee('Chỉ số 20 → 27')
            ->assertSee('không tự chia tiền')
            ->assertDontSee('Chưa có hóa đơn');
    }

    public function test_profile_update_rejects_duplicate_tenant_contact_and_changes_nothing(): void
    {
        [$client, $tenant] = $this->createClientContext('PROFILE');
        [$other, $otherTenant] = $this->createClientContext('PROFILEOTHER');

        $this->actingAs($client)->put('/client/account', [
            'name' => 'Tên bị từ chối',
            'email' => $otherTenant->email,
            'phone' => $otherTenant->phone,
        ])->assertSessionHasErrors(['email', 'phone']);

        $this->assertNotSame('Tên bị từ chối', $client->fresh()->name);
        $this->assertSame($tenant->email, $client->email);
        $this->assertSame($tenant->phone, $client->phone);
        $this->assertSame($other->email, $otherTenant->email);
    }

    public function test_profile_update_is_atomic_and_available_to_settling_account(): void
    {
        [$client, $tenant] = $this->createClientContext('PROFILEOK');
        $client->update(['status' => User::STATUS_SETTLING]);

        $this->actingAs($client)->put('/client/account', [
            'name' => 'Tên hiển thị mới',
            'date_of_birth' => '1998-05-20',
            'gender' => 'female',
            'cccd' => '079098001234',
            'cccd_issue_date' => '2020-06-15',
            'cccd_issue_place' => 'Cuc Canh sat quan ly hanh chinh ve trat tu xa hoi',
            'email' => 'profile-new@example.test',
            'phone' => '0912345678',
            'address' => '123 Nguyen Hue, Quan 1, TP.HCM',
        ])->assertSessionHasNoErrors();

        $client->refresh();
        $tenant->refresh();
        $this->assertSame('Tên hiển thị mới', $client->name);
        $this->assertSame('profile-new@example.test', $client->email);
        $this->assertSame('profile-new@example.test', $tenant->email);
        $this->assertSame('0912345678', $tenant->phone);
        $this->assertSame('079098001234', $tenant->cccd);
        $this->assertSame('female', $tenant->gender);
    }

    public function test_password_change_rejects_wrong_weak_same_and_unconfirmed_passwords_then_accepts_strong_one(): void
    {
        [$client] = $this->createClientContext('PASSWORD');
        $originalHash = $client->password;
        $invalidPayloads = [
            ['current_password' => 'wrong', 'password' => 'New-password@123', 'password_confirmation' => 'New-password@123'],
            ['current_password' => 'Password@123', 'password' => 'weakpass', 'password_confirmation' => 'weakpass'],
            ['current_password' => 'Password@123', 'password' => 'Password@123', 'password_confirmation' => 'Password@123'],
            ['current_password' => 'Password@123', 'password' => 'New-password@123', 'password_confirmation' => 'Different@123'],
        ];

        foreach ($invalidPayloads as $payload) {
            $this->actingAs($client)->put('/client/account/password', $payload)->assertSessionHasErrors();
            $this->assertSame($originalHash, $client->fresh()->password);
        }

        $this->actingAs($client)->put('/client/account/password', [
            'current_password' => 'Password@123',
            'password' => 'New-password@123',
            'password_confirmation' => 'New-password@123',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('New-password@123', $client->fresh()->password));
    }

    public function test_missing_private_documents_show_safe_warning_and_are_auditable_without_losing_paths(): void
    {
        [$client, , $contract, $room] = $this->createClientContext('MISSINGFILES');
        $contract->update(['contract_file' => 'contracts/missing.pdf']);
        $reading = $this->createReading($room, 7, 2026, 10, 'utility-readings/electricity/missing.jpg');

        $this->actingAs($client)->get('/client/contracts/'.$contract->id)
            ->assertSuccessful()->assertSee('File hợp đồng không còn tồn tại')
            ->assertDontSee(route('client.contracts.file', $contract));
        $this->actingAs($client)->get('/client/utilities')
            ->assertSuccessful()->assertSee('Ảnh không còn tồn tại')
            ->assertDontSee(route('client.utilities.image', [$reading, 'electricity']));
        $this->actingAs($client)->get('/client/contracts/'.$contract->id.'/file')->assertNotFound();
        $this->actingAs($client)->get('/client/utilities/'.$reading->id.'/electricity-image')->assertNotFound();

        $this->artisan('portal:audit-private-files')
            ->expectsOutput('Phát hiện tài liệu riêng tư của cổng khách thuê bị thiếu. Đường dẫn DB được giữ nguyên để đối soát.')
            ->assertFailed();
        $this->assertSame('contracts/missing.pdf', $contract->fresh()->contract_file);
        $this->assertSame('utility-readings/electricity/missing.jpg', $reading->fresh()->electricity_image);
    }

    private function createClientContext(string $suffix): array
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);
        $user = User::create([
            'name' => 'Khách '.$suffix,
            'email' => strtolower($suffix).'@portal.test',
            'phone' => '095'.str_pad((string) User::count(), 7, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'password' => 'Password@123',
            'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'full_name' => 'Khách portal '.$suffix,
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'cccd' => 'PORTAL-'.$suffix,
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => 'Hà Nội',
        ]);
        $room = $this->createRoom('PORTAL-'.$suffix);
        $contract = $this->createContract($tenant, $room, 'HD-PORTAL-'.$suffix, Contract::STATUS_ACTIVE);

        return [$user, $tenant, $contract, $room];
    }

    private function createRoom(string $code): Room
    {
        return Room::create([
            'room_code' => $code,
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'status' => Room::STATUS_OCCUPIED,
        ]);
    }

    private function createContract(Tenant $tenant, Room $room, string $code, string $status): Contract
    {
        return Contract::query()->forceCreate([
            'contract_code' => $code,
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'deposit_amount' => 3000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => $status,
        ]);
    }

    private function createInvoice(
        Contract $contract,
        Room $room,
        string $code,
        int $month,
        string $status,
        int $year = 2026
    ): Invoice {
        return Invoice::create([
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'invoice_code' => $code,
            'month' => $month,
            'year' => $year,
            'invoice_date' => sprintf('%04d-%02d-01', $year, $month),
            'due_date' => sprintf('%04d-%02d-10', $year, $month),
            'room_fee' => 100000,
            'electricity_fee' => 0,
            'water_fee' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 100000,
            'status' => $status,
        ]);
    }

    private function createReading(
        Room $room,
        int $month,
        int $year,
        int $usage,
        ?string $electricityImage = null
    ): UtilityReading {
        return UtilityReading::create([
            'room_id' => $room->id,
            'month' => $month,
            'year' => $year,
            'record_date' => sprintf('%04d-%02d-28', $year, $month),
            'electricity_old' => 0,
            'electricity_new' => $usage,
            'electricity_image' => $electricityImage,
            'water_old' => 0,
            'water_new' => $usage,
            'status' => 'confirmed',
        ]);
    }
}
