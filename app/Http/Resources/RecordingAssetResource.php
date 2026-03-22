<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordingAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'storage_path' => $this->storage_path,
            'mime_type' => $this->mime_type,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}
