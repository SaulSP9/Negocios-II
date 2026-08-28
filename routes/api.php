<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\InteraccionController;
use App\Http\Controllers\Api\MetricasController;

Route::get('/clientes', [ClienteController::class, 'index']);
Route::post('/clientes', [ClienteController::class, 'store']);
Route::get('/clientes/{id}', [ClienteController::class, 'show']);
Route::put('/clientes/{id}', [ClienteController::class, 'update']);
Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);
Route::put('/clientes/{id}/etapa', [ClienteController::class, 'cambiarEtapa']);

Route::post('/interacciones', [InteraccionController::class, 'store']);
Route::get('/clientes/{id}/interacciones', [InteraccionController::class, 'porCliente']);

Route::get('/metricas', [MetricasController::class, 'index']);