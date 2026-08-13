<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 2段階認証コードをメールで通知するクラス。
 *
 * ShouldQueueを実装し、メール送信をキュー経由の非同期処理にすることで、
 * SMTPサーバーが遅い/不通の場合でもログインのHTTPリクエスト自体が
 * ブロックされないようにする(QUEUE_CONNECTIONがsync以外の場合)。
 */
class TwoFactorCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $code  メールで送信する平文の認証コード
     * @return void
     */
    public function __construct(private readonly string $code)
    {
    }

    /**
     * 通知の送信チャンネルを指定する(メールのみ)。
     *
     * @param  object  $notifiable  通知対象のUser/Adminモデルインスタンス
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * メール本文を組み立てる。
     *
     * @param  object  $notifiable  通知対象のUser/Adminモデルインスタンス
     * @return MailMessage  認証コードを含むメールメッセージ
     */
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
