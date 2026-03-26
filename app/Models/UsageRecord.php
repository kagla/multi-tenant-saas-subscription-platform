<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy(TenantScope::class)]
class UsageRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'metric',
        'value',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'recorded_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
