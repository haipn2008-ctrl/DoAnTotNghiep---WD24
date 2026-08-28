<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\ContractTenant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\ClientPortalNotification;

class ClientNotificationService
{
    public function appendix(ContractAppendix $appendix, string $title, string $message): void
    {
        $appendix->loadMissing('contract.tenant.user');
        $this->send(
            $appendix->contract?->tenant?->user,
            'contract_appendix_pending',
            $title,
            $message,
            'appendix',
            ['appendix_id' => $appendix->id, 'contract_id' => $appendix->contract_id]
        );
    }

    public function contract(Contract $contract, string $type, string $title, string $message): void
    {
        $contract->loadMissing('tenant.user');
        $this->send($contract->tenant?->user, $type, $title, $message, 'contract', [
            'contract_id' => $contract->id,
            'contract_code' => $contract->contract_code,
        ]);
    }

    public function payment(Payment $payment, string $type, string $title, string $message): void
    {
        $payment->loadMissing('invoice.contract.tenant.user');
        $invoice = $payment->invoice;
        $recipient = $payment->submitter ?: $invoice?->contract?->tenant?->user;
        $this->send($recipient, $type, $title, $message, 'invoice', [
            'invoice_id' => $invoice?->id,
            'invoice_code' => $invoice?->invoice_code,
            'payment_id' => $payment->id,
        ]);
    }

    public function invoice(Invoice $invoice, string $type, string $title, string $message): void
    {
        $invoice->loadMissing('contract.tenant.user');
        $this->send($invoice->contract?->tenant?->user, $type, $title, $message, 'invoice', [
            'invoice_id' => $invoice->id,
            'invoice_code' => $invoice->invoice_code,
            'remaining_amount' => $invoice->remaining_amount,
        ]);
    }

    public function support(SupportRequest $request, string $title, string $message): void
    {
        $request->loadMissing('user');
        $this->send($request->user, 'support_request_updated', $title, $message, 'support', [
            'support_request_id' => $request->id,
        ]);
    }

    public function member(ContractTenant $member, string $title, string $message): void
    {
        $member->loadMissing('declarer', 'contract.tenant.user');
        $recipient = $member->declarer ?: $member->contract?->tenant?->user;
        $this->send($recipient, 'contract_member_updated', $title, $message, 'contract', [
            'contract_id' => $member->contract_id,
            'contract_member_id' => $member->id,
        ]);
    }

    public function vehicle(Vehicle $vehicle, string $title, string $message): void
    {
        $vehicle->loadMissing('tenant.user');
        $this->send($vehicle->tenant?->user, 'vehicle_reviewed', $title, $message, 'vehicles', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    private function send(?User $recipient, string $type, string $title, string $message, string $action, array $context): void
    {
        if (! $recipient) {
            return;
        }

        $recipient->notify(new ClientPortalNotification($type, $title, $message, $action, $context));
    }
}
