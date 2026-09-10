<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\InteraccionController;
use App\Http\Controllers\Api\MetricasController;
use App\Models\Cliente;

// Ruta principal
Route::get('/', function () {
    $clientes = Cliente::all();
    return view('welcome', compact('clientes'));
})->name('tienda');

// CRUD de Clientes (público / libre para pruebas)
Route::resource('clientes', ClienteController::class);
Route::put('/clientes/{id}/etapa', [ClienteController::class, 'updateEtapa'])->name('clientes.updateEtapa');

// Interacciones e historial
Route::post('/interacciones', [InteraccionController::class, 'store'])->name('interacciones.store');
Route::get('/mi-actividad', [InteraccionController::class, 'byCliente'])->name('interacciones.miActividad');

// Rutas autenticadas (Dashboard)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [MetricasController::class, 'dashboard'])->name('admin.dashboard');
});