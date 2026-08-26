<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'code'              => $this->code,
            'name'              => $this->name,
            'description'       => $this->description,
            'is_system'         => (bool) $this->is_system,
            'permissions_count' => $this->whenCounted('permissions', $this->permissions_count, function () {
                return $this->permissions ? $this->permissions->count() : 0;
            }),
            'permissions'       => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
