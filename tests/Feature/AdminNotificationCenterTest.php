<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractExtensionRequest;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    private Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $this->admin = $this->user($adminRole, 'notification-admin@example.test');
        $this->client = $this->user($clientRole, 'notification-client@example.test');
        $tenant = Tenant::create([
            'user_id' => $this->client->id,
            'full_name' => 'Khách nhận thông báo',
            'cccd' => '079000009999',
            'phone' => '0900009999',
        ]);
        $room = Room::create([
            'room_code' => 'NOTIFY-01', 'floor' => 1, 'price' => 3000000,
            'area' => 25, 'max_people' => 3, 'status' => Room::STATUS_OCCUPIED,
        ]);
        $this->contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-NOTIFY-01', 'room_id' => $room->id, 'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000, 'deposit_amount' => 0, 'number_of_people' => 1,
            'start_date' => today()->subYear(), 'end_date' => today()->subDay(),
            'status' => Contract::STATUS_EXPIRED,
        ]);
    }

    public function test_bell_shows_number_and_latest_unresolved_notifications_in_admin_header(): void
    {
        $this->alert('contract_expired', 'Hợp đồng cần trả phòng');
        $this->alert('deposit_exception', 'Cần đối soát tiền cọc');
        $this->alert('move_in_overdue', 'Thông báo đã xử lý', now());

        $this->actingAs($this->admin)->get('/admin')
            ->assertOk()
            ->assertSee('admin-notification-button', false)
            ->assertSee('2 việc đang cần xử lý')
            ->assertSee('Hợp đồng cần trả phòng')
            ->assertSee('Cần đối soát tiền cọc')
            ->assertDontSee('Thông báo đã xử lý');
    }

    public function test_notification_center_filters_open_resolved_and_all_notifications(): void
    {
        $this->alert('contract_expired', 'Việc chưa xử lý');
        $this->alert('move_in_overdue', 'Việc đã xử lý', now());

        $this->actingAs($this->admin)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Việc chưa xử lý')
            ->assertSee('Cần xử lý (1)')
            ->assertSee('Đã xử lý (1)')
            ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 1
                && $notifications->first()->title === 'Việc chưa xử lý');

        $this->get(route('admin.notifications.index', ['status' => 'resolved']))
            ->assertOk()
            ->assertSee('Việc đã xử lý')
            ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 1
                && $notifications->first()->title === 'Việc đã xử lý');

        $this->get(route('admin.notifications.index', ['status' => 'all']))
            ->assertOk()
            ->assertSee('Việc đã xử lý')
            ->assertSee('Việc chưa xử lý')
            ->assertSee('HD-NOTIFY-01')
            ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 2);
    }

    public function test_client_cannot_access_admin_notification_center(): void
    {
        $this->actingAs($this->client)->get(route('admin.notifications.index'))->assertForbidden();
    }

    public function test_user_request_notification_opens_correct_queue_without_being_resolved(): void
    {
        $extensionRequest = ContractExtensionRequest::create([
            'contract_id' => $this->contract->id,
            'current_end_date' => today()->subDay(),
            'requested_end_date' => today()->addMonth(),
            'reason' => 'Muốn tiếp tục thuê phòng',
            'status' => ContractExtensionRequest::STATUS_PENDING,
        ]);

        $notification = app(AdminNotificationService::class)->extensionRequested($extensionRequest);

        $this->assertNull($notification->resolved_at);
        $this->assertSame('extension_request', $notification->type);
        $this->actingAs($this->admin)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Khách thuê vừa yêu cầu gia hạn hợp đồng')
            ->assertSee('Muốn tiếp tục thuê phòng');

        $this->get(route('admin.notifications.open', $notification))
            ->assertRedirect(route('admin.extension-requests.index').'#request-'.$extensionRequest->id);

        // Việc chỉ mở xem không được coi là đã xử lý.
        $this->assertNull($notification->fresh()->resolved_at);

        app(AdminNotificationService::class)->resolve('extension_request', $extensionRequest);
        $this->assertNotNull($notification->fresh()->resolved_at);
    }

    private function alert(string $type, string $title, $resolvedAt = null): ContractLifecycleAlert
    {
        return ContractLifecycleAlert::create([
            'contract_id' => $this->contract->id,
            'type' => $type,
            'dedupe_key' => $type.':current',
            'title' => $title,
            'message' => 'Admin mở hợp đồng để thực hiện hành động phù hợp.',
            'detected_at' => now(),
            'resolved_at' => $resolvedAt,
        ]);
    }

    private function user(Role $role, string $email): User
    {
        return User::create([
            'name' => 'Notification user', 'email' => $email, 'role_id' => $role->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
    }
}
