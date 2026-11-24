<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambioEstado;
use App\Http\Requests\DatosCarta;
use App\Services\CartaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartaController extends Controller
{
    public function __construct(private CartaService $cartas) {}

    public function listar(Request $request)
    {
        $filtros = $request->validate([
            'estado' => ['sometimes', 'string', Rule::in(array_keys(CartaService::TRANSICIONES))],
            'ciudad' => ['sometimes', 'required', 'string'],
        ]);
        return response()->json($this->cartas->listar($filtros));
    }

    public function registrar(DatosCarta $request)
    {
        $carta = $this->cartas->registrar($request->validated());
        return response()->json($carta, 201)->header('Location', url('/api/cartas/'.$carta->id));
    }

    public function detalle(string $id)
    {
        return response()->json($this->cartas->detalle($id));
    }

    public function editar(DatosCarta $request, string $id)
    {
        return response()->json($this->cartas->editar($id, $request->validated()));
    }

    public function eliminar(string $id)
    {
        $this->cartas->eliminar($id);
        return response()->noContent();
    }

    public function cambiarEstado(CambioEstado $request, string $id)
    {
        return response()->json($this->cartas->cambiarEstado($id, $request->validated('estado')));
    }
}
