<?php

namespace App\Notifications;

use App\Models\InvoicePaymentDelayRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentDelayDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly InvoicePaymentDelayRequest $delayRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $approved = $this->delayRequest->status === InvoicePaymentDelayRequest::STATUS_APPROVED;
        $invoice = $this->delayRequest->invoice;

        return [
            'type' => 'payment_delay_decision',
            'title' => $approved ? 'Đã chấp nhận chậm thanh toán' : 'Từ chối lý do chậm thanh toán',
            'message' => $approved
                ? 'Hóa đơn '.$invoice->invoice_code.' được gia hạn đến '.$this->delayRequest->approved_until?->format('d/m/Y').'.'
                : 'Lý do chậm thanh toán hóa đơn '.$invoice->invoice_code.' không được chấp nhận. Vui lòng thanh toán ngay hoặc liên hệ ban quản lý.',
            'action' => 'invoice',
            'invoice_id' => $invoice->id,
            'invoice_code' => $invoice->invoice_code,
            'review_note' => $this->delayRequest->review_note,
        ];
    }
}
