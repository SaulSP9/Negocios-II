<?php

// Solo se ejecuta desde run.cjs con una base SQLite temporal y aislada.
if (getenv('HF_UI_TEST') !== '1') {
    exit(1);
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$accounts = [];
foreach (['admin', 'ventas', 'logistica'] as $role) {
    $u = new App\Models\User(['name' => 'UI '.ucfirst($role), 'email' => $role.'@ui.example.test', 'password' => 'UiPassword123456!']);
    $u->role = $role;
    $u->save();
    $accounts[$role] = $u->id;
}
$client = App\Models\Cliente::create(['nombre' => 'Cliente UI existente', 'correo' => 'cliente@ui.example.test', 'estado' => 'activo', 'etapa_crm' => 'Prospecto']);
$supplier = App\Models\Proveedor::create(['nombre' => 'Proveedor UI existente', 'contacto' => 'Contacto UI', 'correo' => 'proveedor@ui.example.test', 'telefono' => '4491234567']);
$ids = [];
foreach (['PUSH', 'PULL'] as $strategy) {
    $p = App\Models\Product::create(['name' => 'UI '.$strategy, 'description' => 'Producto para pruebas de interfaz', 'category' => 'Hoodie',
        'price_cents' => $strategy === 'PUSH' ? 29900 : 19900, 'stock' => $strategy === 'PUSH' ? 4 : 10,
        'stock_minimo' => 3, 'stock_objetivo' => 12, 'proveedor_id' => $supplier->id, 'costo_unitario_cents' => 10000,
        'image' => 'https://example.test/p.jpg', 'estrategia_logistica' => $strategy, 'active' => true]);
    $ids[$strategy] = $p->id;
}
echo json_encode(['users' => $accounts, 'client' => $client->id, 'products' => $ids], JSON_THROW_ON_ERROR);
