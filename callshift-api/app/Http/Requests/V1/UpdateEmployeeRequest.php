<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Employee;
use App\Models\Position;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $target = $this->route('employee');
        $employeeId = $target instanceof Employee ? $target->id : ($this->route('id') ?? $target);
        $companyId = $this->user()?->company_id;

        return [
            'employee_code'      => [
                'sometimes',
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('employees', 'employee_code')
                    ->where('company_id', $companyId)
                    ->ignore($employeeId),
            ],
            'document_type'      => ['sometimes', 'required', 'string', 'max:20', 'in:CC,CE,TI,PASSPORT,OTHER,NIT'],
            'document_number'    => [
                'sometimes',
                'required',
                'string',
                'max:40',
                'alpha_dash',
                Rule::unique('employees', 'document_number')
                    ->where('company_id', $companyId)
                    ->ignore($employeeId),
            ],
            'first_name'         => ['sometimes', 'required', 'string', 'max:60'],
            'middle_name'        => ['nullable', 'string', 'max:60'],
            'last_name'          => ['sometimes', 'required', 'string', 'max:60'],
            'second_last_name'   => ['nullable', 'string', 'max:60'],
            'email'              => [
                'sometimes',
                'required',
                'email',
                'max:120',
                Rule::unique('employees', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($employeeId),
            ],
            'personal_email'     => ['nullable', 'email', 'max:120'],
            'phone'              => ['nullable', 'string', 'max:30'],
            'birth_date'         => ['nullable', 'date', 'before:today'],
            'hire_date'          => ['sometimes', 'required', 'date'],
            'termination_date'   => ['nullable', 'date', 'after_or_equal:hire_date'],
            'department_id'      => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'position_id'        => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('positions', 'id')->where('company_id', $companyId),
            ],
            'employment_type_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('employment_types', 'id')->where('company_id', $companyId),
            ],
            'supervisor_id'      => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'status'             => ['sometimes', 'required', 'string', 'in:ACTIVE,INACTIVE,ON_LEAVE,TERMINATED'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $target = $this->route('employee');
            $employeeId = $target instanceof Employee ? $target->id : (int) ($this->route('id') ?? $target);

            // 1. Prevenir auto-supervisión
            $supervisorId = $this->input('supervisor_id');
            if ($supervisorId && (int) $supervisorId === (int) $employeeId) {
                $v->errors()->add('supervisor_id', 'Un empleado no puede ser su propio supervisor.');
            }

            // 2. Validar coherencia Cargo - Departamento
            $positionId = $this->input('position_id');
            $departmentId = $this->input('department_id');

            if ($positionId && $departmentId) {
                $position = Position::where('company_id', $this->user()?->company_id)->find($positionId);
                if ($position && $position->department_id && $position->department_id != $departmentId) {
                    $v->errors()->add('position_id', 'El cargo seleccionado pertenece a un departamento diferente.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'employee_code.unique'   => 'Este código de empleado ya está en uso en su empresa.',
            'document_number.unique' => 'Este número de documento ya está registrado en su empresa.',
            'email.unique'           => 'Este correo electrónico ya está en uso en su empresa.',
        ];
    }
}
