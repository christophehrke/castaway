<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'file_size_bytes' => $this->file_size_bytes,
            'duration_seconds' => $this->duration_seconds,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'assets' => RecordingAssetResource::collection($this->whenLoaded('assets')),
            'intent' => new AiIntentResource($this->whenLoaded('intent')),
            'workflows' => WorkflowResource::collection($this->whenLoaded('workflows')),
        ];
    }
}
