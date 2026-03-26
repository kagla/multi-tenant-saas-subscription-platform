<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\UsageRecord;
use App\Notifications\UsageLimitWarning;
use Illuminate\Support\Facades\Cache;

class UsageTracker
{
    protected Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public static function for(Tenant $tenant): static
    {
        return new static($tenant);
    }

    public function track(string $metric, float $value = 1): UsageRecord
    {
        $record = UsageRecord::create([
            'tenant_id' => $this->tenant->id,
            'metric' => $metric,
            'value' => $value,
            'recorded_at' => now(),
        ]);

        $this->checkThresholds($metric);

        return $record;
    }

    public function getTodayUsage(string $metric): float
    {
        return (float) UsageRecord::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('metric', $metric)
            ->where('recorded_at', '>=', now()->startOfDay())
            ->sum('value');
    }

    public function getMonthlyUsage(string $metric): float
    {
        return (float) UsageRecord::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('metric', $metric)
            ->where('recorded_at', '>=', now()->startOfMonth())
            ->sum('value');
    }

    public function getDailyUsageForPeriod(string $metric, int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $records = UsageRecord::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('metric', $metric)
            ->where('recorded_at', '>=', $startDate)
            ->selectRaw("DATE(recorded_at) as date, SUM(value) as total")
            ->groupByRaw('DATE(recorded_at)')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[$date] = (float) ($records[$date] ?? 0);
        }

        return $result;
    }

    public function canUse(string $metric): bool
    {
        $limit = $this->getLimit($metric);

        if ($limit === PHP_INT_MAX) {
            return true;
        }

        $usage = $this->isDaily($metric)
            ? $this->getTodayUsage($metric)
            : $this->getTotalUsage($metric);

        return $usage < $limit;
    }

    public function getRemainingQuota(string $metric): float
    {
        $limit = $this->getLimit($metric);

        if ($limit === PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        $usage = $this->isDaily($metric)
            ? $this->getTodayUsage($metric)
            : $this->getTotalUsage($metric);

        return max(0, $limit - $usage);
    }

    public function getUsagePercent(string $metric): float
    {
        $limit = $this->getLimit($metric);

        if ($limit === PHP_INT_MAX) {
            return 0;
        }

        $usage = $this->isDaily($metric)
            ? $this->getTodayUsage($metric)
            : $this->getTotalUsage($metric);

        return $limit > 0 ? min(100, round($usage / $limit * 100, 1)) : 0;
    }

    public function getTotalUsage(string $metric): float
    {
        return (float) UsageRecord::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('metric', $metric)
            ->sum('value');
    }

    protected function getLimit(string $metric): int
    {
        return $this->tenant->getPlanLimit($metric);
    }

    protected function isDaily(string $metric): bool
    {
        return in_array($metric, ['api_calls_per_day']);
    }

    protected function checkThresholds(string $metric): void
    {
        $percent = $this->getUsagePercent($metric);
        $cacheKey = "usage_alert:{$this->tenant->id}:{$metric}";

        if ($percent >= 100 && !Cache::has("{$cacheKey}:100")) {
            $this->sendWarning($metric, 100, $percent);
            Cache::put("{$cacheKey}:100", true, $this->isDaily($metric) ? now()->endOfDay() : now()->addDay());
        } elseif ($percent >= 80 && !Cache::has("{$cacheKey}:80")) {
            $this->sendWarning($metric, 80, $percent);
            Cache::put("{$cacheKey}:80", true, $this->isDaily($metric) ? now()->endOfDay() : now()->addDay());
        }
    }

    protected function sendWarning(string $metric, int $threshold, float $currentPercent): void
    {
        $owner = $this->tenant->users()
            ->withoutGlobalScopes()
            ->where('role', 'owner')
            ->first();

        if ($owner) {
            $owner->notify(new UsageLimitWarning(
                $this->tenant,
                $metric,
                $threshold,
                $currentPercent,
            ));
        }
    }
}
