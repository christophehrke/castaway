<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiIntent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'recording_id',
        'version',
        'status',
        'title',
        'description',
        'steps',
        'raw_transcript',
        'raw_vision_data',
        'model_used',
        'tokens_used',
        'processing_started_at',
        'processing_completed_at',
    ];

    protected $casts = [
        'steps' => 'array',
        'raw_vision_data' => 'array',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
    ];

    public function recording(): BelongsTo
    {
        return $this->belongsTo(Recording::class);
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }
}
