<?php

namespace App\Http\Requests;

use App\Services\CartaService;
use Illuminate\Validation\Rule;

class CambioEstado extends SolicitudJson
{
    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', Rule::in(array_keys(CartaService::TRANSICIONES))],
            'comportamiento' => ['missing'],
        ];
    }
}
