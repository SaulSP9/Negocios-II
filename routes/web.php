<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\ScmController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/login', fn () => redirect('/?vista=login'))->name('login');
Route::get('/sesion', [AuthController::class, 'session']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/tienda/productos', [StoreController::class, 'products']);
Route::get('/tienda/comentarios', [StoreController::class, 'comments']);
Route::get('/tienda/subasta', [StoreController::class, 'auction']);
Route::post('/tienda/contacto', [StoreController::class, 'contact'])->middleware('throttle:5,1');
Route::post('/tienda/suscripciones', [StoreController::class, 'subscribe'])->middleware('throttle:5,1');
Route::middleware('auth')->group(function () {
    Route::get('/tienda/pedidos', [StoreController::class, 'orders']);
    Route::post('/tienda/pedidos', [StoreController::class, 'checkout'])->middleware('throttle:20,1');
    Route::get('/tienda/publicaciones', [StoreController::class, 'publications']);
    Route::post('/tienda/publicaciones', [StoreController::class, 'savePublication']);
    Route::put('/tienda/publicaciones/{publication}', [StoreController::class, 'savePublication']);
    Route::delete('/tienda/publicaciones/{publication}', [StoreController::class, 'deletePublication']);
    Route::post('/tienda/comentarios', [StoreController::class, 'comment'])->middleware('throttle:10,1');
    Route::post('/tienda/subastas/{auction}/ofertas', [StoreController::class, 'bid'])->middleware('throttle:20,1');
});
Route::middleware(['auth', 'role:admin,usuario'])->group(function () {
    Route::view('/crm', 'crm.index');
    Route::apiResource('clientes', CrmController::class)->parameters(['clientes' => 'cliente'])->except('destroy');
    Route::put('/clientes/{cliente}/etapa', [CrmController::class, 'etapa']);
    Route::post('/interacciones', [CrmController::class, 'interaction']);
    Route::get('/clientes/{cliente}/interacciones', [CrmController::class, 'history']);
    Route::post('/clientes/{cliente}/evaluaciones', [CrmController::class, 'evaluate']);
    Route::get('/metricas', [CrmController::class, 'metrics']);
    Route::get('/mi-actividad', [CrmController::class, 'activity']);
    Route::get('/contactos', [CrmController::class, 'contacts']);
    Route::post('/contactos/{id}/registrar', [CrmController::class, 'handleContact']);
});
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::delete('/clientes/{cliente}', [CrmController::class, 'destroy']);
    Route::get('/usuarios', [CrmController::class, 'users']);
    Route::post('/usuarios', [CrmController::class, 'createUser']);
    Route::get('/tienda/estadisticas', [StoreController::class, 'stats']);
    Route::post('/tienda/productos', [StoreController::class, 'saveProduct']);
    Route::put('/tienda/productos/{product}', [StoreController::class, 'saveProduct']);
    Route::patch('/tienda/productos/{product}/visibilidad', [StoreController::class, 'toggleProduct']);
    Route::put('/tienda/pedidos/{order}/estado', [StoreController::class, 'orderStatus']);
});

Route::middleware(['auth', 'role:admin,usuario'])->group(function () {
    Route::view('/scm', 'scm.index');
    Route::get('/productos', [ScmController::class, 'products']);
    Route::get('/productos/{producto}', [ScmController::class, 'product'])->withTrashed();
    Route::get('/productos/{producto}/movimientos', [ScmController::class, 'history'])->withTrashed();
    Route::get('/proveedores', [ScmController::class, 'suppliers']);
    Route::post('/inventario/movimiento', [ScmController::class, 'movement']);
    Route::get('/pedidos', [ScmController::class, 'orders']);
    Route::post('/pedidos', [ScmController::class, 'createOrder']);
    Route::put('/pedidos/{pedido}/estado', [ScmController::class, 'orderStatus']);
    Route::get('/scm/estado', [ScmController::class, 'state']);
    Route::get('/scm/reportes', [ScmController::class, 'reports']);
    Route::get('/scm/reportes/csv', [ScmController::class, 'export']);
});
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/productos', [ScmController::class, 'saveProduct']);
    Route::put('/productos/{producto}', [ScmController::class, 'saveProduct']);
    Route::delete('/productos/{producto}', [ScmController::class, 'deleteProduct']);
    Route::put('/productos/{producto}/estrategia', [ScmController::class, 'strategy']);
    Route::post('/proveedores', [ScmController::class, 'saveSupplier']);
    Route::put('/proveedores/{proveedor}', [ScmController::class, 'saveSupplier']);
    Route::delete('/proveedores/{proveedor}', [ScmController::class, 'deleteSupplier']);
    Route::put('/scm/nivel', [ScmController::class, 'maturity']);
});
