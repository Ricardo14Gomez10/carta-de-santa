<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'proyecto' => 'Cartas a Santa y gestión de regalos',
    'cartas' => url('/api/cartas'),
]));
