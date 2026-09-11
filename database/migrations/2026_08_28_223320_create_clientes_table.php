<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo')->unique();
            $table->string('telefono')->nullable();
            $table->string('empresa')->nullable();
            $table->date('fecha_registro')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->enum('etapa_crm', ['Prospecto', 'Activo', 'Frecuente', 'Inactivo'])->default('Prospecto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};