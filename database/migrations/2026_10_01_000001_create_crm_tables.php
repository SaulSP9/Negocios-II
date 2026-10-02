<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('cliente')->index();
        });
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('correo')->unique();
            $table->string('telefono', 25)->nullable();
            $table->string('empresa', 150)->nullable();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->string('estado')->default('activo')->index();
            $table->string('etapa_crm')->default('Prospecto')->index();
            $table->timestamps();
        });
        Schema::create('interacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo');
            $table->text('descripcion');
            $table->timestamp('fecha')->index();
            $table->timestamps();
        });
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('puntuacion');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
        Schema::dropIfExists('interacciones');
        Schema::dropIfExists('clientes');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
