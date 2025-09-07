<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Especialidade;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class UpdateSeccionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $seccion = $this->route('seccion');
        $seccionId = $seccion->id;
        return [
            'nombre' => 'required|string|max:55|unique:seccione,nombre,' . $seccionId . ',id,condicion,1',
            'id_institucion' => 'required|exists:institucione,id',
            'especialidades' => 'nullable|array',
            'especialidades.*' => 'exists:especialidad,id',
        ];  
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la sección es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto válido.',
            'nombre.max' => 'El nombre no puede exceder los 55 caracteres.',
            'nombre.unique' => 'Ya existe una sección con este nombre.',
            'id_institucion.required' => 'La institución es obligatoria.',
            'id_institucion.exists' => 'La institución seleccionada no es válida.',
            'especialidades.required' => 'Debe seleccionar al menos una especialidad.',
            'especialidades.array' => 'Las especialidades deben ser un listado válido.',
            'especialidades.min' => 'Debe seleccionar al menos una especialidad.',
            'especialidades.*.required' => 'Cada especialidad debe ser válida.',
            'especialidades.*.exists' => 'Una de las especialidades seleccionadas no existe.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Sin validaciones adicionales - solo las básicas
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $seccion = $this->route('seccion');
        $response = redirect()->back()
            ->withErrors($validator)
            ->withInput()
            ->with('modal_editar_id', $seccion->id);
        
        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }

}
