<?php

namespace App\Notifications;

use App\Notifications\Channels\TernisAuthChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A 5xx error encounter, fanned out to admins (mail gated by each
 * admin's notify_server_error_email preference). Dispatch is
 * throttled in App\Support\Notifier so an error storm notifies once
 * per window instead of once per exception.
 */
class ServerErrorAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $httpCode,
        public readonly string $exceptionClass,
        public readonly string $method,
        public readonly string $host,
        public readonly string $path,
        public readonly string $message,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', TernisAuthChannel::class];

        if ($notifiable->notify_server_error_email ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[ternis.link admin] {$this->httpCode} on {$this->host}{$this->path}")
            ->greeting('Hi '.$notifiable->name.',')
            ->line("A {$this->httpCode} error was rendered for {$this->method} {$this->host}{$this->path}.")
            ->line('Exception: '.$this->exceptionClass)
            ->line($this->message !== '' ? $this->message : '(no message)');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->httpCode} on {$this->host}{$this->path}",
            'lines' => [
                "A {$this->httpCode} error was rendered for {$this->method} {$this->host}{$this->path}.",
                'Exception: '.$this->exceptionClass,
                $this->message !== '' ? $this->message : '(no message)',
            ],
            'action_url' => null,
            'action_label' => null,
        ];
    }

    public function toTernisAuth(object $notifiable): array
    {
        return [
            'recipient' => $notifiable->sso_sub ?? $notifiable->email,
            'subject' => "{$this->httpCode} on {$this->host}{$this->path}",
            'body' => $this->exceptionClass.': '.$this->message,
            'action_url' => null,
        ];
    }
}
