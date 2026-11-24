<?php

namespace App\Http\Middleware;

use App\Exceptions\ErrorApi;
use Closure;
use Illuminate\Http\Request;

class ExigirJefe
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if ($token && hash_equals((string) config('polo.jefe'), $token)) {
            return $next($request);
        }
        if ($token && hash_equals((string) config('polo.elfo'), $token)) {
            throw new ErrorApi(403, 'ROL_INSUFICIENTE', 'Se requiere el rol ELFO_JEFE.');
        }
        throw new ErrorApi(401, 'TOKEN_INVALIDO', 'Falta un token Bearer válido.');
    }
}
