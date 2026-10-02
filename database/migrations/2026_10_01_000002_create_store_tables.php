<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('color')->default('');
            $table->string('category', 40);
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('original_price_cents')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('image', 2048);
            $table->string('badge')->nullable();
            $table->text('description');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->uuid('checkout_key');
            $table->unique(['user_id', 'checkout_key']);
            $table->string('address', 255);
            $table->string('city', 120);
            $table->string('zip', 5);
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->string('status')->default('Pendiente');
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('price_cents');
            $table->text('description');
            $table->string('image', 2048);
            $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image', 2048);
            $table->unsignedInteger('current_bid_cents');
            $table->timestamp('ends_at');
            $table->timestamps();
        });
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email');
            $table->text('message');
            $table->boolean('handled')->default(false);
            $table->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['subscriptions', 'contact_messages', 'bids', 'auctions', 'comments', 'publications', 'order_items', 'orders', 'products'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
