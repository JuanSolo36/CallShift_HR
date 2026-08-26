<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'legal_name'      => $this->legal_name,
            'tax_id'          => $this->tax_id,
            'slug'            => $this->slug,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'address'         => $this->address,
            'city'            => $this->city,
            'country'         => $this->country,
            'timezone'        => $this->timezone,
            'currency'        => $this->currency,
            'date_format'     => $this->date_format,
            'logo'            => $this->logo,
            'primary_color'   => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'status'          => $this->status,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
