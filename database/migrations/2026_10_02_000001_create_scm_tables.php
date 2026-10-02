<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 120);
            $t->string('contacto', 120);
            $t->string('correo')->unique();
            $t->string('telefono', 25);
            $t->timestamps();
        });
        Schema::table('products', function (Blueprint $t) {
            $t->foreignId('proveedor_id')->nullable()->constrained('proveedores')->restrictOnDelete();
            $t->unsignedInteger('stock_minimo')->default(0);
            $t->unsignedInteger('stock_objetivo')->default(1);
            $t->unsignedInteger('costo_unitario_cents')->default(0);
            $t->string('estrategia_logistica')->default('PULL')->index();
            $t->unsignedInteger('inventory_version')->default(0);
            $t->softDeletes();
        });
        Schema::create('inventarios', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->unique()->constrained('products')->restrictOnDelete();
            $t->string('ubicacion')->default('Almacén principal');
            $t->string('unidad')->default('pieza');
            $t->timestamps();
        });
        Schema::create('pedidos_scm', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('products')->restrictOnDelete();
            $t->unsignedInteger('cantidad');
            $t->string('tipo');
            $t->string('estado')->default('pendiente')->index();
            $t->boolean('automatico')->default(false);
            $t->string('automatico_key')->nullable()->unique();
            $t->uuid('request_key')->nullable()->unique();
            $t->foreignId('usuario_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('fecha_surtido')->nullable();
            $t->timestamps();
        });
        Schema::create('movimientos_inventario', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('products')->restrictOnDelete();
            $t->string('tipo');
            $t->unsignedInteger('cantidad');
            $t->string('motivo');
            $t->timestamp('fecha')->index();
            $t->unsignedInteger('stock_anterior');
            $t->unsignedInteger('stock_resultante');
            $t->string('referencia', 255)->nullable();
            $t->foreignId('usuario_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('pedido_id')->nullable()->constrained('pedidos_scm')->restrictOnDelete();
            $t->foreignId('order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $t->uuid('request_key')->nullable()->unique();
            $t->timestamps();
        });
        Schema::create('scm_settings', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->string('nivel_scm')->default('Inicial');
            $t->json('checklist');
            $t->timestamps();
        });
        $now = now();
        DB::table('scm_settings')->insert(['id' => 1, 'nivel_scm' => 'Inicial', 'checklist' => json_encode(['catalogo' => false, 'proveedores' => false, 'inventario' => false, 'estrategias' => false, 'pedidos' => false, 'reportes' => false]), 'created_at' => $now, 'updated_at' => $now]);
        // Preserva el inventario existente como saldo inicial de trazabilidad.
        DB::table('products')->orderBy('id')->chunkById(200, function ($products) use ($now) {
            foreach ($products as $p) {
                DB::table('inventarios')->insert(['producto_id' => $p->id, 'ubicacion' => 'Almacén principal', 'unidad' => 'pieza', 'created_at' => $now, 'updated_at' => $now]);
                if ($p->stock > 0) {
                    DB::table('movimientos_inventario')->insert(['producto_id' => $p->id, 'tipo' => 'entrada', 'cantidad' => $p->stock, 'motivo' => 'ajuste', 'fecha' => $now, 'stock_anterior' => 0, 'stock_resultante' => $p->stock, 'referencia' => 'Saldo inicial al incorporar SCM', 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        });
    }

    public function down(): void
    {
        foreach (['scm_settings', 'movimientos_inventario', 'pedidos_scm', 'inventarios'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('products', function (Blueprint $t) {
            $t->dropConstrainedForeignId('proveedor_id');
            $t->dropColumn(['stock_minimo', 'stock_objetivo', 'costo_unitario_cents', 'estrategia_logistica', 'inventory_version', 'deleted_at']);
        });
        Schema::dropIfExists('proveedores');
    }
};
