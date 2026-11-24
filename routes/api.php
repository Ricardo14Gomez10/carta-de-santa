<?php

use App\Http\Controllers\CartaController;
use App\Http\Middleware\ExigirJefe;
use Illuminate\Support\Facades\Route;

Route::pattern('id', '[0-9]+');
Route::controller(CartaController::class)->prefix('cartas')->group(function () {
    Route::get('/', 'listar');
    Route::post('/', 'registrar');
    Route::get('/{id}', 'detalle');
    Route::put('/{id}', 'editar');
    Route::middleware(ExigirJefe::class)->group(function () {
        Route::delete('/{id}', 'eliminar');
        Route::patch('/{id}/estado', 'cambiarEstado');
    });
});
