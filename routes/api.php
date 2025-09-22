<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\MarcacionController;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/empleados/toggle', [MovimientoController::class, 'toggleEstadoAPI']);

Route::get('/empleados/listar', [MovimientoController::class, 'index']);

Route::post('marcaciones/download', [MarcacionController::class, 'download']);
