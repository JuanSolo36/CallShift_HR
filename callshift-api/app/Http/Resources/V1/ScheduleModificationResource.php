<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleModificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'schedule_version_id'    => $this->schedule_version_id,
            'schedule_assignment_id' => $this->schedule_assignment_id,
            'employee_id'            => $this->employee_id,
            'employee'               => $this->whenLoaded('employee', fn() => [
                'id'            => $this->employee->id,
                'first_name'    => $this->employee->first_name,
                'last_name'     => $this->employee->last_name,
                'employee_code' => $this->employee->employee_code,
                'document_type' => $this->employee->document_type,
                'document_number' => $this->employee->document_number,
            ]),
            'modification_type'      => is_object($this->modification_type) ? $this->modification_type->value : $this->modification_type,
            'modification_type_label' => is_object($this->modification_type) ? $this->modification_type->label() : null,
            'previous_data'          => $this->previous_data,
            'new_data'               => $this->new_data,
            'reason'                 => $this->reason,
            'created_by'             => $this->created_by,
            'creator'                => $this->whenLoaded('creator', fn() => [
                'id'       => $this->creator->id,
                'username' => $this->creator->username,
                'email'    => $this->creator->email,
            ]),
            'approved_by'            => $this->approved_by,
            'approver'               => $this->whenLoaded('approver', fn() => [
                'id'       => $this->approver->id,
                'username' => $this->approver->username,
                'email'    => $this->approver->email,
            ]),
            'evidences'              => ModificationEvidenceResource::collection($this->whenLoaded('evidences')),
            'evidences_count'        => $this->evidences ? $this->evidences->count() : 0,
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }
}
