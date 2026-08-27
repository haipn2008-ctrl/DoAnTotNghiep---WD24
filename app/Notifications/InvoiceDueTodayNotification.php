<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvoiceDueTodayNotification extends Notification
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
            'type' => 'invoice_due_today',
            'title' => 'Hôm nay là hạn cuối thanh toán',
            'message' => 'Hóa đơn '.$this->invoice->invoice_code.' đến hạn hôm nay. Vui lòng hoàn tất thanh toán trước khi hết ngày.',
            'action' => 'invoice',
            'invoice_id' => $this->invoice->id,
            'invoice_code' => $this->invoice->invoice_code,
            'due_date' => $this->invoice->effective_due_date?->format('d/m/Y'),
        ];
    }
}
