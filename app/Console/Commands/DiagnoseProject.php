<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DiagnoseProject extends Command
{
    protected $signature = 'hf:diagnostico';

    protected $description = 'Comprueba conexión, tablas CRM/SCM/ERP, rutas, cuenta administradora y archivos compilados.';

    public function handle(): int
    {
        $ok = true;
        $check = function (string $name, bool $value) use (&$ok) {
            $value ? $this->info('[OK] '.$name) : $this->error('[FALTA] '.$name);
            $ok = $ok && $value;
        };
        $check('PHP 8.2 o superior', PHP_VERSION_ID >= 80200);
        $check('Clave de aplicación', filled(config('app.key')));
        $compiled = file_exists(public_path('build/manifest.json'));
        $check('Archivos de tienda compilados', $compiled);
        if (! $compiled) {
            $this->line('Para compilar los recursos del clon, ejecuta npm ci y npm run build.');
        }
        try {
            DB::connection()->getPdo();
            $this->line('Conexión: '.config('database.default').' | Base: '.DB::connection()->getDatabaseName());
            foreach (['users', 'clientes', 'interacciones', 'evaluaciones', 'products', 'proveedores', 'inventarios', 'movimientos_inventario', 'pedidos_scm',
                'ordenes_empresariales', 'orden_productos', 'orden_historial', 'procesos_internos', 'recursos_erp', 'reportes_erp', 'estado_erp'] as $table) {
                $check('Tabla '.$table, Schema::hasTable($table));
            }
            $check('Campos SCM del catálogo', Schema::hasColumns('products', ['proveedor_id', 'stock_minimo', 'stock_objetivo', 'costo_unitario_cents', 'inventory_version', 'deleted_at']));
            $check('Vínculo ERP de movimientos', Schema::hasColumn('movimientos_inventario', 'orden_empresarial_id'));
            $check('Cuenta administradora', Schema::hasTable('users') && Schema::hasColumn('users', 'role') && User::where('role', 'admin')->exists());
        } catch (\Throwable $e) {
            $this->error('No se pudo comprobar la base: '.$e->getMessage());
            $ok = false;
        }
        $routes = collect(Route::getRoutes())->map(fn ($r) => $r->uri())->all();
        foreach (['crm', 'scm', 'erp', 'clientes', 'productos', 'proveedores', 'ordenes', 'ordenes/procesar', 'erp/metricas', 'erp/estado', 'auth/login', 'usuarios/rol'] as $uri) {
            $check('Ruta /'.$uri, in_array($uri, $routes, true));
        }
        $this->line($ok ? 'Proyecto preparado para la revisión funcional.' : 'Consulta docs/TIENDA_U4.md para resolver los puntos marcados como FALTA.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
