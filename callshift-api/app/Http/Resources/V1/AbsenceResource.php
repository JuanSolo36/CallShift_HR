<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AbsenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $startDate = $this->start_date instanceof Carbon ? $this->start_date->format('Y-m-d') : (is_string($this->start_date) ? substr($this->start_date, 0, 10) : $this->start_date);
        $endDate = $this->end_date instanceof Carbon ? $this->end_date->format('Y-m-d') : (is_string($this->end_date) ? substr($this->end_date, 0, 10) : $this->end_date);

        return [
            'id'               => $this->id,
            'company_id'       => $this->company_id,
            'employee_id'      => $this->employee_id,
            'employee'         => $this->whenLoaded('employee', fn() => [
                'id'            => $this->employee->id,
                'full_name'     => $this->employee->full_name,
                'employee_code' => $this->employee->employee_code,
                'department'    => $this->employee->department?->name ?? 'N/A',
            ]),
            'leave_request_id' => $this->leave_request_id,
            'type'             => is_object($this->type) ? $this->type->value : $this->type,
            'type_label'       => is_object($this->type) && method_exists($this->type, 'label') ? $this->type->label() : $this->type,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'start_time'       => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time'         => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'is_full_day'      => (bool) $this->is_full_day,
            'reason'           => $this->reason,
            'status'           => is_object($this->status) ? $this->status->value : $this->status,
            'approved_by'      => $this->approved_by,
            'approver'         => $this->whenLoaded('approver', fn() => [
                'id'       => $this->approver->id,
                'username' => $this->approver->username,
                'email'    => $this->approver->email,
            ]),
            'approved_at'      => $this->approved_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
