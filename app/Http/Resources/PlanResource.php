<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'price_monthly_cents' => $this->price_monthly_cents,
            'price_yearly_cents' => $this->price_yearly_cents,
            'limits' => $this->limits,
            'sort_order' => $this->sort_order,
        ];
    }
}
