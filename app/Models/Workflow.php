<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workflow extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'recording_id',
        'ai_intent_id',
        'organization_id',
        'engine',
        'variant',
        'version',
        'workflow_json',
        'node_count',
        'status',
    ];

    protected $casts = [
        'workflow_json' => 'array',
    ];

    public function recording(): BelongsTo
    {
        return $this->belongsTo(Recording::class);
    }

    public function aiIntent(): BelongsTo
    {
        return $this->belongsTo(AiIntent::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
