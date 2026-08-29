<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Đặt lại mật khẩu - Quản lý phòng trọ')
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line('Mã xác thực đặt lại mật khẩu của bạn là:')
            ->line('**'.$this->code.'**')
            ->line('Nhập mã này trên trang đặt lại mật khẩu. Mã có hiệu lực trong 10 phút và chỉ dùng được một lần.')
            ->line('Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.');
    }
}
