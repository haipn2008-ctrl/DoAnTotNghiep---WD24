<?php

namespace Tests\Feature;

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

class ClientRoomMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_sidebar_menu_contains_collapsible_room_member_and_vehicle_links(): void
    {
        [$client] = $this->rentalFixture('VEHICLE-LINK');

        $roomPage = $this->actingAs($client)->get(route('client.room.show'));

        $roomPage->assertOk()
            ->assertSee('id="roomMenuButton"', false)
            ->assertSee('aria-controls="roomSubmenu"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('Phòng')
            ->assertSee('Thành viên')
            ->assertSee('Phương tiện')
            ->assertSee('href="'.route('client.room.show').'"', false)
            ->assertSee('href="'.route('client.room.members.index').'"', false)
            ->assertSee('href="'.route('client.vehicles.index').'"', false);
    }

    public function test_room_page_shows_only_current_room_evidence_with_server_time(): void
    {
        Storage::fake('public');
        [$client, $contract] = $this->rentalFixture('ROOM-EVIDENCE');
        $otherRoom = Room::create([
            'room_code' => 'ROOM-OTHER-EVIDENCE',
            'floor' => 2,
            'price' => 2500000,
            'area' => 20,
            'max_people' => 2,
            'current_people' => 0,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        $image = RoomImage::create([
            'room_id' => $contract->room_id,
            'uploaded_by' => $client->id,
            'evidence_type' => RoomImage::TYPE_BASELINE,
            'disk' => 'public',
            'path' => 'room-evidence/current-room.jpg',
            'caption' => 'Ảnh tài sản trước khi bàn giao.',
            'taken_at' => '2026-08-28 14:43:00',
        ]);
        RoomImage::create([
            'room_id' => $otherRoom->id,
            'uploaded_by' => $client->id,
            'evidence_type' => RoomImage::TYPE_BASELINE,
            'disk' => 'public',
            'path' => 'room-evidence/other-room.jpg',
            'caption' => 'Ảnh của phòng khác không được hiển thị.',
            'taken_at' => '2026-08-28 15:00:00',
        ]);
        Storage::disk('public')->put($image->path, 'current-room-image');

        $this->actingAs($client)
            ->get(route('client.room.show'))
            ->assertOk()
            ->assertSee('Ảnh tài sản và hiện trạng phòng')
            ->assertSee('Ảnh tài sản trước khi bàn giao.')
            ->assertSee('28/08/2026 14:43')
            ->assertSee('data-image-modal', false)
            ->assertDontSee('Ảnh của phòng khác không được hiển thị.');
    }

    public function test_client_can_open_details_of_checked_in_members_in_their_room(): void
    {
        Storage::fake('local');
        [$client, $contract, $representative] = $this->rentalFixture('A');
        $memberTenant = Tenant::create([
            'full_name' => 'Nguyễn Văn Thành Viên',
            'date_of_birth' => '1998-05-12',
            'gender' => 'male',
            'cccd' => '079123456789',
            'cccd_issue_date' => '2020-06-15',
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'phone' => '0912345678',
            'email' => 'thanhvien@example.com',
            'address' => '12 Nguyễn Trãi, Quận 1',
        ]);
        $member = ContractTenant::create([
            'contract_id' => $contract->id,
            'tenant_id' => $memberTenant->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => $memberTenant->full_name,
            'date_of_birth' => $memberTenant->date_of_birth,
            'identity_number' => $memberTenant->cccd,
            'phone' => $memberTenant->phone,
            'relationship' => 'Bạn cùng phòng',
            'address' => $memberTenant->address,
            'status' => ContractTenant::STATUS_CHECKED_IN,
            'actual_move_in_at' => '2026-08-01 09:00:00',
            'identity_front_path' => 'contract-identities/front/member.jpg',
            'identity_back_path' => 'contract-identities/back/member.jpg',
        ]);
        Storage::disk('local')->put($member->identity_front_path, 'front-image');
        Storage::disk('local')->put($member->identity_back_path, 'back-image');
        $pending = ContractTenant::create([
            'contract_id' => $contract->id,
            'tenant_id' => Tenant::create([
                'full_name' => 'Người đang chờ',
                'cccd' => '079123456790',
                'phone' => '0912345679',
            ])->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => 'Người đang chờ',
            'status' => ContractTenant::STATUS_PENDING,
        ]);

        $this->actingAs($client)
            ->get(route('client.room.members.index'))
            ->assertOk()
            ->assertSee($representative->full_name)
            ->assertSee($member->full_name)
            ->assertSee(route('client.room.members.show', $member))
            ->assertDontSee($pending->full_name)
            ->assertDontSee('Tài khoản của tôi');

        $this->get(route('client.room.show'))
            ->assertOk()
            ->assertDontSee($representative->full_name)
            ->assertDontSee($member->full_name);

        $this->get(route('client.room.members.show', $member))
            ->assertOk()
            ->assertSee('Nguyễn Văn Thành Viên')
            ->assertSee('1998-05-12')
            ->assertSee('079123456789')
            ->assertSee('0912345678')
            ->assertSee('Mặt trước CCCD')
            ->assertDontSee('Email đăng nhập')
            ->assertDontSee('thanhvien@example.com');

        $this->get(route('client.room.members.show', $representative))
            ->assertOk()
            ->assertSee('Email đăng nhập')
            ->assertSee('a@room-member.test');

        $this->get(route('client.room.members.identity', [$member, 'front']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        $this->put(route('client.room.members.update', $member), [
            'full_name' => 'Nguyễn Văn Đã Sửa',
            'date_of_birth' => '1998-05-12',
            'gender' => 'male',
            'identity_number' => '079123456789',
            'cccd_issue_date' => '2020-06-15',
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'phone' => '0912345688',
            'email' => 'khong-duoc-cap-nhat@example.com',
            'address' => '99 Nguyễn Trãi, Quận 1',
            'identity_front' => UploadedFile::fake()->image('front-new.jpg'),
            'identity_back' => UploadedFile::fake()->image('back-new.jpg'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contract_tenants', [
            'id' => $member->id,
            'full_name' => 'Nguyễn Văn Đã Sửa',
            'phone' => '0912345688',
            'address' => '99 Nguyễn Trãi, Quận 1',
        ]);
        $this->assertDatabaseHas('tenants', [
            'id' => $memberTenant->id,
            'full_name' => 'Nguyễn Văn Đã Sửa',
            'email' => 'thanhvien@example.com',
        ]);
        $this->assertNotSame('contract-identities/front/member.jpg', $member->fresh()->identity_front_path);

        $representativeTenant = $representative->tenant;
        $this->put(route('client.room.members.update', $representative), [
            'full_name' => 'Đại diện đã sửa',
            'date_of_birth' => $representativeTenant->date_of_birth->toDateString(),
            'gender' => $representativeTenant->gender,
            'identity_number' => $representative->identity_number,
            'cccd_issue_date' => $representativeTenant->cccd_issue_date->toDateString(),
            'cccd_issue_place' => $representativeTenant->cccd_issue_place,
            'email' => 'daidien-moi@example.com',
            'phone' => '0901234567',
            'address' => $representativeTenant->address,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'name' => 'Đại diện đã sửa',
            'email' => 'daidien-moi@example.com',
            'phone' => '0901234567',
        ]);
    }

    public function test_client_cannot_view_pending_or_other_room_members(): void
    {
        [$client, $contract] = $this->rentalFixture('OWNER');
        [, $otherContract, $otherMember] = $this->rentalFixture('OTHER');
        $pending = ContractTenant::create([
            'contract_id' => $contract->id,
            'tenant_id' => Tenant::create([
                'full_name' => 'Hồ sơ chờ duyệt',
                'cccd' => '079123456791',
                'phone' => '0912345680',
            ])->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => 'Hồ sơ chờ duyệt',
            'status' => ContractTenant::STATUS_PENDING,
        ]);

        $this->actingAs($client)
            ->get(route('client.room.members.show', $pending))
            ->assertNotFound();

        $this->get(route('client.room.members.show', $otherMember))
            ->assertNotFound();

        $this->get(route('client.room.members.identity', [$otherMember, 'front']))
            ->assertNotFound();

        $this->put(route('client.room.members.update', $otherMember), [])
            ->assertNotFound();

        $this->assertNotSame($contract->id, $otherContract->id);
    }

    public function test_client_can_see_move_in_and_move_out_history_without_opening_former_member_profile(): void
    {
        [$client, $contract] = $this->rentalFixture('HISTORY');
        $formerTenant = Tenant::create([
            'full_name' => 'Người thuê đã rời phòng',
            'cccd' => '079999999901',
            'phone' => '0987654321',
        ]);
        $formerMember = ContractTenant::create([
            'contract_id' => $contract->id,
            'tenant_id' => $formerTenant->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => 'Người thuê đã rời phòng',
            'phone' => '0987654321',
            'status' => ContractTenant::STATUS_MOVED_OUT,
            'actual_move_in_at' => '2026-08-05 09:30:00',
            'actual_move_out_at' => '2026-08-20 18:15:00',
        ]);

        $this->actingAs($client)
            ->get(route('client.room.members.index'))
            ->assertOk()
            ->assertSee('Lịch sử cư trú')
            ->assertSee($formerMember->full_name)
            ->assertSee('05/08/2026 09:30')
            ->assertSee('20/08/2026 18:15')
            ->assertDontSee('0987654321')
            ->assertDontSee(route('client.room.members.show', $formerMember));

        $this->get(route('client.room.members.show', $formerMember))->assertNotFound();
    }

    private function rentalFixture(string $suffix): array
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);
        $client = User::create([
            'name' => 'Khách '.$suffix,
            'email' => strtolower($suffix).'@room-member.test',
            'role_id' => $role->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $client->id,
            'full_name' => 'Khách đại diện '.$suffix,
            'date_of_birth' => '1995-01-01',
            'gender' => 'female',
            'cccd' => '0790000000'.str_pad((string) Tenant::count(), 2, '0', STR_PAD_LEFT),
            'cccd_issue_date' => '2020-01-01',
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'email' => strtolower($suffix).'@room-member.test',
            'phone' => '09000000'.str_pad((string) Tenant::count(), 2, '0', STR_PAD_LEFT),
            'address' => 'Hà Nội',
        ]);
        $room = Room::create([
            'room_code' => 'ROOM-'.$suffix,
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'max_people' => 4,
            'current_people' => 1,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-'.$suffix,
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'number_of_people' => 1,
            'start_date' => '2026-08-01',
            'end_date' => '2027-08-01',
            'actual_move_in_at' => '2026-08-01 09:00:00',
            'status' => Contract::STATUS_ACTIVE,
        ]);
        $representative = ContractTenant::create([
            'contract_id' => $contract->id,
            'tenant_id' => $tenant->id,
            'role' => ContractTenant::ROLE_REPRESENTATIVE,
            'full_name' => $tenant->full_name,
            'date_of_birth' => $tenant->date_of_birth,
            'identity_number' => $tenant->cccd,
            'phone' => $tenant->phone,
            'status' => ContractTenant::STATUS_CHECKED_IN,
            'actual_move_in_at' => '2026-08-01 09:00:00',
        ]);

        return [$client, $contract, $representative];
    }
}
