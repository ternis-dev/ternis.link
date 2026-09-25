<?php

namespace App\Notifications;

use App\Notifications\Channels\TernisAuthChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Security-relevant event for the affected user (API key created /
 * revoked, domain verified / deactivated, role or plan changed).
 * Always lands in the in-app inbox; email goes out only when the
 * user kept notify_security_email enabled.
 */
class SecurityAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly array $lines,
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionLabel = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', TernisAuthChannel::class];

        if ($notifiable->notify_security_email ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[ternis.link] '.$this->title)
            ->greeting('Hi '.$notifiable->name.',')
            ->lines($this->lines);

        if ($this->actionUrl !== null) {
            $mail->action($this->actionLabel ?? 'Open dashboard', $this->actionUrl);
        }

        return $mail->line('If this was not you, please contact us immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'lines' => $this->lines,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
        ];
    }

    public function toTernisAuth(object $notifiable): array
    {
        return [
            'recipient' => $notifiable->sso_sub ?? $notifiable->email,
            'subject' => $this->title,
            'body' => implode("\n", $this->lines),
            'action_url' => $this->actionUrl,
        ];
    }
}
