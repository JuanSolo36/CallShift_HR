<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class WorkPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $startDate = $this->start_date instanceof Carbon ? $this->start_date : Carbon::parse($this->start_date);
        $endDate = $this->end_date instanceof Carbon ? $this->end_date : Carbon::parse($this->end_date);
        $durationDays = $startDate->diffInDays($endDate) + 1;

        return [
            'id'                 => $this->id,
            'company_id'         => $this->company_id,
            'department_id'      => $this->department_id,
            'department'         => $this->whenLoaded('department', function () {
                return $this->department ? [
                    'id'   => $this->department->id,
                    'name' => $this->department->name,
                    'code' => $this->department->code,
                ] : null;
            }),
            'name'               => $this->name,
            'period_type'        => is_object($this->period_type) ? $this->period_type->value : $this->period_type,
            'start_date'         => $startDate->format('Y-m-d'),
            'end_date'           => $endDate->format('Y-m-d'),
            'duration_days'      => (int) $durationDays,
            'status'             => is_object($this->status) ? $this->status->value : $this->status,
            'status_label'       => is_object($this->status) && method_exists($this->status, 'label') ? $this->status->label() : (string) $this->status,
            'current_version_id' => $this->current_version_id,
            'current_version'    => $this->whenLoaded('currentVersion', function () {
                return $this->currentVersion ? [
                    'id'                    => $this->currentVersion->id,
                    'version_number'        => $this->currentVersion->version_number,
                    'status'                => is_object($this->currentVersion->status) ? $this->currentVersion->status->value : $this->currentVersion->status,
                    'lock_version'          => $this->currentVersion->lock_version,
                    'score'                 => $this->currentVersion->score,
                    'hard_conflicts_count'  => $this->currentVersion->hard_conflicts_count,
                    'soft_conflicts_count'  => $this->currentVersion->soft_conflicts_count,
                ] : null;
            }),
            'versions_count'     => $this->whenCounted('versions', $this->versions_count ?? ($this->relationLoaded('versions') ? $this->versions->count() : $this->versions()->count())),
            'created_by'         => $this->created_by,
            'creator'            => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id'       => $this->creator->id,
                    'username' => $this->creator->username,
                    'email'    => $this->creator->email,
                ] : null;
            }),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
