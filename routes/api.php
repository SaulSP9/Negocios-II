<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\InteraccionController;
use App\Http\Controllers\Api\MetricasController;


// Si tienes un middleware de autenticación de API como auth:sanctum, colócalo dentro
Route::put('/clientes/{id}/etapa', [ClienteController::class, 'actualizarEtapa']);
// Endpoints REST según requerimientos
Route::get('/clientes', [ClienteController::class, 'index']);
Route::post('/clientes', [ClienteController::class, 'store']);
Route::get('/clientes/{id}', [ClienteController::class, 'show']);
Route::put('/clientes/{id}', [ClienteController::class, 'update']);
Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);
Route::put('/clientes/{id}/etapa', [ClienteController::class, 'updateEtapa']);

Route::post('/interacciones', [InteraccionController::class, 'store']);
Route::get('/clientes/{id}/interacciones', [InteraccionController::class, 'getByCliente']);

Route::get('/metricas', [MetricasController::class, 'dashboard']);