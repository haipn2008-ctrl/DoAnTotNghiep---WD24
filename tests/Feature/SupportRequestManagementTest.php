<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Role;
use App\Models\Room;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupportRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_client_can_submit_request_with_or_without_private_attachment(): void
    {
        [$client, $tenant, $contract] = $this->createClientContext('CREATE');

        $this->actingAs($client)->post('/client/support', [
            'submission_token' => (string) Str::uuid(),
            'category' => 'repair',
            'subject' => 'Vòi nước bị rò',
            'description' => 'Vui lòng kiểm tra vòi nước trong phòng.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($client)->post('/client/support', [
            'submission_token' => (string) Str::uuid(),
            'category' => 'utility',
            'subject' => 'Kiểm tra đồng hồ điện',
            'description' => 'Chỉ số điện có dấu hiệu bất thường.',
            'attachment' => UploadedFile::fake()->image('meter.jpg'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $requests = SupportRequest::orderBy('id')->get();
        $this->assertCount(2, $requests);
        $this->assertSame($client->id, $requests[0]->user_id);
        $this->assertSame($tenant->id, $requests[0]->tenant_id);
        $this->assertSame($contract->id, $requests[0]->contract_id);
        $this->assertSame(SupportRequest::STATUS_NEW, $requests[0]->status);
        $this->assertNull($requests[0]->attachment);
        Storage::disk('local')->assertExists($requests[1]->attachment);
        Storage::disk('public')->assertMissing($requests[1]->attachment);
    }

    public function test_submission_validation_rejects_forged_empty_oversized_and_non_image_data(): void
    {
        [$client] = $this->createClientContext('VALIDATE');

        $this->actingAs($client)->post('/client/support', [
            'submission_token' => (string) Str::uuid(),
            'category' => 'forged',
            'subject' => '',
            'description' => str_repeat('x', 5001),
            'attachment' => UploadedFile::fake()->create('huge.jpg', 5121, 'image/jpeg'),
        ])->assertSessionHasErrors(['category', 'subject', 'description', 'attachment']);

        $this->actingAs($client)->post('/client/support', [
            'submission_token' => (string) Str::uuid(),
            'category' => 'repair',
            'subject' => 'Tệp giả mạo',
            'description' => 'Nội dung hợp lệ nhưng tệp không phải ảnh.',
            'attachment' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('support_requests', 0);
        Storage::disk('local')->assertDirectoryEmpty('support-requests');
    }

    public function test_client_can_request_support_for_a_settling_contract(): void
    {
        [$client, , $contract] = $this->createClientContext('NOCONTRACT');
        $contract->forceFill(['status' => Contract::STATUS_SETTLING])->save();

        $this->actingAs($client)->post('/client/support', [
            'submission_token' => (string) Str::uuid(),
            'contract_id' => $contract->id,
            'category' => 'contract',
            'subject' => 'Không còn hợp đồng',
            'description' => 'Request trực tiếp sau khi hợp đồng kết thúc.',
            'attachment' => UploadedFile::fake()->image('orphan.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('support_requests', [
            'user_id' => $client->id,
            'contract_id' => $contract->id,
            'category' => 'contract',
        ]);
        $this->assertNotNull(SupportRequest::query()->sole()->attachment);
    }

    public function test_clients_only_list_and_download_their_own_requests_while_admin_can_download_all(): void
    {
        [$owner, $tenant, $contract] = $this->createClientContext('OWNER');
        [$other] = $this->createClientContext('OTHER');
        $path = UploadedFile::fake()->image('private.jpg')->store('support-requests', 'local');
        $supportRequest = $this->createSupportRequest($owner, $tenant, $contract, [
            'subject' => 'PRIVATE-SUPPORT-SUBJECT',
            'attachment' => $path,
        ]);
        $admin = $this->createAdmin();

        $this->actingAs($owner)->get('/client/support')
            ->assertSuccessful()->assertSee('PRIVATE-SUPPORT-SUBJECT');
        $this->actingAs($other)->get('/client/support')
            ->assertSuccessful()->assertDontSee('PRIVATE-SUPPORT-SUBJECT');
        $this->actingAs($owner)->get('/client/support/'.$supportRequest->id.'/attachment')->assertSuccessful();
        $this->actingAs($other)->get('/client/support/'.$supportRequest->id.'/attachment')->assertNotFound();
        $this->actingAs($admin)->get('/admin/support/'.$supportRequest->id.'/attachment')->assertSuccessful();
    }

    public function test_replaying_the_same_submission_token_does_not_create_duplicate_request(): void
    {
        [$client] = $this->createClientContext('REPLAY');
        $payload = [
            'submission_token' => (string) Str::uuid(),
            'category' => 'other',
            'subject' => 'Chỉ tạo một lần',
            'description' => 'Cùng request bị gửi lại do người dùng bấm hai lần.',
        ];

        $this->actingAs($client)->post('/client/support', $payload)
            ->assertSessionHasNoErrors();
        $this->actingAs($client)->post('/client/support', $payload)
            ->assertSessionHasErrors('submission_token');

        $this->assertDatabaseCount('support_requests', 1);
        $this->assertDatabaseHas('support_requests', [
            'submission_token' => $payload['submission_token'],
            'subject' => 'Chỉ tạo một lần',
        ]);
    }

    public function test_missing_attachment_or_request_returns_not_found(): void
    {
        [$client, $tenant, $contract] = $this->createClientContext('MISSING');
        $supportRequest = $this->createSupportRequest($client, $tenant, $contract, [
            'attachment' => 'support-requests/already-lost.jpg',
        ]);
        $admin = $this->createAdmin();

        $this->actingAs($client)->get('/client/support/'.$supportRequest->id.'/attachment')->assertNotFound();
        $this->actingAs($client)->get('/client/support/999999/attachment')->assertNotFound();
        $this->actingAs($admin)->get('/admin/support/'.$supportRequest->id.'/attachment')->assertNotFound();
        $this->actingAs($client)->get('/client/support')
            ->assertSuccessful()
            ->assertSee('Tệp đính kèm không còn tồn tại')
            ->assertDontSee('/client/support/'.$supportRequest->id.'/attachment');
        $this->actingAs($admin)->get('/admin/support')
            ->assertSuccessful()
            ->assertSee('Tệp đính kèm không còn tồn tại')
            ->assertDontSee('/admin/support/'.$supportRequest->id.'/attachment');

        $this->artisan('support:audit-attachments')
            ->expectsOutput('Phát hiện bản ghi hỗ trợ có tệp đính kèm bị thiếu. Dữ liệu đường dẫn được giữ nguyên để đối soát.')
            ->assertFailed();
        $this->assertSame('support-requests/already-lost.jpg', $supportRequest->fresh()->attachment);
    }

    public function test_admin_can_filter_and_move_request_to_in_progress(): void
    {
        [$client, $tenant, $contract] = $this->createClientContext('FILTER');
        $repair = $this->createSupportRequest($client, $tenant, $contract, ['subject' => 'REPAIR-ONLY']);
        $this->createSupportRequest($client, $tenant, $contract, [
            'category' => 'invoice',
            'subject' => 'INVOICE-ONLY',
        ]);
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get('/admin/support?category=repair&status=new')
            ->assertSuccessful()->assertSee('REPAIR-ONLY')->assertDontSee('INVOICE-ONLY');
        $this->actingAs($admin)->get('/admin/support?category=forged&status=forged')
            ->assertSessionHasErrors(['category', 'status']);

        $this->actingAs($admin)->put('/admin/support/'.$repair->id, [
            'status' => SupportRequest::STATUS_IN_PROGRESS,
            'admin_response' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $repair->refresh();
        $this->assertSame(SupportRequest::STATUS_IN_PROGRESS, $repair->status);
        $this->assertSame($admin->id, $repair->handled_by);
        $this->assertNull($repair->admin_response);
        $this->assertNull($repair->responded_at);
    }

    public function test_resolve_and_reject_require_response_and_store_review_metadata(): void
    {
        [$client, $tenant, $contract] = $this->createClientContext('TERMINAL');
        $resolved = $this->createSupportRequest($client, $tenant, $contract);
        $rejected = $this->createSupportRequest($client, $tenant, $contract, ['subject' => 'Từ chối']);
        $admin = $this->createAdmin();

        $this->actingAs($admin)->put('/admin/support/'.$resolved->id, [
            'status' => SupportRequest::STATUS_RESOLVED,
            'admin_response' => '',
        ])->assertSessionHasErrors('admin_response');
        $this->assertSame(SupportRequest::STATUS_NEW, $resolved->fresh()->status);

        foreach ([[$resolved, SupportRequest::STATUS_RESOLVED], [$rejected, SupportRequest::STATUS_REJECTED]] as [$item, $status]) {
            $this->actingAs($admin)->put('/admin/support/'.$item->id, [
                'status' => $status,
                'admin_response' => 'Đã kiểm tra và phản hồi khách thuê.',
            ])->assertSessionHasNoErrors();

            $item->refresh();
            $this->assertSame($status, $item->status);
            $this->assertSame($admin->id, $item->handled_by);
            $this->assertNotNull($item->responded_at);
        }
    }

    public function test_terminal_request_cannot_be_overwritten_by_repeated_or_stale_action(): void
    {
        [$client, $tenant, $contract] = $this->createClientContext('IMMUTABLE');
        $supportRequest = $this->createSupportRequest($client, $tenant, $contract);
        $firstAdmin = $this->createAdmin('first-support-admin@example.test');
        $secondAdmin = $this->createAdmin('second-support-admin@example.test');

        $this->actingAs($firstAdmin)->put('/admin/support/'.$supportRequest->id, [
            'status' => SupportRequest::STATUS_RESOLVED,
            'admin_response' => 'Phản hồi cuối cùng.',
        ])->assertSessionHasNoErrors();
        $respondedAt = $supportRequest->fresh()->responded_at;

        $this->actingAs($secondAdmin)->put('/admin/support/'.$supportRequest->id, [
            'status' => SupportRequest::STATUS_REJECTED,
            'admin_response' => 'Ghi đè trái phép.',
        ])->assertSessionHasErrors('status');

        $supportRequest->refresh();
        $this->assertSame(SupportRequest::STATUS_RESOLVED, $supportRequest->status);
        $this->assertSame('Phản hồi cuối cùng.', $supportRequest->admin_response);
        $this->assertSame($firstAdmin->id, $supportRequest->handled_by);
        $this->assertTrue($respondedAt->equalTo($supportRequest->responded_at));
    }

    public function test_support_endpoints_allow_settling_clients_and_still_enforce_authentication_and_role(): void
    {
        [$client, $tenant, $contract] = $this->createClientContext('AUTH');
        $supportRequest = $this->createSupportRequest($client, $tenant, $contract);
        $admin = $this->createAdmin();

        $this->get('/client/support')->assertRedirect('/login');
        $this->get('/admin/support')->assertRedirect('/login');
        $this->actingAs($client)->get('/admin/support')->assertForbidden();
        $this->actingAs($admin)->get('/client/support')->assertForbidden();

        $client->update(['status' => User::STATUS_SETTLING]);
        $contract->forceFill(['status' => Contract::STATUS_SETTLING])->save();
        $this->actingAs($client)->get('/client/support')->assertSuccessful();
        $this->actingAs($client)->post('/client/support', [
            'submission_token' => (string) Str::uuid(),
            'contract_id' => $contract->id,
            'category' => 'contract',
            'subject' => 'Hỗ trợ quyết toán',
            'description' => 'Cần kiểm tra số liệu sau khi trả phòng.',
        ])->assertSessionHasNoErrors();
        $this->actingAs($client)->get('/client/support/'.$supportRequest->id.'/attachment')
            ->assertStatus(404);
        $this->assertDatabaseCount('support_requests', 2);
    }

    private function createClientContext(string $suffix): array
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);
        $user = User::create([
            'name' => 'Khách '.$suffix,
            'email' => strtolower($suffix).'@support.test',
            'phone' => '0901'.str_pad((string) User::count(), 6, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'full_name' => 'Khách hỗ trợ '.$suffix,
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'cccd' => 'SUPPORT-'.$suffix,
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => 'Hà Nội',
        ]);
        $room = Room::create([
            'room_code' => 'SP-'.$suffix,
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-SP-'.$suffix,
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'deposit_amount' => 3000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => Contract::STATUS_ACTIVE,
        ]);

        return [$user, $tenant, $contract];
    }

    private function createAdmin(string $email = 'support-admin@example.test'): User
    {
        $role = Role::firstOrCreate(['role_name' => 'Admin']);

        return User::create([
            'name' => 'Admin hỗ trợ',
            'email' => $email,
            'phone' => '0981'.str_pad((string) User::count(), 6, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createSupportRequest(
        User $user,
        Tenant $tenant,
        Contract $contract,
        array $attributes = []
    ): SupportRequest {
        return SupportRequest::create(array_merge([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'category' => 'repair',
            'subject' => 'Yêu cầu hỗ trợ',
            'description' => 'Nội dung cần hỗ trợ.',
            'status' => SupportRequest::STATUS_NEW,
        ], $attributes));
    }
}
