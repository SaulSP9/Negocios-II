<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Orden;
use App\Models\Product;
use App\Models\Proveedor;
use App\Models\Recurso;
use App\Models\ReporteErp;
use App\Models\User;
use App\Services\ErpMetrics;
use App\Services\ErpOrders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DemoErp extends Command
{
    protected $signature = 'hf:demo-u3';

    protected $description = 'Agrega un escenario ficticio e idempotente para presentar CRM + SCM + ERP; conserva los datos existentes.';

    public function handle(ErpOrders $orders, ErpMetrics $metrics): int
    {
        $emails = ['admin' => 'admin.demo@hfstudios.test', 'ventas' => 'ventas.demo@hfstudios.test', 'logistica' => 'logistica.demo@hfstudios.test'];
        $missing = User::whereIn('email', array_values($emails))->count() < 3;
        $password = $missing ? $this->secret('Contraseña para las nuevas cuentas demo (mínimo 12 caracteres)') : null;
        if ($missing && Validator::make(['password' => $password], ['password' => 'required|string|min:12|max:128'])->fails()) {
            $this->error('La contraseña debe tener entre 12 y 128 caracteres.');

            return self::FAILURE;
        }
        $this->line('Agregando registros de demostración; las cuentas y registros existentes se conservan.');
        DB::transaction(function () use ($emails, $password, $orders, $metrics) {
            $accounts = [];
            foreach ($emails as $role => $email) {
                $user = User::where('email', $email)->first();
                if (! $user) {
                    $user = new User(['name' => 'Demo '.ucfirst($role), 'email' => $email, 'password' => $password]);
                    $user->role = $role;
                    $user->save();
                }
                $accounts[$role] = $user;
            }
            $supplier = Proveedor::firstOrCreate(['correo' => 'proveedor.demo@hfstudios.test'],
                ['nombre' => 'Textiles DEMO', 'contacto' => 'Contacto de demostración', 'telefono' => '4490000000']);
            $client = Cliente::firstOrCreate(['correo' => 'cliente.demo@hfstudios.test'],
                ['nombre' => 'Cliente de demostración', 'empresa' => 'Empresa DEMO', 'telefono' => '4490000001', 'estado' => 'activo', 'etapa_crm' => 'Prospecto']);
            Cliente::firstOrCreate(['correo' => 'cliente2.demo@hfstudios.test'],
                ['nombre' => 'Segundo cliente DEMO', 'empresa' => 'Comercial DEMO', 'estado' => 'activo', 'etapa_crm' => 'Activo']);
            $push = Product::firstOrCreate(['name' => 'DEMO · HF Hoodie Push'], ['description' => 'Prenda ficticia para demostrar reposición automática',
                'category' => 'Hoodie', 'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=600', 'price_cents' => 49900, 'stock' => 4, 'stock_minimo' => 3, 'stock_objetivo' => 15, 'costo_unitario_cents' => 22000, 'estrategia_logistica' => 'PUSH', 'proveedor_id' => $supplier->id, 'active' => true]);
            Product::firstOrCreate(['name' => 'DEMO · HF T-Shirt Pull'], ['description' => 'Prenda ficticia para demostrar pedidos bajo demanda',
                'category' => 'T-Shirt', 'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600', 'price_cents' => 24900, 'stock' => 18, 'stock_minimo' => 2, 'stock_objetivo' => 20, 'costo_unitario_cents' => 11000, 'estrategia_logistica' => 'PULL', 'proveedor_id' => $supplier->id, 'active' => true]);
            Recurso::firstOrCreate(['nombre' => 'DEMO · Estación de empaque'], ['tipo' => 'fisico', 'descripcion' => 'Recurso para preparación de órdenes',
                'disponible' => true, 'responsable_id' => $accounts['logistica']->id]);
            $key = '902df967-93cb-4c3a-a90b-cbdb0264eab1';
            if (! Orden::where('request_key', $key)->exists()) {
                $orders->create(['cliente_id' => $client->id, 'request_key' => $key, 'notas' => 'Orden de demostración para la exposición U3.',
                    'productos' => [['producto_id' => $push->id, 'cantidad' => 2, 'precio_unitario_cents' => $push->price_cents]]], $accounts['admin'], true);
                ReporteErp::create(['usuario_id' => $accounts['admin']->id, 'resumen' => $metrics->summary()]);
            }
        }, 3);
        foreach ($emails as $role => $email) {
            $this->info($role.': '.$email);
        }
        $this->info('Abre /?vista=login. Las cuentas nuevas usan la contraseña que escribiste; las existentes conservan la suya.');
        $this->info('Presentación: /erp -> Nueva orden -> Procesar -> SCM -> Historial / Push -> Reportes -> Nivel ERP.');

        return self::SUCCESS;
    }
}
