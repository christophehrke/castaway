<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageCounter extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'period',
        'recordings_count',
        'conversions_count',
        'storage_bytes',
        'ai_tokens_used',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function forCurrentPeriod(string $organizationId): self
    {
        return static::firstOrCreate(
            [
                'organization_id' => $organizationId,
                'period' => now()->format('Y-m'),
            ]
        );
    }
}
