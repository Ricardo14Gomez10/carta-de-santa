<?php

namespace App\Http\Requests;

use App\Exceptions\ErrorApi;
use Illuminate\Foundation\Http\FormRequest;

abstract class SolicitudJson extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (!$this->isJson() || !is_object(json_decode($this->getContent()))) {
            throw new ErrorApi(400, 'FORMATO_INVALIDO', 'Envía un objeto JSON válido con Content-Type application/json.');
        }
    }

    public function validationData(): array
    {
        return $this->json()->all();
    }

    public function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'string' => 'Se requiere texto.',
            'integer' => 'Se requiere un número entero.',
            'between' => 'El valor debe estar entre :min y :max.',
            'array' => 'Se requiere una lista.',
            'list' => 'Se requiere una lista ordenada.',
            'regalos.min' => 'Indica al menos un regalo.',
            'regalos.max' => 'Indica hasta cinco regalos.',
            'distinct' => 'Los regalos no se pueden repetir.',
            'missing' => 'Este campo lo asigna el sistema.',
            'in' => 'El estado indicado no existe.',
        ];
    }
}
