<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Position;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'employee_code'      => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('employees', 'employee_code')->where('company_id', $companyId),
            ],
            'document_type'      => ['required', 'string', 'max:20', 'in:CC,CE,TI,PASSPORT,OTHER,NIT'],
            'document_number'    => [
                'required',
                'string',
                'max:40',
                'alpha_dash',
                Rule::unique('employees', 'document_number')->where('company_id', $companyId),
            ],
            'first_name'         => ['required', 'string', 'max:60'],
            'middle_name'        => ['nullable', 'string', 'max:60'],
            'last_name'          => ['required', 'string', 'max:60'],
            'second_last_name'   => ['nullable', 'string', 'max:60'],
            'email'              => [
                'required',
                'email',
                'max:120',
                Rule::unique('employees', 'email')->where('company_id', $companyId),
            ],
            'personal_email'     => ['nullable', 'email', 'max:120'],
            'phone'              => ['nullable', 'string', 'max:30'],
            'birth_date'         => ['nullable', 'date', 'before:today'],
            'hire_date'          => ['required', 'date'],
            'termination_date'   => ['nullable', 'date', 'after_or_equal:hire_date'],
            'department_id'      => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'position_id'        => [
                'required',
                'integer',
                Rule::exists('positions', 'id')->where('company_id', $companyId),
            ],
            'employment_type_id' => [
                'required',
                'integer',
                Rule::exists('employment_types', 'id')->where('company_id', $companyId),
            ],
            'supervisor_id'      => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'status'             => ['nullable', 'string', 'in:ACTIVE,INACTIVE,ON_LEAVE,TERMINATED'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // Validar consistencia entre Cargo y Departamento si el cargo está restringido a un departamento
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
            'employee_code.required'        => 'El código de empleado es obligatorio.',
            'employee_code.unique'          => 'Este código de empleado ya se encuentra registrado en su empresa.',
            'document_number.required'      => 'El número de documento es obligatorio.',
            'document_number.unique'        => 'Este número de documento ya está registrado en su empresa.',
            'email.required'                => 'El correo electrónico laboral es obligatorio.',
            'email.unique'                  => 'Este correo electrónico ya está registrado para otro empleado en su empresa.',
            'department_id.exists'          => 'El departamento seleccionado no existe en su empresa.',
            'position_id.exists'            => 'El cargo seleccionado no existe en su empresa.',
            'employment_type_id.exists'     => 'El tipo de contrato seleccionado no existe en su empresa.',
            'supervisor_id.exists'          => 'El supervisor seleccionado no existe en su empresa.',
            'termination_date.after_or_equal'=> 'La fecha de retiro no puede ser anterior a la fecha de contratación.',
        ];
    }
}
