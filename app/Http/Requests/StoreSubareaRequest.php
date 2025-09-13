<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubareaRequest extends FormRequest
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
        return [
            'nombre' => 'required|string|max:100|unique:subarea,nombre',
            'id_especialidad' => 'required|integer|exists:especialidad,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la subárea es obligatorio.',
            'nombre.string' => 'El nombre de la subárea debe ser un texto.',
            'nombre.max' => 'El nombre de la subárea no puede tener más de 100 caracteres.',
            'nombre.unique' => 'Ya existe una subárea con este nombre.',
            'id_especialidad.required' => 'La especialidad es obligatoria.',
            'id_especialidad.integer' => 'La especialidad debe ser un número entero.',
            'id_especialidad.exists' => 'La especialidad seleccionada no es válida.',
        ];
    }
}
