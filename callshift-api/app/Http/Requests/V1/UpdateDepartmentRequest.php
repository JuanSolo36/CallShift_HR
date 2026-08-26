<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Department;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $target = $this->route('department');
        $departmentId = $target instanceof Department ? $target->id : ($this->route('id') ?? $target);
        $companyId = $this->user()?->company_id;

        return [
            'name'             => ['sometimes', 'required', 'string', 'max:100'],
            'code'             => [
                'sometimes',
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('departments', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($departmentId),
            ],
            'cost_center_code' => ['nullable', 'string', 'max:30', 'alpha_dash'],
            'description'      => ['nullable', 'string', 'max:500'],
            'manager_id'       => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'status'           => ['sometimes', 'required', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'        => 'Este código de departamento ya está en uso en su empresa.',
            'manager_id.exists'  => 'El gerente o responsable seleccionado no existe en su empresa.',
        ];
    }
}
