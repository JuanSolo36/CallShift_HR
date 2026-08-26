<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    protected string $token;

    public function __construct($resource, string $token)
    {
        parent::__construct($resource);
        $this->token = $token;
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'token'      => $this->token,
            'token_type' => 'Bearer',
            'user'       => new UserResource($this->resource),
        ];
    }
}
