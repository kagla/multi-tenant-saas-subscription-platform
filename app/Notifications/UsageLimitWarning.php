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
            ->subject(($isExceeded ? '[조치 필요] ' : '') . "{$metricLabel} 사용량 {$this->threshold}% 도달")
            ->greeting("안녕하세요 {$notifiable->name}님,");

        if ($isExceeded) {
            $message->line("조직 **{$this->tenant->name}**이(가) **" . ucfirst($this->tenant->plan) . "** 플랜의 **{$metricLabel}** 한도에 도달했습니다.")
                ->line('이 리소스의 추가 사용이 제한될 수 있습니다.')
                ->action('플랜 업그레이드', url('/subscription/plans'));
        } else {
            $message->line("조직 **{$this->tenant->name}**이(가) **" . ucfirst($this->tenant->plan) . "** 플랜의 **{$metricLabel}** 허용량 중 **{$this->currentPercent}%**를 사용했습니다.")
                ->line('서비스 중단을 방지하려면 업그레이드를 고려해 주세요.')
                ->action('사용량 확인', url('/subscription'));
        }

        return $message->line('저희 플랫폼을 이용해 주셔서 감사합니다.');
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
