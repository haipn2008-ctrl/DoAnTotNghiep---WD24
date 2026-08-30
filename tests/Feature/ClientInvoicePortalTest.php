<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientInvoicePortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_client_only_sees_invoices_belonging_to_their_tenant_profile(): void
    {
        [$client, $ownContract, $ownRoom] = $this->createClientContext('A');
        [, $otherContract, $otherRoom] = $this->createClientContext('B');
        $ownInvoice = $this->createInvoice($ownContract, $ownRoom, 'INV-OWN');
        $this->createInvoice($otherContract, $otherRoom, 'INV-OTHER');

        $this->actingAs($client)
            ->get('/client/invoices')
            ->assertSuccessful()
            ->assertSee('INV-OWN')
            ->assertDontSee('INV-OTHER');

        $this->actingAs($client)
            ->get('/client/invoices/'.$ownInvoice->id)
            ->assertSuccessful()
            ->assertSee('Tiền điện')
            ->assertSee('20 kWh')
            ->assertSee('Gửi biên lai cho ban quản lý')
            ->assertSee('Quét mã VietQR')
            ->assertDontSee('Mã thanh toán cố định của bạn')
            ->assertDontSee('Khi chuyển tiền ghi rõ mã thanh toán')
            ->assertDontSee('Chỉ số cũ')
            ->assertDontSee('Chỉ số mới');
    }

    public function test_client_cannot_open_or_print_another_tenants_invoice(): void
    {
        [$client] = $this->createClientContext('OWNER');
        [, $otherContract, $otherRoom] = $this->createClientContext('OTHER');
        $otherInvoice = $this->createInvoice($otherContract, $otherRoom, 'INV-PRIVATE');

        $this->actingAs($client)
            ->get('/client/invoices/'.$otherInvoice->id)
            ->assertNotFound();

        $this->actingAs($client)
            ->get('/client/invoices/'.$otherInvoice->id.'/print')
            ->assertNotFound();
    }

    public function test_client_payment_confirmation_stays_pending_until_admin_approves_it(): void
    {
        Storage::fake('local');
        [$client, $contract, $room] = $this->createClientContext('PAY');
        $invoice = $this->createInvoice($contract, $room, 'INV-PAY');

        $this->actingAs($client)
            ->post('/client/invoices/'.$invoice->id.'/payments', [
                'amount_paid' => 3070000,
                'payment_date' => '2026-07-09',
                'payment_method' => 'bank_transfer',
                'transaction_code' => 'FT-PAY-001',
                'proof_image' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect('/client/invoices/'.$invoice->id);

        $payment = Payment::firstOrFail();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame($client->id, $payment->submitted_by);
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->fresh()->status);
        Storage::disk('local')->assertExists($payment->proof_image);

        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Admin duyệt',
            'email' => 'payment-admin@example.com',
            'phone' => '0999999999',
            'role_id' => $adminRole->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->post('/admin/invoices/payments/'.$payment->id.'/approve')
            ->assertRedirect();

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame($admin->id, $payment->fresh()->confirmed_by);
        $this->assertNotNull($payment->fresh()->reviewed_at);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_client_cannot_submit_payment_for_another_tenants_invoice(): void
    {
        Storage::fake('local');
        [$client] = $this->createClientContext('PAYOWNER');
        [, $otherContract, $otherRoom] = $this->createClientContext('PAYOTHER');
        $otherInvoice = $this->createInvoice($otherContract, $otherRoom, 'INV-PAY-PRIVATE');

        $this->actingAs($client)
            ->post('/client/invoices/'.$otherInvoice->id.'/payments', [
                'amount_paid' => 100000,
                'payment_date' => '2026-07-09',
                'payment_method' => 'bank_transfer',
                'transaction_code' => 'INVALID',
                'proof_image' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_proof_is_private_and_only_available_to_owner_or_admin(): void
    {
        Storage::fake('local');
        [$client, $contract, $room] = $this->createClientContext('PROOFOWNER');
        [$otherClient] = $this->createClientContext('PROOFOTHER');
        $invoice = $this->createInvoice($contract, $room, 'INV-PROOF');

        $this->actingAs($client)->post('/client/invoices/'.$invoice->id.'/payments', [
            'amount_paid' => 100000,
            'proof_image' => UploadedFile::fake()->image('private-proof.jpg'),
        ])->assertSessionHasNoErrors();

        $payment = Payment::firstOrFail();
        $clientProofUrl = route('client.invoices.payments.proof', $payment);
        $adminProofUrl = route('admin.invoices.payments.proof', $payment);

        $this->post('/logout');
        $this->get($clientProofUrl)->assertRedirect('/login');
        $this->actingAs($otherClient)->get($clientProofUrl)->assertNotFound();
        $ownerResponse = $this->actingAs($client)->get($clientProofUrl)
            ->assertSuccessful();
        $this->assertStringContainsString('private', $ownerResponse->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', $ownerResponse->headers->get('cache-control'));

        $admin = $this->createAdmin('proof-admin@example.test');
        $this->actingAs($client)->get($adminProofUrl)->assertForbidden();
        $this->actingAs($admin)->get($adminProofUrl)->assertSuccessful();

        $payment->update([
            'status' => Payment::STATUS_SUCCESS,
            'confirmed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $invoice))
            ->assertSuccessful()
            ->assertSee('Xem biên lai')
            ->assertSee($adminProofUrl, false);
    }

    public function test_admin_payment_page_shows_newest_request_first(): void
    {
        [, $contract, $room] = $this->createClientContext('PAYMENTORDER');
        $invoice = $this->createInvoice($contract, $room, 'INV-PAYMENT-ORDER');
        $admin = $this->createAdmin('payment-order-admin@example.test');

        foreach ([
            ['code' => 'PAYMENT-OLDER', 'created_at' => now()->subHour(), 'proof' => null],
            ['code' => 'PAYMENT-NEWER', 'created_at' => now(), 'proof' => 'payment-proofs/newer.jpg'],
        ] as $item) {
            Payment::query()->forceCreate([
                'invoice_id' => $invoice->id,
                'amount_paid' => 100000,
                'payment_date' => today(),
                'payment_method' => Payment::METHOD_BANK_TRANSFER,
                'transaction_code' => $item['code'],
                'status' => Payment::STATUS_PENDING,
                'proof_image' => $item['proof'],
                'created_at' => $item['created_at'],
                'updated_at' => $item['created_at'],
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.invoices.payments'))
            ->assertSuccessful()
            ->assertSeeInOrder(['PAYMENT-NEWER', 'PAYMENT-OLDER'])
            ->assertSee('Xem ảnh lớn')
            ->assertDontSee('Không có ảnh');
    }

    public function test_admin_can_reject_client_payment_without_reducing_invoice_balance(): void
    {
        [$client, $contract, $room] = $this->createClientContext('REJECTPAY');
        $invoice = $this->createInvoice($contract, $room, 'INV-REJECT');
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 500000,
            'payment_date' => '2026-07-09',
            'payment_method' => 'bank_transfer',
            'transaction_code' => 'REJECT-001',
            'status' => Payment::STATUS_PENDING,
            'submitted_by' => $client->id,
        ]);
        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Admin từ chối',
            'email' => 'reject-admin@example.com',
            'phone' => '0977777777',
            'role_id' => $adminRole->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->post('/admin/invoices/payments/'.$payment->id.'/reject', [
                'review_note' => 'Không tìm thấy giao dịch tương ứng.',
            ])
            ->assertRedirect();

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->fresh()->status);
        $this->actingAs($client)
            ->get('/client/invoices/'.$invoice->id)
            ->assertSee('Không tìm thấy giao dịch tương ứng.');
    }

    public function test_client_cannot_reserve_more_than_the_available_invoice_balance(): void
    {
        Storage::fake('local');
        [$client, $contract, $room] = $this->createClientContext('RESERVE');
        $invoice = $this->createInvoice($contract, $room, 'INV-RESERVE');

        foreach ([['RESERVE-1', 2000000], ['RESERVE-2', 1070000]] as [$code, $amount]) {
            $this->actingAs($client)->post('/client/invoices/'.$invoice->id.'/payments', [
                'amount_paid' => $amount,
                'payment_date' => '2026-07-09',
                'payment_method' => 'bank_transfer',
                'transaction_code' => $code,
                'proof_image' => UploadedFile::fake()->image($code.'.jpg'),
            ])->assertSessionHasNoErrors();
        }

        $this->actingAs($client)->from('/client/invoices/'.$invoice->id)
            ->post('/client/invoices/'.$invoice->id.'/payments', [
                'amount_paid' => 1,
                'payment_date' => '2026-07-09',
                'payment_method' => 'qr',
                'transaction_code' => 'RESERVE-3',
                'proof_image' => UploadedFile::fake()->image('reserve-3.jpg'),
            ])
            ->assertRedirect('/client/invoices/'.$invoice->id)
            ->assertSessionHasErrors('amount_paid');

        $this->assertDatabaseCount('payments', 2);
        Storage::disk('local')->assertMissing('payment-proofs/reserve-3.jpg');
    }

    public function test_client_payment_rejects_huge_fractional_and_non_positive_amounts_before_storing_proof(): void
    {
        Storage::fake('local');
        [$client, $contract, $room] = $this->createClientContext('AMOUNT-VALIDATION');
        $invoice = $this->createInvoice($contract, $room, 'INV-AMOUNT-VALIDATION');

        foreach (['999999999999999999999999', '1.5', 0] as $index => $amount) {
            $this->actingAs($client)->post('/client/invoices/'.$invoice->id.'/payments', [
                'amount_paid' => $amount,
                'proof_image' => UploadedFile::fake()->image("invalid-{$index}.jpg"),
            ])->assertSessionHasErrors('amount_paid');
        }

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_client_payment_uses_server_date_and_qr_method_instead_of_untrusted_fields(): void
    {
        Storage::fake('local');
        [$client, $contract, $room] = $this->createClientContext('DUPLICATE');
        $invoice = $this->createInvoice($contract, $room, 'INV-DUPLICATE');
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 100000,
            'payment_date' => '2026-07-09',
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'transaction_code' => 'BANK-UNIQUE-1',
            'status' => Payment::STATUS_FAILED,
        ]);

        $response = $this->actingAs($client)->post('/client/invoices/'.$invoice->id.'/payments', [
            'amount_paid' => 100000,
            'payment_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash',
            'transaction_code' => 'BANK-UNIQUE-1',
            'proof_image' => UploadedFile::fake()->image('invalid.jpg'),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 2);
        $submitted = Payment::latest('id')->firstOrFail();
        $this->assertSame(now()->toDateString(), $submitted->payment_date->toDateString());
        $this->assertSame(Payment::METHOD_QR, $submitted->payment_method);
        $this->assertNull($submitted->transaction_code);
        $this->assertSame(Payment::STATUS_PENDING, $submitted->status);
    }

    public function test_admin_payment_is_atomic_and_cannot_overpay_or_use_future_date(): void
    {
        [$client, $contract, $room] = $this->createClientContext('ADMINPAY');
        $invoice = $this->createInvoice($contract, $room, 'INV-ADMINPAY');
        $admin = $this->createAdmin('admin-pay@example.test');

        $this->actingAs($admin)->post('/admin/invoices/'.$invoice->id.'/payments', [
            'amount_paid' => 3000000,
            'payment_date' => '2026-07-09',
            'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post('/admin/invoices/'.$invoice->id.'/payments', [
            'amount_paid' => 70001,
            'payment_date' => '2026-07-09',
            'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasErrors('amount_paid');

        $this->actingAs($admin)->post('/admin/invoices/'.$invoice->id.'/payments', [
            'amount_paid' => 70000,
            'payment_date' => now()->addDay()->toDateString(),
            'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasErrors('payment_date');

        $this->assertSame(1, Payment::count());
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
        $recordedPayment = Payment::firstOrFail();
        $this->assertSame($admin->id, $recordedPayment->submitted_by);
        $this->assertSame($admin->id, $recordedPayment->confirmed_by);
        $this->assertNotNull($recordedPayment->reviewed_at);
        $this->assertSame($client->tenant->id, $invoice->contract->tenant_id);
    }

    public function test_admin_direct_payment_respects_amount_reserved_by_pending_submissions(): void
    {
        [$client, $contract, $room] = $this->createClientContext('ADMINRESERVE');
        $invoice = $this->createInvoice($contract, $room, 'INV-ADMINRESERVE');
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 100000,
            'payment_date' => today(),
            'payment_method' => Payment::METHOD_QR,
            'status' => Payment::STATUS_PENDING,
            'submitted_by' => $client->id,
        ]);
        $admin = $this->createAdmin('admin-reserve@example.test');

        $payload = [
            'amount_paid' => 3070000,
            'payment_date' => today()->toDateString(),
            'payment_method' => Payment::METHOD_CASH,
        ];

        $this->actingAs($admin)
            ->post('/admin/invoices/'.$invoice->id.'/payments', $payload)
            ->assertSessionHasErrors('amount_paid');

        $payload['amount_paid'] = 2970000;
        $this->actingAs($admin)
            ->post('/admin/invoices/'.$invoice->id.'/payments', $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Payment::count());
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
    }

    public function test_invoice_detail_exposes_pending_review_and_allows_more_partial_collections_after_rejection(): void
    {
        [$client, $contract, $room] = $this->createClientContext('DETAILREVIEW');
        $invoice = $this->createInvoice($contract, $room, 'INV-DETAIL-REVIEW');
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 2570000,
            'payment_date' => today(),
            'payment_method' => Payment::METHOD_CASH,
            'status' => Payment::STATUS_SUCCESS,
        ]);
        $pending = Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 500000,
            'payment_date' => today(),
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'transaction_code' => 'DETAIL-PENDING-001',
            'status' => Payment::STATUS_PENDING,
            'submitted_by' => $client->id,
        ]);
        $invoice->refreshStatus();
        $admin = $this->createAdmin('detail-review-admin@example.test');

        $this->actingAs($admin)->get(route('admin.invoices.show', $invoice))
            ->assertSuccessful()
            ->assertSee('Chờ xác nhận')
            ->assertSee('Duyệt thanh toán')
            ->assertSee('Từ chối')
            ->assertSee('Số tiền còn lại đang được giữ cho giao dịch chờ xác nhận.')
            ->assertDontSee('name="amount_paid"', false);

        $this->post(route('admin.invoices.payments.reject', $pending), [
            'review_note' => 'Không tìm thấy giao dịch trong tài khoản ngân hàng.',
        ])->assertSessionHasNoErrors();

        $this->get(route('admin.invoices.show', $invoice))
            ->assertSuccessful()
            ->assertSee('Ghi nhận thanh toán')
            ->assertSee('name="amount_paid"', false);

        $this->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 200000,
            'payment_date' => today()->toDateString(),
            'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasNoErrors();

        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
        $this->actingAs($admin)->get(route('admin.invoices.show', $invoice))
            ->assertSee('Ghi nhận thanh toán')
            ->assertSee('300.000đ');
    }

    public function test_existing_public_payment_proofs_are_moved_to_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        [, $contract, $room] = $this->createClientContext('LEGACYPROOF');
        $invoice = $this->createInvoice($contract, $room, 'INV-LEGACYPROOF');
        $path = 'payment-proofs/legacy-proof.jpg';
        Storage::disk('public')->put($path, 'legacy-proof');
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 100000,
            'payment_date' => today(),
            'payment_method' => Payment::METHOD_QR,
            'status' => Payment::STATUS_PENDING,
            'proof_image' => $path,
        ]);

        $migration = require database_path('migrations/2026_08_24_000016_move_payment_proofs_to_private_storage.php');
        $migration->up();

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_orphaned_public_payment_proofs_are_preserved_in_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $path = 'payment-proofs/orphaned-proof.jpg';
        Storage::disk('public')->put($path, 'orphaned-proof');

        $migration = require database_path('migrations/2026_08_24_000017_move_orphaned_payment_proofs_to_private_storage.php');
        $migration->up();

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('orphaned-proof', Storage::disk('local')->get($path));
    }

    public function test_orphaned_proof_name_collision_preserves_both_private_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $path = 'payment-proofs/collision.jpg';
        Storage::disk('public')->put($path, 'public-version');
        Storage::disk('local')->put($path, 'private-version');

        $migration = require database_path('migrations/2026_08_24_000017_move_orphaned_payment_proofs_to_private_storage.php');
        $migration->up();

        Storage::disk('public')->assertMissing($path);
        $this->assertSame('private-version', Storage::disk('local')->get($path));
        $orphanedPaths = Storage::disk('local')->files('payment-proofs/orphaned');
        $this->assertCount(1, $orphanedPaths);
        $this->assertSame('public-version', Storage::disk('local')->get($orphanedPaths[0]));
    }

    public function test_processed_payment_cannot_be_approved_or_rejected_again(): void
    {
        [$client, $contract, $room] = $this->createClientContext('IDEMPOTENT');
        $invoice = $this->createInvoice($contract, $room, 'INV-IDEMPOTENT');
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 500000,
            'payment_date' => '2026-07-09',
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'transaction_code' => 'IDEMPOTENT-1',
            'status' => Payment::STATUS_PENDING,
            'submitted_by' => $client->id,
        ]);
        $admin = $this->createAdmin('idempotent-admin@example.test');

        $this->actingAs($admin)->post('/admin/invoices/payments/'.$payment->id.'/approve')
            ->assertSessionHasNoErrors();
        $reviewedAt = $payment->fresh()->reviewed_at;

        $this->actingAs($admin)->post('/admin/invoices/payments/'.$payment->id.'/approve')
            ->assertSessionHasErrors('payment');
        $this->actingAs($admin)->post('/admin/invoices/payments/'.$payment->id.'/reject', [
            'review_note' => 'Không được ghi đè.',
        ])->assertSessionHasErrors('payment');

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);
        $this->assertSame($admin->id, $payment->confirmed_by);
        $this->assertTrue($reviewedAt->equalTo($payment->reviewed_at));
        $this->assertNull($payment->review_note);
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
    }

    public function test_database_rejects_non_positive_amount_and_duplicate_transaction_code(): void
    {
        [, $contract, $room] = $this->createClientContext('DBPAY');
        $invoice = $this->createInvoice($contract, $room, 'INV-DBPAY');
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 1,
            'payment_date' => '2026-07-09',
            'payment_method' => Payment::METHOD_CASH,
            'transaction_code' => 'DB-DUPLICATE',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        foreach ([
            ['amount_paid' => 0, 'transaction_code' => 'DB-ZERO'],
            ['amount_paid' => 1, 'transaction_code' => 'DB-DUPLICATE'],
        ] as $values) {
            try {
                Payment::create(array_merge([
                    'invoice_id' => $invoice->id,
                    'payment_date' => '2026-07-09',
                    'payment_method' => Payment::METHOD_CASH,
                    'status' => Payment::STATUS_SUCCESS,
                ], $values));
                $this->fail('Database must reject invalid payment integrity.');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_endpoints_enforce_authentication_and_role_boundaries(): void
    {
        [$client, $contract, $room] = $this->createClientContext('PAYAUTH');
        $invoice = $this->createInvoice($contract, $room, 'INV-PAYAUTH');
        $admin = $this->createAdmin('payment-auth-admin@example.test');

        $this->post('/admin/invoices/'.$invoice->id.'/payments')->assertRedirect('/login');
        $this->post('/client/invoices/'.$invoice->id.'/payments')->assertRedirect('/login');
        $this->actingAs($client)->post('/admin/invoices/'.$invoice->id.'/payments')->assertForbidden();
        $this->actingAs($admin)->post('/client/invoices/'.$invoice->id.'/payments')->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_approval_rechecks_balance_after_another_payment_was_recorded(): void
    {
        [$client, $contract, $room] = $this->createClientContext('STALEPAY');
        $invoice = $this->createInvoice($contract, $room, 'INV-STALEPAY');
        $pending = Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 2000000,
            'payment_date' => '2026-07-09',
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'transaction_code' => 'STALE-PENDING',
            'status' => Payment::STATUS_PENDING,
            'submitted_by' => $client->id,
        ]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 2000000,
            'payment_date' => '2026-07-10',
            'payment_method' => Payment::METHOD_CASH,
            'status' => Payment::STATUS_SUCCESS,
        ]);
        $admin = $this->createAdmin('stale-payment-admin@example.test');

        $this->actingAs($admin)->post('/admin/invoices/payments/'.$pending->id.'/approve')
            ->assertSessionHasErrors('payment');

        $this->assertSame(Payment::STATUS_PENDING, $pending->fresh()->status);
        $this->assertNull($pending->confirmed_by);
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->fresh()->status);
    }

    public function test_admin_payment_list_rejects_invalid_filters(): void
    {
        $admin = $this->createAdmin('payment-filter-admin@example.test');

        $this->actingAs($admin)->get('/admin/invoices/payments?status=forged&method=crypto&keyword='.(str_repeat('x', 101)))
            ->assertSessionHasErrors(['status', 'method', 'keyword']);
    }

    public function test_transaction_code_audit_reports_clean_and_duplicate_data_without_modifying_it(): void
    {
        $this->artisan('payments:audit-transaction-codes')
            ->expectsOutput('Không phát hiện mã giao dịch trùng. Có thể chạy migration an toàn.')
            ->assertSuccessful();

        [, $contract, $room] = $this->createClientContext('AUDITPAY');
        $invoice = $this->createInvoice($contract, $room, 'INV-AUDITPAY');
        DB::statement('DROP INDEX payments_transaction_code_unique');

        try {
            foreach ([100000, 200000] as $amount) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount_paid' => $amount,
                    'payment_date' => '2026-07-09',
                    'payment_method' => Payment::METHOD_BANK_TRANSFER,
                    'transaction_code' => 'AUDIT-DUPLICATE',
                    'status' => Payment::STATUS_SUCCESS,
                ]);
            }

            $this->artisan('payments:audit-transaction-codes')
                ->expectsOutput('Phát hiện mã giao dịch trùng. Không tự động thay đổi dữ liệu tài chính.')
                ->assertFailed();

            $this->assertSame(2, Payment::where('transaction_code', 'AUDIT-DUPLICATE')->count());
        } finally {
            Payment::where('transaction_code', 'AUDIT-DUPLICATE')->delete();
            DB::statement('CREATE UNIQUE INDEX payments_transaction_code_unique ON payments (transaction_code)');
        }
    }

    public function test_client_only_sees_utility_usage_within_their_own_contract_periods(): void
    {
        [$client, $contract, $room] = $this->createClientContext('UTILITY');
        [, $otherContract, $otherRoom] = $this->createClientContext('UTILITYOTHER');

        $ownReading = UtilityReading::create([
            'room_id' => $room->id,
            'month' => 7,
            'year' => 2026,
            'record_date' => '2026-07-31',
            'electricity_old' => 100,
            'electricity_new' => 120,
            'water_old' => 40,
            'water_new' => 44,
            'status' => 'confirmed',
        ]);
        UtilityReading::create([
            'room_id' => $room->id,
            'month' => 12,
            'year' => 2025,
            'electricity_old' => 0,
            'electricity_new' => 888,
            'water_old' => 0,
            'water_new' => 888,
            'status' => 'confirmed',
        ]);
        UtilityReading::create([
            'room_id' => $otherRoom->id,
            'month' => 7,
            'year' => 2026,
            'electricity_old' => 0,
            'electricity_new' => 777,
            'water_old' => 0,
            'water_new' => 777,
            'status' => 'confirmed',
        ]);
        $invoice = $this->createInvoice($contract, $room, 'INV-UTILITY');
        $invoice->update(['utility_reading_id' => $ownReading->id]);

        $this->actingAs($client)
            ->get('/client/utilities')
            ->assertSuccessful()
            ->assertSeeText('20 kWh')
            ->assertSeeText('4 m³')
            ->assertSeeText('70.000đ')
            ->assertDontSeeText('777 kWh')
            ->assertDontSeeText('888 kWh')
            ->assertDontSee('Chỉ số cũ')
            ->assertDontSee('Chỉ số mới');
    }

    public function test_client_only_sees_their_own_room_and_contracts(): void
    {
        [$client, $ownContract, $ownRoom] = $this->createClientContext('PORTAL');
        [, $otherContract, $otherRoom] = $this->createClientContext('PORTALOTHER');

        $this->actingAs($client)
            ->get('/client/room')
            ->assertSuccessful()
            ->assertSee($ownRoom->room_code)
            ->assertDontSee($otherRoom->room_code);

        $this->actingAs($client)
            ->get('/client/contracts')
            ->assertSuccessful()
            ->assertSee($ownContract->contract_code)
            ->assertDontSee($otherContract->contract_code);

        $this->actingAs($client)
            ->get('/client/contracts/'.$otherContract->id)
            ->assertNotFound();
    }

    public function test_support_request_can_be_sent_reviewed_and_seen_only_by_its_owner(): void
    {
        Storage::fake('local');
        [$client, $contract] = $this->createClientContext('SUPPORT');
        [$otherClient] = $this->createClientContext('SUPPORTOTHER');

        $this->actingAs($client)
            ->post('/client/support', [
                'submission_token' => (string) Str::uuid(),
                'category' => 'utility',
                'subject' => 'Điện tăng bất thường',
                'description' => 'Nhờ ban quản lý kiểm tra lại mức sử dụng.',
                'attachment' => UploadedFile::fake()->image('meter.jpg'),
            ])
            ->assertRedirect();

        $supportRequest = SupportRequest::firstOrFail();
        $this->assertSame($client->id, $supportRequest->user_id);
        $this->assertSame($contract->id, $supportRequest->contract_id);
        Storage::disk('local')->assertExists($supportRequest->attachment);

        $this->actingAs($otherClient)
            ->get('/client/support')
            ->assertSuccessful()
            ->assertDontSee('Điện tăng bất thường');

        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Admin hỗ trợ',
            'email' => 'support-admin@example.com',
            'phone' => '0988888888',
            'role_id' => $adminRole->id,
            'password' => 'password',
        ]);
        $this->actingAs($admin)
            ->get('/admin/support')
            ->assertSuccessful()
            ->assertSee('Điện tăng bất thường');

        $this->actingAs($admin)
            ->put('/admin/support/'.$supportRequest->id, [
                'status' => 'resolved',
                'admin_response' => 'Đã kiểm tra và xác nhận chỉ số đúng.',
            ])
            ->assertRedirect();

        $this->actingAs($client)
            ->get('/client/support')
            ->assertSuccessful()
            ->assertSee('Điện tăng bất thường')
            ->assertSee('Đã kiểm tra và xác nhận chỉ số đúng.');
    }

    public function test_client_can_update_contact_information_and_password(): void
    {
        Storage::fake('local');
        [$client] = $this->createClientContext('ACCOUNT');

        $this->actingAs($client)
            ->put('/client/account', [
                'name' => 'Tên mới',
                'date_of_birth' => '1992-02-02',
                'gender' => 'female',
                'cccd' => '079000009876',
                'cccd_issue_date' => '2021-03-04',
                'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                'email' => 'new-account@example.com',
                'phone' => '0912345678',
                'address' => 'Địa chỉ mới',
                'identity_front' => UploadedFile::fake()->image('cccd-front.jpg'),
                'identity_back' => UploadedFile::fake()->image('cccd-back.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertSame('Tên mới', $client->name);
        $this->assertSame('new-account@example.com', $client->email);
        $this->assertSame('new-account@example.com', $client->tenant->email);
        $this->assertSame('0912345678', $client->tenant->phone);
        $this->assertSame('079000009876', $client->tenant->cccd);
        $this->assertSame('Địa chỉ mới', $client->tenant->address);
        $document = $client->tenant->document;
        $this->assertNotNull($document);
        Storage::disk('local')->assertExists([$document->cccd_front_image, $document->cccd_back_image]);
        $this->actingAs($client)->get(route('client.account.identity-document', 'front'))->assertOk();
        $this->get(route('client.account.edit'))
            ->assertOk()
            ->assertSee('data-image-modal', false)
            ->assertSee(route('client.account.identity-document', 'front'));

        $this->actingAs($client)
            ->put('/client/account/password', [
                'current_password' => 'password',
                'password' => 'New-password-123!',
                'password_confirmation' => 'New-password-123!',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('New-password-123!', $client->fresh()->password));
    }

    private function createClientContext(string $suffix): array
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);
        $user = User::create([
            'name' => 'Khách '.$suffix,
            'email' => strtolower($suffix).'@client.test',
            'phone' => '090000'.str_pad((string) User::count(), 4, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'password' => 'password',
        ]);
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'full_name' => 'Khách thuê '.$suffix,
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'cccd' => 'CCCD-'.$suffix,
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => 'Hà Nội',
        ]);
        $room = Room::create([
            'room_code' => 'P-'.$suffix,
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'status' => 'occupied',
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-'.$suffix,
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'deposit_amount' => 3000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        return [$user, $contract, $room];
    }

    private function createAdmin(string $email): User
    {
        $role = Role::firstOrCreate(['role_name' => 'Admin']);

        return User::create([
            'name' => 'Admin thanh toán',
            'email' => $email,
            'phone' => '098'.str_pad((string) User::count(), 7, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'password' => 'password',
        ]);
    }

    private function createInvoice(Contract $contract, Room $room, string $code): Invoice
    {
        $invoice = Invoice::create([
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'invoice_code' => $code,
            'month' => 7,
            'year' => 2026,
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-10',
            'room_fee' => 3000000,
            'electricity_fee' => 70000,
            'water_fee' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 3070000,
            'status' => 'unpaid',
        ]);
        $invoice->details()->create([
            'type' => 'electricity',
            'name' => 'Tiền điện',
            'quantity' => 20,
            'unit' => 'kWh',
            'unit_price' => 3500,
            'amount' => 70000,
            'old_index' => 100,
            'new_index' => 120,
            'sort_order' => 1,
        ]);

        return $invoice;
    }
}
