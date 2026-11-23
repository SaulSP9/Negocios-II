<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promociones', fn (Blueprint $table) => $table->foreignId('producto_id')->nullable()->constrained('products')->restrictOnDelete());
        Schema::create('importaciones_carrito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carrito')->cascadeOnDelete();
            $table->uuid('request_key');
            $table->string('payload_hash', 64);
            $table->json('rechazados');
            $table->unique(['carrito_id', 'request_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones_carrito');
        Schema::table('promociones', fn (Blueprint $table) => $table->dropConstrainedForeignId('producto_id'));
    }
};
