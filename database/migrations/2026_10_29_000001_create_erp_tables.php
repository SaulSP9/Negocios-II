<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_empresariales', function (Blueprint $t) {
            $t->id();
            $t->string('folio', 30)->nullable()->unique();
            $t->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $t->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $t->timestamp('fecha')->index();
            $t->string('estado', 20)->default('pendiente')->index();
            $t->unsignedBigInteger('total_cents')->default(0);
            $t->uuid('request_key')->unique();
            $t->string('payload_hash', 64);
            $t->string('notas', 500)->nullable();
            $t->timestamp('procesada_at')->nullable();
            $t->timestamp('cancelada_at')->nullable();
            $t->timestamps();
        });
        Schema::create('orden_productos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_id')->constrained('ordenes_empresariales')->cascadeOnDelete();
            $t->foreignId('producto_id')->constrained('products')->restrictOnDelete();
            $t->string('nombre', 120);
            $t->unsignedInteger('cantidad');
            $t->unsignedInteger('precio_unitario_cents');
            $t->unsignedBigInteger('subtotal_cents');
            $t->timestamps();
            $t->unique(['orden_id', 'producto_id']);
        });
        Schema::create('orden_historial', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_id')->constrained('ordenes_empresariales')->cascadeOnDelete();
            $t->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $t->string('evento', 30);
            $t->string('descripcion', 500);
            $t->timestamp('fecha')->index();
            $t->timestamps();
        });
        Schema::create('procesos_internos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_id')->constrained('ordenes_empresariales')->cascadeOnDelete();
            $t->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $t->string('tipo', 30);
            $t->string('estado', 20);
            $t->json('pasos');
            $t->timestamp('finalizado_at');
            $t->timestamps();
        });
        Schema::table('movimientos_inventario', function (Blueprint $t) {
            $t->foreignId('orden_empresarial_id')->nullable()->constrained('ordenes_empresariales')->restrictOnDelete();
        });
        Schema::create('recursos_erp', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 120);
            $t->string('tipo', 30);
            $t->string('descripcion', 500)->nullable();
            $t->boolean('disponible')->default(true);
            $t->foreignId('responsable_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('reportes_erp', function (Blueprint $t) {
            $t->id();
            $t->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $t->json('resumen');
            $t->timestamps();
        });
        Schema::create('estado_erp', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->string('nivel', 30)->default('Básico');
            $t->json('checklist');
            $t->timestamps();
        });
        DB::table('estado_erp')->insert(['id' => 1, 'nivel' => 'Básico', 'checklist' => json_encode(array_fill_keys(['clientes', 'inventario', 'ordenes', 'movimientos', 'reportes', 'procesos'], false)), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', fn (Blueprint $t) => $t->dropConstrainedForeignId('orden_empresarial_id'));
        foreach (['estado_erp', 'reportes_erp', 'recursos_erp', 'procesos_internos', 'orden_historial', 'orden_productos', 'ordenes_empresariales'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
