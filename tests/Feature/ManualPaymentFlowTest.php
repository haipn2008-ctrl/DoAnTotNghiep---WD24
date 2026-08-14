<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ManualPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_manual_payment_confirmation_routes_remain(): void
    {
        $this->assertTrue(Route::has('client.invoices.payments.store'));
        $this->assertTrue(Route::has('admin.invoices.payments.approve'));
        $this->assertTrue(Route::has('admin.invoices.payments.reject'));

        $this->assertFalse(Route::has('webhooks.payments'));
        $this->assertFalse(Route::has('admin.payment-webhooks.index'));
        $this->assertFalse(Route::has('admin.payment-webhooks.reconcile'));
        $this->assertFalse(Schema::hasTable('payment_webhook_events'));

        $this->postJson('/webhooks/payments', [
            'transaction_id' => 'REMOVED-WEBHOOK',
            'amount' => 100000,
            'content' => 'Thanh toan hoa don',
            'transaction_date' => now()->toIso8601String(),
        ])->assertNotFound();
    }
}
