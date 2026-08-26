<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'module'      => $this->module,
            'code'        => $this->code,
            'name'        => $this->name,
            'description' => $this->description,
        ];
    }
}
