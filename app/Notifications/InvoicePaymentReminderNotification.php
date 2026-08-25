<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvoicePaymentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly float $remainingAmount,
        private readonly ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = filled($this->note)
            ? trim($this->note)
            : 'Vui lòng kiểm tra và thanh toán khoản công nợ của hóa đơn này.';

        return [
            'type' => 'invoice_payment_reminder',
            'title' => 'Nhắc thanh toán hóa đơn',
            'message' => $message,
            'invoice_id' => $this->invoice->id,
            'invoice_code' => $this->invoice->invoice_code,
            'room_code' => $this->invoice->room?->room_code,
            'due_date' => $this->invoice->due_date?->format('d/m/Y'),
            'remaining_amount' => $this->remainingAmount,
        ];
    }
}
