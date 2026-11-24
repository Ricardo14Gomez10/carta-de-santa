<?php

namespace App\Http\Requests;

class DatosCarta extends SolicitudJson
{
    public function rules(): array
    {
        return [
            'remitente' => ['required', 'string', 'between:2,60'],
            'edad' => ['required', 'integer:strict', 'between:1,17'],
            'ciudad' => ['required', 'string'],
            'regalos' => ['required', 'array', 'list', 'min:1', 'max:5'],
            'regalos.*' => ['required', 'string', 'distinct:ignore_case'],
            'id' => ['missing'],
            'fechaRecepcion' => ['missing'],
            'comportamiento' => ['missing'],
            'estado' => ['missing'],
        ];
    }
}
