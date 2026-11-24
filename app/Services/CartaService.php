<?php

namespace App\Services;

use App\Exceptions\ErrorApi;
use App\Integraciones\ListaBuenosTraviesosAdapter;
use App\Models\Carta;
use Illuminate\Support\Facades\DB;

class CartaService
{
    public const TRANSICIONES = [
        'RECIBIDA' => ['APROBADA', 'RECHAZADA'],
        'APROBADA' => ['EN_PRODUCCION'],
        'EN_PRODUCCION' => ['ENTREGADA'],
        'ENTREGADA' => [],
        'RECHAZADA' => [],
    ];

    public function __construct(private ListaBuenosTraviesosAdapter $lista) {}

    public function registrar(array $datos): Carta
    {
        $hoy = now('America/Guayaquil');
        if ($hoy->month === 12 && $hoy->day > 24) {
            throw new ErrorApi(422, 'RECEPCION_CERRADA', 'La recepción de cartas cierra el 24 de diciembre.');
        }
        $carta = new Carta($datos);
        $carta->estado = 'RECIBIDA';
        $carta->comportamiento = 'PENDIENTE_VERIFICACION';
        $carta->fechaRecepcion = $hoy->toDateString();
        $carta->save();
        return $carta;
    }

    public function listar(array $filtros)
    {
        return Carta::where($filtros)->orderBy('id')->get()->map(function (Carta $carta) {
            $partes = preg_split('/\s+/u', trim($carta->remitente), -1, PREG_SPLIT_NO_EMPTY);
            $nombre = array_shift($partes);
            $carta->remitente = $nombre.($partes ? ' '.mb_substr($partes[0], 0, 1).'.' : '');
            return $carta->makeHidden('edad');
        });
    }

    public function detalle(string $id): Carta
    {
        return Carta::findOrFail($id);
    }

    public function editar(string $id, array $datos): Carta
    {
        return DB::transaction(function () use ($id, $datos) {
            $carta = Carta::lockForUpdate()->findOrFail($id);
            if ($carta->estado !== 'RECIBIDA') {
                throw new ErrorApi(409, 'CARTA_NO_EDITABLE', 'Solo se puede editar una carta RECIBIDA.');
            }
            $carta->update($datos);
            return $carta;
        });
    }

    public function eliminar(string $id): void
    {
        DB::transaction(function () use ($id) {
            $carta = Carta::lockForUpdate()->findOrFail($id);
            if ($carta->estado === 'ENTREGADA') {
                throw new ErrorApi(409, 'CARTA_ENTREGADA', 'Una carta entregada no se puede eliminar.');
            }
            $carta->delete();
        });
    }

    public function cambiarEstado(string $id, string $destino): Carta
    {
        $carta = DB::transaction(function () use ($id, $destino) {
            $carta = Carta::lockForUpdate()->findOrFail($id);
            if (!in_array($destino, self::TRANSICIONES[$carta->estado], true)) {
                throw new ErrorApi(409, 'TRANSICION_INVALIDA', "No se puede pasar de {$carta->estado} a {$destino}.");
            }
            if ($destino === 'APROBADA') {
                $resultado = $this->lista->consultar($carta->remitente, $carta->ciudad);
                $carta->comportamiento = $resultado ?? 'PENDIENTE_VERIFICACION';
                $destino = match ($resultado) {
                    'BUENO' => 'APROBADA',
                    'TRAVIESO' => 'RECHAZADA',
                    default => 'RECIBIDA',
                };
            }
            $carta->estado = $destino;
            $carta->save();
            return $carta;
        });
        if ($carta->estado === 'RECIBIDA') {
            throw new ErrorApi(503, 'LISTA_NO_DISPONIBLE', 'No se pudo verificar el comportamiento. La carta sigue recibida y se puede reintentar.');
        }
        return $carta;
    }
}
