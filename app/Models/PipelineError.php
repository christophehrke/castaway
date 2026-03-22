<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineError extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'recording_id',
        'stage',
        'error_code',
        'error_message',
        'error_context',
        'resolved_at',
        'created_at',
    ];

    protected $casts = [
        'error_context' => 'array',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function recording(): BelongsTo
    {
        return $this->belongsTo(Recording::class);
    }
}
