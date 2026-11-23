<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_monedero', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monedero_id')->constrained('monederos')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo', 24);
            $table->bigInteger('importe_cents');
            $table->unsignedBigInteger('saldo_cents');
            $table->string('metodo', 24)->nullable();
            $table->string('concepto', 200);
            $table->uuid('request_key')->nullable();
            $table->timestamps();
            $table->unique(['monedero_id', 'request_key']);
            $table->unique(['order_id', 'tipo']);
        });

        DB::table('monederos')->where('saldo_cents', '>', 0)->chunkById(200, function ($wallets) {
            $now = now();
            DB::table('movimientos_monedero')->insert($wallets->map(fn ($wallet) => [
                'monedero_id' => $wallet->id, 'tipo' => 'apertura',
                'importe_cents' => $wallet->saldo_cents, 'saldo_cents' => $wallet->saldo_cents,
                'concepto' => 'Saldo inicial de la actualización', 'created_at' => $now, 'updated_at' => $now,
            ])->all());
        });

        Schema::table('tickets_atencion', fn (Blueprint $table) => $table->string('categoria', 30)->default('General'));
        Schema::create('eventos_ticket_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets_atencion')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('estado', 20);
            $table->text('mensaje');
            $table->timestamps();
        });
        DB::table('tickets_atencion')->whereNotNull('respuesta')->chunkById(200, function ($tickets) {
            DB::table('eventos_ticket_atencion')->insert($tickets->map(fn ($ticket) => [
                'ticket_id' => $ticket->id, 'estado' => $ticket->estado, 'mensaje' => $ticket->respuesta,
                'created_at' => $ticket->updated_at ?? $ticket->fecha, 'updated_at' => $ticket->updated_at ?? $ticket->fecha,
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_ticket_atencion');
        Schema::table('tickets_atencion', fn (Blueprint $table) => $table->dropColumn('categoria'));
        Schema::dropIfExists('movimientos_monedero');
    }
};
