<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\SupportRequest;
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
            ->assertViewHas('recentInvoice', fn ($value) => $value->contract_id === $contract->id)
            ->assertViewHas('openInvoices', fn ($value) => $value->count() === 5
                && $value->every(fn ($invoice) => $invoice->contract_id === $contract->id))
            ->assertViewHas('supportRequests', 1)
            ->assertDontSee('OTHER-PRIVATE');
    }

    public function test_settling_account_keeps_history_invoice_and_account_access_without_dead_navigation(): void
    {
        [$client] = $this->createClientContext('SETTLING');
        $client->update(['status' => User::STATUS_SETTLING]);

        $this->actingAs($client)->get('/client')->assertSuccessful()
            ->assertDontSee('href="'.route('client.room.show').'"', false)
            ->assertDontSee('href="'.route('client.utilities.index').'"', false)
            ->assertDontSee('href="'.route('client.support.index').'"', false)
            ->assertSee('Xem hóa đơn quyết toán');
        $this->actingAs($client)->get('/client/contracts')->assertSuccessful();
        $this->actingAs($client)->get('/client/invoices')->assertSuccessful();
        $this->actingAs($client)->get('/client/account')->assertSuccessful();
        $this->actingAs($client)->get('/client/room')->assertRedirect('/client/invoices');
        $this->actingAs($client)->get('/client/utilities')->assertRedirect('/client/invoices');
        $this->actingAs($client)->get('/client/support')->assertRedirect('/client/invoices');
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
