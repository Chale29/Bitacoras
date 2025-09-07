<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventoRequest extends FormRequest
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

        'id_seccion' => 'required|exists:seccione,id',
        'id_subarea' => 'required|exists:subarea,id',
        'id_horario' => 'required|exists:horarios,id',
        'id_institucion' => 'required|exists:institucione,id',
        'observacion' => 'required|string|max:500',
        'prioridad' => 'required|in:alta,media,regular,baja',
    ];
}


    public function messages(): array
    {
        return [
            'id_bitacora.required' => 'La bitácora es obligatoria.',
            'id_bitacora.exists' => 'La bitácora seleccionada no existe.',
            'id_seccion.required' => 'La sección es obligatoria.',
            'id_seccion.exists' => 'La sección seleccionada no existe.',
            'id_subarea.required' => 'La subárea es obligatoria.',
            'id_subarea.exists' => 'La subárea seleccionada no existe.',
            'id_horario.required' => 'El horario es obligatorio.',
            'id_horario.exists' => 'El horario seleccionado no existe.',
            'prioridad.required' => 'La prioridad es obligatoria.',
            'prioridad.in' => 'La prioridad debe ser alta, media, regular o baja.',
            'observacion.required' => 'La observación es obligatoria.',
            'observacion.string' => 'La observación debe ser texto.',
            'observacion.max' => 'La observación no puede exceder los 500 caracteres.',
        ];
    }
}
