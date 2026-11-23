<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Monedero;
use App\Models\Product;
use App\Models\Promocion;
use App\Models\Proveedor;
use App\Models\TicketAtencion;
use App\Models\User;
use App\Support\Editorial;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Proveedor::firstOrCreate(['correo' => 'abasto@hfstudio.test'], [
            'nombre' => 'HF Abasto', 'contacto' => 'Operación de almacén', 'telefono' => '4490000001',
        ]);
        $products = json_decode(file_get_contents(database_path('seeders/products.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($products as $p) {
            Product::withTrashed()->firstOrCreate(['id' => $p['id']], [
                'name' => $p['name'], 'color' => $p['color'], 'category' => $p['category'],
                'price_cents' => (int) round($p['price'] * 100), 'original_price_cents' => (int) round($p['originalPrice'] * 100),
                'stock' => $p['id'] === 1 ? 8 : 30, 'stock_minimo' => 5, 'stock_objetivo' => 30, 'proveedor_id' => $supplier->id,
                'estrategia_logistica' => $p['id'] === 1 ? 'PUSH' : 'PULL',
                'image' => $p['image'], 'badge' => $p['badge'], 'description' => $p['description'], 'active' => true,
            ]);
        }
        Auction::firstOrCreate(['id' => 1], ['name' => 'HF Signature Hoodie (Sample 1/1)',
            'image' => Editorial::section('subasta'), 'current_bid_cents' => 350000, 'ends_at' => now()->addDays(7)]);
        Promocion::firstOrCreate(['nombre' => 'Back in Stock'], [
            'descripcion' => '20% en hoodies de la colección de archivo.',
            'categoria_id' => Categoria::where('slug', 'hoodies-outerwear')->value('id'),
            'tipo_descuento' => 'porcentaje', 'valor' => 20, 'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDays(14), 'estado' => true,
        ]);
        Promocion::firstOrCreate(['nombre' => 'Essentials 2026'], [
            'descripcion' => '10% en camisetas de la edición de básicos.',
            'categoria_id' => Categoria::where('slug', 'camisetas-tees')->value('id'),
            'tipo_descuento' => 'porcentaje', 'valor' => 10, 'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDays(30), 'estado' => true,
        ]);

        if (app()->environment('local', 'testing') && config('commerce.demo')) {
            $this->demoAccounts();
        }
    }

    private function demoAccounts(): void
    {
        $client = Cliente::firstOrCreate(['correo' => config('commerce.demo_email')], [
            'nombre' => 'Cliente Demo', 'telefono' => '4490000000', 'etapa_crm' => 'Activo',
        ]);
        if (! $client->direccion) {
            $client->forceFill(['direccion' => 'Av. Convención 101', 'ciudad' => 'Aguascalientes', 'codigo_postal' => '20000'])->save();
        }
        $customer = User::firstOrCreate(['email' => $client->correo], ['name' => $client->nombre, 'password' => 'HFStudioDemo2026!']);
        if ($customer->wasRecentlyCreated || ($customer->role === 'cliente' && ! $customer->cliente_id)) {
            $customer->forceFill(['cliente_id' => $client->id])->save();
        }
        $admin = null;
        foreach (['admin' => 'Administración', 'ventas' => 'Ventas', 'logistica' => 'Logística'] as $role => $label) {
            $staff = User::firstOrCreate(['email' => $role.'@hfstudio.test'], ['name' => $label.' HF', 'password' => 'HFStudioStaff2026!']);
            if ($staff->wasRecentlyCreated) {
                $staff->forceFill(['role' => $role])->save();
            }
            if ($role === 'admin') {
                $admin = $staff;
            }
        }
        $wallet = Monedero::firstOrCreate(['cliente_id' => $client->id], ['saldo_cents' => 500000]);
        if ($wallet->wasRecentlyCreated) {
            $wallet->movimientos()->create(['tipo' => 'apertura', 'importe_cents' => 500000, 'saldo_cents' => 500000, 'concepto' => 'Saldo Cliente Demo']);
        }
        $ticket = TicketAtencion::firstOrCreate(['cliente_id' => $client->id, 'asunto' => 'Guía de tallas'], [
            'mensaje' => '¿En qué parte de la tienda puedo revisar las tallas?',
            'respuesta' => 'Abre la ficha de la prenda para revisar las tallas disponibles.',
            'estado' => 'Respondido', 'fecha' => now(),
        ]);
        if ($ticket->wasRecentlyCreated) {
            $ticket->eventos()->create(['user_id' => $admin->id, 'estado' => $ticket->estado, 'mensaje' => $ticket->respuesta]);
            foreach ([[$customer->id, $ticket->mensaje], [$admin->id, $ticket->respuesta]] as [$actor, $message]) {
                $client->interacciones()->create(['usuario_id' => $actor, 'tipo' => 'ticket', 'descripcion' => 'Ticket #'.$ticket->id.': '.$message, 'fecha' => now()]);
            }
        }
    }
}
