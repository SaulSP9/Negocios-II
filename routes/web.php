<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Api\MetricasController;
use App\Http\Controllers\InteraccionController;


// Ruta para procesar el formulario de nueva interacción
Route::post('/interacciones', [InteraccionController::class, 'store'])->name('interacciones.store');

// Ruta para la vista "Mi Actividad" (disponible para todos los autenticados)
Route::get('/mi-actividad', [InteraccionController::class, 'miActividad'])->name('mi_actividad');
// ...

Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [MetricasController::class, 'index'])->name('dashboard');
});

Route::get('/', function () {
    return view('welcome');
});

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas
Route::middleware('auth')->group(function () {
    
    // Rutas para cualquier usuario autenticado
    Route::resource('clientes', ClienteController::class);
    // Añade esta línea justo debajo de Route::resource('clientes', ...)
    Route::put('/clientes/{id}/etapa', [App\Http\Controllers\ClienteController::class, 'actualizarEtapa'])->name('clientes.etapa');
    
    // Rutas exclusivas para administradores
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // Ahora la ruta pasa por el controlador antes de abrir la vista
        Route::get('/dashboard', [MetricasController::class, 'index'])->name('dashboard');
    });
});