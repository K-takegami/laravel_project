<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $code)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('認証コードのお知らせ')
            ->line('ログインのための認証コードは以下の通りです。')
            ->line("認証コード: {$this->code}")
            ->line('このコードの有効期限は10分です。')
            ->line('心当たりがない場合は、このメールを無視してください。');
    }
}
