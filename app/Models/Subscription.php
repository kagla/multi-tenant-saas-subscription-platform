<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy(TenantScope::class)]
class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'stripe_id',
        'stripe_status',
        'stripe_plan',
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'stripe_id' => 'encrypted',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
