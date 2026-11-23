<?php

use App\Http\Controllers\AtencionController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\OperacionController;
use App\Http\Controllers\MonederoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\ErpController;
use App\Http\Controllers\ScmController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'editorial')->name('home');
Route::view('/tienda/clasica', 'welcome');
Route::get('/catalogo', [CatalogoController::class, 'index']);
Route::get('/catalogo/{producto}', [CatalogoController::class, 'show'])->whereNumber('producto');
Route::get('/checkout', [CarritoController::class, 'checkoutPage'])->name('purchase.checkout');
Route::get('/promociones', [OperacionController::class, 'promotions']);
Route::get('/impuestos', [OperacionController::class, 'taxes']);
Route::post('/auth/registro', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/auth/recuperar', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::get('/auth/restablecer/{token}', [AuthController::class, 'showReset'])->name('password.reset');
Route::post('/auth/restablecer', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::post('/auth/demo', [AuthController::class, 'demo'])->middleware('throttle:6,1');

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/mi-monedero', [MonederoController::class, 'own']);
    Route::post('/mi-monedero/recargas', [MonederoController::class, 'recharge'])->middleware('throttle:10,1');
    Route::get('/auth/perfil', [AuthController::class, 'profile']);
    Route::put('/auth/perfil', [AuthController::class, 'updateProfile']);
    Route::get('/carrito/{cliente_id}', [CarritoController::class, 'show'])->whereNumber('cliente_id');
    Route::post('/carrito/importar', [CarritoController::class, 'merge'])->middleware('throttle:15,1');
    Route::post('/carrito/agregar', [CarritoController::class, 'store']);
    Route::put('/carrito/item/{item}', [CarritoController::class, 'update'])->whereNumber('item');
    Route::delete('/carrito/item/{item}', [CarritoController::class, 'destroy'])->whereNumber('item');
    Route::delete('/carrito/vaciar/{cliente_id}', [CarritoController::class, 'clear'])->whereNumber('cliente_id');
    Route::post('/pagos/procesar', [CarritoController::class, 'pay'])->middleware('throttle:20,1');
    Route::get('/compras/{order:folio}', [CarritoController::class, 'orderPage'])->name('purchase.receipt');
    Route::get('/pagos/{pago}', [CarritoController::class, 'payment'])->whereNumber('pago');
    Route::get('/atencion/tickets', [AtencionController::class, 'index']);
    Route::get('/atencion/ticket/{ticket}', [AtencionController::class, 'show'])->whereNumber('ticket');
    Route::post('/atencion/ticket', [AtencionController::class, 'store'])->middleware('throttle:10,1');
    Route::put('/atencion/ticket/{ticket}/respuesta', [AtencionController::class, 'answer'])->whereNumber('ticket')->middleware('permission:commerce.support');
    Route::get('/operacion/pedidos', [OperacionController::class, 'orders'])->middleware('permission:commerce.orders');
});
Route::middleware(['auth', 'permission:commerce.configure'])->group(function () {
    Route::post('/promociones', [OperacionController::class, 'savePromotion']);
    Route::put('/promociones/{promocion}', [OperacionController::class, 'savePromotion'])->whereNumber('promocion');
    Route::post('/impuestos', [OperacionController::class, 'saveTax']);
    Route::put('/impuestos/{impuesto}', [OperacionController::class, 'saveTax'])->whereNumber('impuesto');
    Route::get('/operacion/monederos/reporte', [MonederoController::class, 'report']);
    Route::get('/operacion/monederos/{cliente}', [MonederoController::class, 'history'])->whereNumber('cliente');
    Route::post('/operacion/monederos/{cliente}/ajustes', [MonederoController::class, 'adjust'])->whereNumber('cliente');
    Route::get('/operacion/monederos', [OperacionController::class, 'wallets']);
    Route::post('/operacion/monederos/{cliente}/abono', [OperacionController::class, 'credit'])->whereNumber('cliente');
});
Route::get('/gestion', function (Illuminate\Http\Request $request) {
    foreach (['erp.read' => 'operaciones', 'crm' => 'clientes', 'scm.read' => 'inventario'] as $permission => $section) {
        if ($request->user()->hasPermission($permission)) {
            return redirect('/gestion/'.$section);
        }
    }
    abort(403);
})->middleware('auth');
Route::get('/gestion/tienda', function (Illuminate\Http\Request $request) {
    abort_unless(collect(['commerce.configure', 'commerce.orders', 'commerce.support'])
        ->contains(fn ($permission) => $request->user()->hasPermission($permission)), 403);

    return view('editorial', ['initialView' => 'operacion']);
})->middleware('auth');
Route::get('/login', fn () => redirect('/?vista=login'))->name('login');
Route::get('/sesion', [AuthController::class, 'session']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/tienda/productos', [StoreController::class, 'products']);
Route::get('/tienda/comentarios', [StoreController::class, 'comments']);
Route::get('/tienda/subasta', [StoreController::class, 'auction']);
Route::post('/tienda/contacto', [StoreController::class, 'contact'])->middleware('throttle:5,1');
Route::post('/tienda/suscripciones', [StoreController::class, 'subscribe'])->middleware('throttle:5,1');
Route::middleware('auth')->group(function () {
    Route::get('/tienda/pedidos', [StoreController::class, 'orders']);
    Route::get('/tienda/pedidos/{order}', [StoreController::class, 'showOrder'])->whereNumber('order');
    Route::post('/tienda/pedidos', [StoreController::class, 'checkout'])->middleware('throttle:20,1');
    Route::get('/tienda/publicaciones', [StoreController::class, 'publications']);
    Route::post('/tienda/publicaciones', [StoreController::class, 'savePublication']);
    Route::put('/tienda/publicaciones/{publication}', [StoreController::class, 'savePublication']);
    Route::delete('/tienda/publicaciones/{publication}', [StoreController::class, 'deletePublication']);
    Route::post('/tienda/comentarios', [StoreController::class, 'comment'])->middleware('throttle:10,1');
    Route::post('/tienda/subastas/{auction}/ofertas', [StoreController::class, 'bid'])->middleware('throttle:20,1');
});
Route::middleware(['auth', 'permission:crm'])->group(function () {
    Route::view('/crm', 'crm.index');
    Route::view('/gestion/clientes', 'crm.index');
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

Route::middleware(['auth', 'permission:scm.read'])->group(function () {
    Route::view('/scm', 'scm.index');
    Route::view('/gestion/inventario', 'scm.index');
    Route::get('/productos', [ScmController::class, 'products']);
    Route::get('/productos/{producto}', [ScmController::class, 'product'])->withTrashed();
    Route::get('/productos/{producto}/movimientos', [ScmController::class, 'history'])->withTrashed();
    Route::get('/proveedores', [ScmController::class, 'suppliers']);
    Route::get('/pedidos', [ScmController::class, 'orders']);
    Route::get('/scm/estado', [ScmController::class, 'state']);
    Route::get('/scm/reportes', [ScmController::class, 'reports']);
    Route::get('/scm/reportes/csv', [ScmController::class, 'export']);
});
Route::middleware(['auth', 'permission:scm.operate'])->group(function () {
    Route::post('/inventario/movimiento', [ScmController::class, 'movement']);
    Route::post('/pedidos', [ScmController::class, 'createOrder']);
    Route::put('/pedidos/{pedido}/estado', [ScmController::class, 'orderStatus']);
});
Route::middleware(['auth', 'permission:scm.manage'])->group(function () {
    Route::post('/productos', [ScmController::class, 'saveProduct']);
    Route::put('/productos/{producto}', [ScmController::class, 'saveProduct']);
    Route::delete('/productos/{producto}', [ScmController::class, 'deleteProduct']);
    Route::put('/productos/{producto}/restaurar', [ScmController::class, 'restoreProduct'])->withTrashed();
    Route::put('/productos/{producto}/estrategia', [ScmController::class, 'strategy']);
    Route::post('/proveedores', [ScmController::class, 'saveSupplier']);
    Route::put('/proveedores/{proveedor}', [ScmController::class, 'saveSupplier']);
    Route::delete('/proveedores/{proveedor}', [ScmController::class, 'deleteSupplier']);
});
Route::put('/scm/nivel', [ScmController::class, 'maturity'])->middleware(['auth', 'role:admin']);

Route::middleware(['auth', 'permission:erp.read'])->group(function () {
    Route::view('/erp', 'erp.index');
    Route::view('/gestion/operaciones', 'erp.index');
    Route::get('/ordenes', [ErpController::class, 'index']);
    Route::get('/ordenes/{orden}', [ErpController::class, 'show'])->whereNumber('orden');
    Route::put('/ordenes/{orden}/estado', [ErpController::class, 'state'])->whereNumber('orden');
    Route::get('/erp/metricas', [ErpController::class, 'metrics']);
    Route::get('/erp/estado', [ErpController::class, 'implementation']);
    Route::get('/erp/recursos', [ErpController::class, 'resources']);
    Route::get('/erp/reportes', [ErpController::class, 'reports']);
    Route::get('/erp/reportes/csv', [ErpController::class, 'export']);
    Route::get('/erp/reportes/{reporte}/csv', [ErpController::class, 'exportReport'])->whereNumber('reporte');
    Route::post('/erp/reportes', [ErpController::class, 'generateReport']);
});
Route::middleware(['auth', 'permission:erp.write'])->group(function () {
    Route::post('/ordenes', [ErpController::class, 'store']);
    Route::post('/ordenes/procesar', [ErpController::class, 'process']);
});
Route::middleware(['auth', 'permission:erp.resources'])->group(function () {
    Route::post('/erp/recursos', [ErpController::class, 'saveResource']);
    Route::put('/erp/recursos/{recurso}', [ErpController::class, 'saveResource']);
    Route::get('/erp/responsables', [ErpController::class, 'resourceOwners']);
});
Route::middleware(['auth', 'permission:erp.admin'])->group(function () {
    Route::put('/erp/estado', [ErpController::class, 'updateImplementation']);
});
Route::middleware(['auth', 'permission:users'])->group(function () {
    Route::put('/usuarios/rol', [UserRoleController::class, 'update']);
    Route::post('/usuarios/rol', [UserRoleController::class, 'update']);
});
