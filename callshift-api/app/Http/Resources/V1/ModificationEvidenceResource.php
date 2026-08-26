<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModificationEvidenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'schedule_modification_id' => $this->schedule_modification_id,
            'original_name'            => $this->original_name,
            'mime_type'                => $this->mime_type,
            'file_size_bytes'          => $this->file_size_bytes,
            'file_size_human'          => $this->human_readable_size,
            'sha256_hash'              => $this->sha256_hash,
            'is_image'                 => $this->isImage(),
            'is_pdf'                   => $this->isPdf(),
            'download_url'             => route('schedule-modifications.evidences.download', [
                'id'         => $this->schedule_modification_id,
                'evidenceId' => $this->id,
            ]),
            'uploaded_by'              => $this->whenLoaded('uploader', fn() => [
                'id'       => $this->uploader->id,
                'username' => $this->uploader->username,
                'email'    => $this->uploader->email,
            ]),
            'created_at'               => $this->created_at?->toIso8601String(),
        ];
    }
}
