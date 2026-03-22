<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'engine' => $this->engine,
            'variant' => $this->variant,
            'version' => $this->version,
            'workflow_json' => $this->workflow_json,
            'node_count' => $this->node_count,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
