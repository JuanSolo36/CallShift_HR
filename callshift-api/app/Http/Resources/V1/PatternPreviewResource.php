<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatternPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pattern' => [
                'id'                => $this['pattern']->id,
                'name'              => $this['pattern']->name,
                'code'              => $this['pattern']->code,
                'cycle_length_days' => (int) $this['pattern']->cycle_length_days,
            ],
            'version' => [
                'id'           => $this['version']->id,
                'lock_version' => (int) $this['version']->lock_version,
            ],
            'work_period' => [
                'id'         => $this['work_period']->id,
                'name'       => $this['work_period']->name,
                'start_date' => $this['work_period']->start_date->format('Y-m-d'),
                'end_date'   => $this['work_period']->end_date->format('Y-m-d'),
            ],
            'summary' => [
                'employees_count'      => $this['summary']['employees_count'],
                'total_days_in_period' => $this['summary']['total_days_in_period'],
                'total_assignments'    => $this['summary']['total_assignments'],
                'new_assignments'      => $this['summary']['new_assignments'],
                'overwritten_count'    => $this['summary']['overwritten_count'],
                'total_work_hours'     => (float) $this['summary']['total_work_hours'],
                'total_work_days'      => $this['summary']['total_work_days'],
                'total_rest_days'      => $this['summary']['total_rest_days'],
                'conflicts_count'      => count($this['conflicts'] ?? []),
            ],
            'conflicts'   => $this['conflicts'] ?? [],
            'projections' => $this['projections'] ?? [],
        ];
    }
}
