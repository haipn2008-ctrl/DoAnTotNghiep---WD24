<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_overdue',
            'title' => 'Hóa đơn đã quá hạn thanh toán',
            'message' => 'Hóa đơn '.$this->invoice->invoice_code.' đã quá hạn. Vui lòng thanh toán hoặc gửi lý do chậm trễ để ban quản lý xem xét.',
            'action' => 'invoice',
            'invoice_id' => $this->invoice->id,
            'invoice_code' => $this->invoice->invoice_code,
            'due_date' => $this->invoice->effective_due_date?->format('d/m/Y'),
        ];
    }
}
