<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
{
    public function __construct(public string $initialPassword) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Thông tin tài khoản - Quản lý phòng trọ')
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line('Tài khoản khách thuê của bạn đã được tạo trên hệ thống quản lý phòng trọ.')
            ->line('Email đăng nhập: '.$notifiable->email)
            ->line('Mật khẩu ban đầu: '.$this->initialPassword)
            ->action('Đăng nhập tài khoản', route('login'))
            ->line('Vì lý do bảo mật, bạn phải đổi mật khẩu trong lần đăng nhập đầu tiên.')
            ->line('Không chia sẻ email này hoặc mật khẩu với bất kỳ ai.');
    }
}
