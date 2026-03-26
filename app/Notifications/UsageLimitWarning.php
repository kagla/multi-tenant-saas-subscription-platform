<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UsageLimitWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Tenant $tenant,
        protected string $metric,
        protected int $threshold,
        protected float $currentPercent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $metricLabel = str_replace('_', ' ', ucfirst($this->metric));
        $isExceeded = $this->threshold >= 100;

        $message = (new MailMessage)
            ->subject(($isExceeded ? '[Action Required] ' : '') . "{$metricLabel} usage at {$this->threshold}%")
            ->greeting("Hello {$notifiable->name},");

        if ($isExceeded) {
            $message->line("Your organization **{$this->tenant->name}** has reached the **{$metricLabel}** limit on the **" . ucfirst($this->tenant->plan) . "** plan.")
                ->line('Further usage of this resource may be restricted.')
                ->action('Upgrade Plan', url('/subscription/plans'));
        } else {
            $message->line("Your organization **{$this->tenant->name}** has used **{$this->currentPercent}%** of its **{$metricLabel}** allowance on the **" . ucfirst($this->tenant->plan) . "** plan.")
                ->line('Consider upgrading to avoid service interruption.')
                ->action('View Usage', url('/subscription'));
        }

        return $message->line('Thank you for using our platform.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'metric' => $this->metric,
            'threshold' => $this->threshold,
            'current_percent' => $this->currentPercent,
            'plan' => $this->tenant->plan,
        ];
    }
}
