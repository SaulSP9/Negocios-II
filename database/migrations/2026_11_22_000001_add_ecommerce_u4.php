<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('slug')->unique();
            $table->string('imagen_url');
            $table->timestamps();
        });
        Schema::table('products', fn (Blueprint $table) => $table->foreignId('categoria_id')->nullable()->constrained('categorias')->restrictOnDelete());
        $categories = [
            'Hoodie' => ['hoodies-outerwear', 'hoodie-esencial'],
            'T-Shirt' => ['camisetas-tees', 'tee-oversized'],
            'Pants' => ['pantalones', 'cargo-pants'],
            'Accessory' => ['accesorios', 'beanie-premium'],
        ];
        foreach (array_unique([...array_keys($categories), ...DB::table('products')->distinct()->pluck('category')->all()]) as $name) {
            [$slug, $photo] = $categories[$name] ?? [Str::slug($name).'-'.substr(sha1($name), 0, 8), 'hoodie-esencial'];
            $id = DB::table('categorias')->where('nombre', $name)->value('id') ?? DB::table('categorias')->insertGetId([
                'nombre' => $name, 'slug' => $slug, 'imagen_url' => 'images/hf-studios/productos/'.$photo.'.webp',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('products')->where('category', $name)->update(['categoria_id' => $id]);
        }

        // Sustituye únicamente las fotos del catálogo de muestra de la versión anterior.
        $seedPhotos = [
            1 => ['https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?q=80&w=800&auto=format&fit=crop', 'images/hf-studios/productos/hoodie-esencial.webp'],
            2 => ['https://images.unsplash.com/photo-1622445275576-721325763afe?q=80&w=800&auto=format&fit=crop', 'images/hf-studios/productos/tee-oversized.webp'],
            3 => ['https://images.unsplash.com/photo-1556821840-3a63f95609a7?q=80&w=800&auto=format&fit=crop', 'images/hf-studios/productos/hoodie-grafico.webp'],
            4 => ['https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?q=80&w=800&auto=format&fit=crop', 'images/hf-studios/productos/tee-vintage.webp'],
            5 => ['https://images.unsplash.com/photo-1618354691373-d851c5c3a990?q=80&w=800&auto=format&fit=crop', 'images/hf-studios/productos/cargo-pants.webp'],
            6 => ['https://images.unsplash.com/photo-1576871337622-98d48d1cf531?q=80&w=800&auto=format&fit=crop', 'images/hf-studios/productos/beanie-premium.webp'],
        ];
        foreach ($seedPhotos as $id => [$previous, $current]) {
            DB::table('products')->where('id', $id)->where('image', $previous)->update(['image' => $current]);
        }
        DB::table('auctions')->where('id', 1)->where('image', 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?q=80&w=800')
            ->update(['image' => 'images/hf-studios/secciones/subasta.webp']);

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('direccion')->nullable();
            $table->string('ciudad', 120)->nullable();
            $table->string('codigo_postal', 5)->nullable();
        });
        Schema::table('users', fn (Blueprint $table) => $table->foreignId('cliente_id')->nullable()->unique()->constrained('clientes')->nullOnDelete());
        DB::table('users')->where('role', 'cliente')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $id = DB::table('clientes')->where('correo', $user->email)->value('id');
                if ($id) {
                    DB::table('users')->where('id', $user->id)->update(['cliente_id' => $id]);
                }
            }
        });
        Schema::create('carrito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->cascadeOnDelete();
            $table->string('estado', 20)->default('Activo');
            $table->timestamps();
        });
        Schema::create('carrito_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carrito')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('products')->restrictOnDelete();
            $table->string('talla', 12);
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('precio_unitario_cents');
            $table->unique(['carrito_id', 'producto_id', 'talla']);
            $table->timestamps();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('orden_empresarial_id')->nullable()->unique()->constrained('ordenes_empresariales')->restrictOnDelete();
            $table->string('checkout_hash', 64)->nullable();
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('subtotal_cents')->change();
            $table->unsignedBigInteger('discount_cents')->default(0)->change();
            $table->unsignedBigInteger('total_cents')->change();
        });
        Schema::table('order_items', fn (Blueprint $table) => $table->string('talla', 12)->nullable());
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->unsignedBigInteger('monto_cents');
            $table->string('metodo_pago', 20);
            $table->string('estado', 20);
            $table->timestamp('fecha');
            $table->timestamps();
        });
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->restrictOnDelete();
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->string('tipo_descuento', 20);
            $table->decimal('valor', 10, 2);
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin');
            $table->boolean('estado')->default(true);
            $table->index(['estado', 'fecha_inicio', 'fecha_fin']);
            $table->timestamps();
        });
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->decimal('porcentaje', 5, 2);
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
        Schema::create('monederos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->restrictOnDelete();
            $table->unsignedBigInteger('saldo_cents')->default(0);
            $table->timestamps();
        });
        Schema::create('tickets_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->string('asunto', 150);
            $table->text('mensaje');
            $table->string('estado', 20)->default('Abierto')->index();
            $table->timestamp('fecha');
            $table->text('respuesta')->nullable();
            $table->timestamps();
        });
        DB::table('impuestos')->insert(['nombre' => 'IVA', 'porcentaje' => 16, 'estado' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        foreach (['tickets_atencion', 'monederos', 'impuestos', 'promociones', 'pagos', 'carrito_detalle', 'carrito'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('talla'));
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('orden_empresarial_id');
            $table->dropColumn(['checkout_hash', 'tax_cents']);
            $table->unsignedInteger('subtotal_cents')->change();
            $table->unsignedInteger('discount_cents')->default(0)->change();
            $table->unsignedInteger('total_cents')->change();
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('cliente_id'));
        Schema::table('clientes', fn (Blueprint $table) => $table->dropColumn(['direccion', 'ciudad', 'codigo_postal']));
        Schema::table('products', fn (Blueprint $table) => $table->dropConstrainedForeignId('categoria_id'));
        Schema::dropIfExists('categorias');
    }
};
