<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Position;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $target = $this->route('position');
        $positionId = $target instanceof Position ? $target->id : ($this->route('id') ?? $target);
        $companyId = $this->user()?->company_id;

        return [
            'name'          => ['sometimes', 'required', 'string', 'max:100'],
            'code'          => [
                'sometimes',
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('positions', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($positionId),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'description'   => ['nullable', 'string', 'max:500'],
            'status'        => ['sometimes', 'required', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'          => 'Este código de cargo ya está en uso en su empresa.',
            'department_id.exists' => 'El departamento seleccionado no existe en su empresa.',
        ];
    }
}
