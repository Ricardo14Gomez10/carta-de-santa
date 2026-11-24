<?php

use App\Exceptions\ErrorApi;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware()
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([ErrorApi::class]);
        $exceptions->render(function (Throwable $error, Request $request) {
            if (!$request->is('api', 'api/*')) {
                return null;
            }
            $detalles = [];
            $headers = [];
            if ($error instanceof ErrorApi) {
                [$status, $codigo, $mensaje] = [$error->status, $error->codigo, $error->getMessage()];
            } elseif ($error instanceof ValidationException) {
                [$status, $codigo, $mensaje] = [422, 'DATOS_INVALIDOS', 'Revisa los campos indicados.'];
                $detalles = $error->errors();
            } else {
                $status = $error instanceof HttpExceptionInterface ? $error->getStatusCode() : 500;
                $headers = $error instanceof HttpExceptionInterface ? $error->getHeaders() : [];
                [$codigo, $mensaje] = match ($status) {
                    400 => ['FORMATO_INVALIDO', 'El formato de la solicitud es inválido.'],
                    404 => ['NO_ENCONTRADO', 'El recurso solicitado no existe.'],
                    405 => ['METODO_INVALIDO', 'Método no permitido en esta ruta.'],
                    default => ['ERROR_HTTP', 'No se pudo completar la solicitud.'],
                };
            }
            return response()->json(compact('codigo', 'mensaje', 'detalles') + [
                'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
                'ruta' => $request->getPathInfo(),
            ], $status, $headers);
        });
    })->create();
