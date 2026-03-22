<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiIntentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'status' => $this->status,
            'title' => $this->title,
            'description' => $this->description,
            'steps' => $this->steps,
            'model_used' => $this->model_used,
            'created_at' => $this->created_at,
        ];
    }
}
