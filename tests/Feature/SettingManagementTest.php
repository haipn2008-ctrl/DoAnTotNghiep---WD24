<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = $this->createUser('Admin', 'settings-admin@example.test');
    }

    public function test_admin_can_view_all_four_price_types_and_empty_database_creates_only_one_active_setting(): void
    {
        $expected = [
            'electricity' => 'Đơn giá điện',
            'water' => 'Đơn giá nước',
            'internet' => 'Phí internet',
            'service' => 'Phí dịch vụ',
        ];

        foreach ($expected as $type => $label) {
            $this->actingAs($this->admin)->get('/admin/settings/'.$type)
                ->assertSuccessful()
                ->assertSee($label);
        }

        $this->assertDatabaseCount('settings', 1);
        $this->assertDatabaseHas('settings', ['is_active' => true]);
    }

    public function test_sidebar_only_shows_two_consolidated_setting_entries(): void
    {
        $this->actingAs($this->admin)->get('/admin/settings/fees')
            ->assertSuccessful()
            ->assertSee('href="'.route('admin.settings.edit', ['type' => 'fees']).'"', false)
            ->assertSee('href="'.route('admin.settings.edit', ['type' => 'bank']).'"', false)
            ->assertDontSee('href="'.route('admin.settings.edit', ['type' => 'electricity']).'"', false)
            ->assertSee('Đơn giá điện')
            ->assertSee('Đơn giá nước')
            ->assertSee('Phí Internet')
            ->assertSee('Phí gửi xe');
    }

    public function test_consolidated_fee_form_updates_every_fee_atomically(): void
    {
        $setting = $this->createSetting();
        $payload = [
            'electric_price' => 4100,
            'water_price' => 22000,
            'internet_fee' => 120000,
            'service_fee' => 65000,
            'parking_fee' => 80000,
        ];

        $this->actingAs($this->admin)->put('/admin/settings/fees', $payload)
            ->assertRedirect('/admin/settings/fees')
            ->assertSessionHasNoErrors();
        foreach ($payload as $field => $value) {
            $this->assertSame(number_format($value, 2, '.', ''), $setting->fresh()->{$field});
        }

        $this->put('/admin/settings/fees', array_merge($payload, [
            'electric_price' => 5000,
            'water_price' => -1,
        ]))->assertSessionHasErrors('water_price');
        $this->assertSame('4100.00', $setting->fresh()->electric_price);
        $this->assertSame('22000.00', $setting->fresh()->water_price);
    }

    public function test_each_price_type_updates_only_its_own_field(): void
    {
        $setting = $this->createSetting();
        $types = [
            'electricity' => 'electric_price',
            'water' => 'water_price',
            'internet' => 'internet_fee',
            'service' => 'service_fee',
        ];

        foreach ($types as $type => $field) {
            $before = $setting->fresh()->only(array_values($types));
            $this->actingAs($this->admin)->put('/admin/settings/'.$type, [$field => 12345.67])
                ->assertRedirect('/admin/settings/'.$type)
                ->assertSessionHasNoErrors();

            $after = $setting->fresh();
            $this->assertSame('12345.67', $after->{$field});
            foreach ($types as $otherField) {
                if ($otherField !== $field) {
                    $this->assertSame((string) $before[$otherField], $after->{$otherField});
                }
            }
        }
    }

    public function test_zero_is_allowed_but_invalid_precision_range_and_types_change_nothing(): void
    {
        $setting = $this->createSetting();

        $this->actingAs($this->admin)->put('/admin/settings/service', ['service_fee' => 0])
            ->assertSessionHasNoErrors();
        $this->assertSame('0.00', $setting->fresh()->service_fee);

        foreach ([null, -1, 'abc', '1.234', 100000000] as $invalid) {
            $this->actingAs($this->admin)->put('/admin/settings/electricity', [
                'electric_price' => $invalid,
            ])->assertSessionHasErrors('electric_price');
            $this->assertSame('3500.00', $setting->fresh()->electric_price);
        }
    }

    public function test_unknown_setting_type_returns_not_found_without_creating_or_changing_data(): void
    {
        $setting = $this->createSetting();

        $this->actingAs($this->admin)->get('/admin/settings/unknown')->assertNotFound();
        $this->actingAs($this->admin)->put('/admin/settings/parking', [
            'parking_fee' => 999999,
        ])->assertRedirect('/admin/settings/parking')->assertSessionHasNoErrors();

        $this->assertDatabaseCount('settings', 1);
        $this->assertSame('3500.00', $setting->fresh()->electric_price);
        $this->assertSame('999999.00', $setting->fresh()->parking_fee);
    }

    public function test_setting_routes_enforce_authentication_and_admin_role_for_direct_requests(): void
    {
        $client = $this->createUser('User', 'settings-client@example.test');

        $this->get('/admin/settings/electricity')->assertRedirect('/login');
        $this->put('/admin/settings/electricity', ['electric_price' => 1])->assertRedirect('/login');
        $this->actingAs($client)->get('/admin/settings/electricity')->assertForbidden();
        $this->actingAs($client)->put('/admin/settings/electricity', ['electric_price' => 1])->assertForbidden();
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_controller_updates_active_setting_instead_of_an_inactive_record(): void
    {
        $inactive = $this->createSetting(['is_active' => false, 'electric_price' => 1000]);
        $active = $this->createSetting(['is_active' => true, 'electric_price' => 3500]);

        $this->actingAs($this->admin)->put('/admin/settings/electricity', [
            'electric_price' => 4000,
        ])->assertSessionHasNoErrors();

        $this->assertSame('1000.00', $inactive->fresh()->electric_price);
        $this->assertSame('4000.00', $active->fresh()->electric_price);
        $this->actingAs($this->admin)->get('/admin/settings/electricity')->assertSee('4.000');
    }

    public function test_seeder_is_idempotent_and_does_not_overwrite_existing_live_prices(): void
    {
        $this->seed(SettingSeeder::class);
        $setting = Setting::current();
        $setting->update(['electric_price' => 4200]);

        $this->seed(SettingSeeder::class);

        $this->assertDatabaseCount('settings', 1);
        $this->assertSame('4200.00', Setting::current()->electric_price);
    }

    public function test_database_rejects_a_second_active_setting_but_allows_inactive_history(): void
    {
        $this->createSetting(['is_active' => true]);
        $this->createSetting(['is_active' => false]);
        $this->createSetting(['is_active' => false, 'electric_price' => 3600]);

        try {
            $this->createSetting(['is_active' => true, 'electric_price' => 9999]);
            $this->fail('Database accepted more than one active setting.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(1, Setting::where('is_active', true)->count());
        $this->assertSame(2, Setting::where('is_active', false)->count());
    }

    public function test_active_setting_audit_reports_duplicates_without_modifying_them(): void
    {
        $first = $this->createSetting(['is_active' => true]);
        $this->artisan('settings:audit-active')
            ->expectsOutput('Cấu hình đơn giá đang hoạt động hợp lệ: 1 bản ghi.')
            ->assertSuccessful();
        DB::statement('DROP INDEX settings_single_active_unique');

        try {
            $second = $this->createSetting(['is_active' => true, 'electric_price' => 9999]);
            $this->artisan('settings:audit-active')
                ->expectsOutput('Phát hiện nhiều cấu hình đơn giá đang hoạt động. Không tự động thay đổi dữ liệu tài chính.')
                ->assertFailed();
            $this->assertTrue($first->fresh()->is_active);
            $this->assertTrue($second->fresh()->is_active);
            $second->delete();
        } finally {
            Setting::whereKeyNot($first->id)->where('is_active', true)->delete();
            DB::statement('CREATE UNIQUE INDEX settings_single_active_unique ON settings (is_active) WHERE is_active = 1');
        }
    }

    private function createUser(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'name' => $roleName.' setting',
            'email' => $email,
            'phone' => '0972'.str_pad((string) User::count(), 6, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createSetting(array $attributes = []): Setting
    {
        return Setting::create(array_merge([
            'electric_price' => 3500,
            'water_price' => 15000,
            'internet_fee' => 100000,
            'service_fee' => 50000,
            'parking_fee' => 0,
            'invoice_day' => 5,
            'payment_due_days' => 10,
            'is_active' => true,
        ], $attributes));
    }
}
