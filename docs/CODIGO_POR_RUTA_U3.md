# U3: código completo por ruta

Base: fb8d73ff0da41cd8e2ba0500f732381cc10485a1

Todos los archivos siguientes se crean o se reemplazan completos en las rutas indicadas, desde la carpeta que contiene artisan. Consulta ERP_U3.md para los comandos y la conexión de base de datos.

## app/Console/Commands/CreateStaff.php

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateStaff extends Command
{
    protected $signature = 'hf:crear-usuario {--role=admin} {--name=} {--email=}';

    protected $description = 'Crea una cuenta del equipo; solicita la contraseña sin mostrarla.';

    public function handle(): int
    {
        $role = $this->option('role');
        $name = $this->option('name') ?: $this->ask('Nombre');
        $email = strtolower(trim($this->option('email') ?: $this->ask('Correo')));
        $password = $this->secret('Contraseña (mínimo 12 caracteres)');
        $validator = Validator::make(compact('role', 'name', 'email', 'password'), ['role' => 'required|in:admin,usuario,ventas,logistica', 'name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|max:128']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = new User(compact('name', 'email', 'password'));
        $user->role = $role;
        $user->save();
        $this->info('Cuenta creada.');

        return self::SUCCESS;
    }
}
```

## app/Console/Commands/DemoErp.php

```php
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
```

## app/Console/Commands/DiagnoseProject.php

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DiagnoseProject extends Command
{
    protected $signature = 'hf:diagnostico';

    protected $description = 'Comprueba conexión, tablas CRM/SCM/ERP, rutas, cuenta administradora y archivos compilados.';

    public function handle(): int
    {
        $ok = true;
        $check = function (string $name, bool $value) use (&$ok) {
            $value ? $this->info('[OK] '.$name) : $this->error('[FALTA] '.$name);
            $ok = $ok && $value;
        };
        $check('PHP 8.2 o superior', PHP_VERSION_ID >= 80200);
        $check('Clave de aplicación', filled(config('app.key')));
        $check('Archivos de tienda compilados', file_exists(public_path('build/manifest.json')));
        try {
            DB::connection()->getPdo();
            $this->line('Conexión: '.config('database.default').' | Base: '.DB::connection()->getDatabaseName());
            foreach (['users', 'clientes', 'interacciones', 'evaluaciones', 'products', 'proveedores', 'inventarios', 'movimientos_inventario', 'pedidos_scm',
                'ordenes_empresariales', 'orden_productos', 'orden_historial', 'procesos_internos', 'recursos_erp', 'reportes_erp', 'estado_erp'] as $table) {
                $check('Tabla '.$table, Schema::hasTable($table));
            }
            $check('Campos SCM del catálogo', Schema::hasColumns('products', ['proveedor_id', 'stock_minimo', 'stock_objetivo', 'costo_unitario_cents', 'inventory_version', 'deleted_at']));
            $check('Vínculo ERP de movimientos', Schema::hasColumn('movimientos_inventario', 'orden_empresarial_id'));
            $check('Cuenta administradora', Schema::hasTable('users') && Schema::hasColumn('users', 'role') && User::where('role', 'admin')->exists());
        } catch (\Throwable $e) {
            $this->error('No se pudo comprobar la base: '.$e->getMessage());
            $ok = false;
        }
        $routes = collect(Route::getRoutes())->map(fn ($r) => $r->uri())->all();
        foreach (['crm', 'scm', 'erp', 'clientes', 'productos', 'proveedores', 'ordenes', 'ordenes/procesar', 'erp/metricas', 'erp/estado', 'auth/login', 'usuarios/rol'] as $uri) {
            $check('Ruta /'.$uri, in_array($uri, $routes, true));
        }
        $this->line($ok ? 'Proyecto preparado para la revisión funcional.' : 'Corrige los puntos FALTA. Consulta docs/ERP_U3.md y ejecuta php artisan migrate.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
```

## app/Http/Controllers/AuthController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function session(Request $request)
    {
        return ['user' => $request->user()?->append(['permissions', 'role_label', 'home']), 'csrf_token' => csrf_token()];
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string|max:128']);
        $credentials['email'] = strtolower(trim($credentials['email']));
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }
        $request->session()->regenerate();

        return $this->session($request);
    }

    public function register(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->email))]);
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|max:128']);
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            Cliente::firstOrCreate(['correo' => $data['email']], ['nombre' => $data['name']]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($this->session($request), 201);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->session($request);
    }
}
```

## app/Http/Controllers/CrmController.php

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Http\Requests\InteraccionRequest;
use App\Models\Cliente;
use App\Models\Evaluacion;
use App\Models\Interaccion;
use App\Models\User;
use App\Services\CrmMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:120', 'estado' => 'nullable|in:activo,inactivo', 'etapa' => 'nullable|in:Prospecto,Activo,Frecuente,Inactivo', 'page' => 'nullable|integer|min:1']);

        return Cliente::withCount('interacciones')->withMax('interacciones', 'fecha')
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('nombre', 'like', "%$s%")->orWhere('correo', 'like', "%$s%")->orWhere('empresa', 'like', "%$s%")))
            ->when($filters['estado'] ?? null, fn ($q, $s) => $q->where('estado', $s))
            ->when($filters['etapa'] ?? null, fn ($q, $s) => $q->where('etapa_crm', $s))->latest('id')->paginate(15);
    }

    public function store(ClienteRequest $request)
    {
        return response()->json(Cliente::create($request->validated()), 201);
    }

    public function show(Cliente $cliente)
    {
        return $cliente->loadCount('interacciones')->load(['evaluaciones.usuario:id,name']);
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return $cliente->fresh();
    }

    public function destroy(Cliente $cliente)
    {
        abort_if(\App\Models\Orden::where('cliente_id', $cliente->id)->exists(), 409, 'El cliente tiene órdenes ERP. Cámbialo a inactivo para conservar su historial.');
        $cliente->delete();

        return response()->noContent();
    }

    public function etapa(Request $request, Cliente $cliente)
    {
        $cliente->update($request->validate(['etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo']));

        return $cliente->fresh();
    }

    public function interaction(InteraccionRequest $request)
    {
        return response()->json(Interaccion::create([...$request->validated(), 'usuario_id' => $request->user()->id])->load('usuario:id,name'), 201);
    }

    public function history(Cliente $cliente)
    {
        return $cliente->interacciones()->with('usuario:id,name')->orderByDesc('fecha')->orderByDesc('id')->get();
    }

    public function activity(Request $request)
    {
        return Interaccion::with(['cliente:id,nombre', 'usuario:id,name'])->where('usuario_id', $request->user()->id)->orderByDesc('fecha')->paginate(15);
    }

    public function metrics(CrmMetrics $metrics)
    {
        return $metrics->summary();
    }

    public function evaluate(Request $request, Cliente $cliente)
    {
        $data = $request->validate(['puntuacion' => 'required|integer|between:1,5', 'observaciones' => 'nullable|string|max:2000']);

        return response()->json(Evaluacion::create([...$data, 'cliente_id' => $cliente->id, 'usuario_id' => $request->user()->id]), 201);
    }

    public function users()
    {
        return User::select('id', 'name', 'email', 'role')->orderBy('name')->get();
    }

    public function createUser(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->email))]);
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|max:128', 'role' => 'required|in:admin,usuario,ventas,logistica']);
        $user = new User($data);
        $user->role = $data['role'];
        $user->save();

        return response()->json($user, 201);
    }

    public function contacts()
    {
        return DB::table('contact_messages')->orderByDesc('id')->paginate(15);
    }

    public function handleContact(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $contact = DB::table('contact_messages')->where('id', $id)->lockForUpdate()->first();
            abort_unless($contact, 404);
            abort_if($contact->handled, 409, 'El mensaje ya fue registrado en el CRM.');
            $client = Cliente::firstOrCreate(['correo' => $contact->email], ['nombre' => $contact->name]);
            Interaccion::create(['cliente_id' => $client->id, 'usuario_id' => $request->user()->id, 'tipo' => 'correo', 'descripcion' => $contact->message, 'fecha' => $contact->created_at]);
            DB::table('contact_messages')->where('id', $id)->update(['handled' => true, 'updated_at' => now()]);

            return response()->json($client);
        });
    }
}
```

## app/Http/Controllers/ErpController.php

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrdenRequest;
use App\Models\EstadoERP;
use App\Models\Orden;
use App\Models\Recurso;
use App\Models\ReporteErp;
use App\Models\User;
use App\Services\ErpMetrics;
use App\Services\ErpOrders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ErpController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:120', 'estado' => 'nullable|in:pendiente,procesada,completada,cancelada', 'page' => 'nullable|integer|min:1']);

        return Orden::with(['cliente:id,nombre,correo,empresa', 'usuario:id,name'])
            ->when($f['estado'] ?? null, fn ($q, $s) => $q->where('estado', $s))
            ->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('folio', 'like', "%$s%")->orWhereHas('cliente', fn ($q) => $q->where('nombre', 'like', "%$s%")->orWhere('correo', 'like', "%$s%"))))
            ->orderByDesc('id')->paginate(15);
    }

    public function show(Orden $orden)
    {
        return $orden->load(['cliente', 'usuario:id,name', 'productos', 'historial.usuario:id,name', 'procesos', 'movimientos']);
    }

    public function store(OrdenRequest $request, ErpOrders $service)
    {
        return response()->json($this->show($service->create($request->validated(), $request->user(), false)), 201);
    }

    public function process(Request $request, ErpOrders $service)
    {
        if ($request->filled('orden_id')) {
            $d = $request->validate(['orden_id' => 'required|integer|exists:ordenes_empresariales,id']);

            return $this->show($service->process($d['orden_id'], $request->user()));
        }
        $validated = app(OrdenRequest::class)->validated();

        return response()->json($this->show($service->create($validated, $request->user(), true)), 201);
    }

    public function state(Request $request, Orden $orden, ErpOrders $service)
    {
        $d = $request->validate(['estado' => 'required|in:procesada,completada,cancelada', 'motivo' => 'nullable|string|max:300']);

        return $this->show($service->changeState($orden->id, $d['estado'], $request->user(), $d['motivo'] ?? null));
    }

    public function metrics(ErpMetrics $metrics)
    {
        return $metrics->summary();
    }

    public function implementation(ErpMetrics $metrics)
    {
        $state = EstadoERP::findOrFail(1);
        $evidence = $metrics->evidence();

        return [...$state->toArray(), 'evidencias' => $evidence, 'progreso' => (int) round(count(array_filter($evidence)) / 6 * 100),
            'niveles' => ['Básico', 'Integrado', 'Automatizado', 'Optimizado']];
    }

    public function updateImplementation(Request $request, ErpMetrics $metrics)
    {
        $keys = ['clientes', 'inventario', 'ordenes', 'movimientos', 'reportes', 'procesos'];
        $rules = ['nivel' => 'required|in:Básico,Integrado,Automatizado,Optimizado', 'checklist' => 'required|array:'.implode(',', $keys)];
        foreach ($keys as $key) {
            $rules['checklist.'.$key] = 'required|boolean';
        }
        $data = $request->validate($rules);
        $evidence = $metrics->evidence();
        foreach ($keys as $key) {
            if ($data['checklist'][$key] && ! $evidence[$key]) {
                throw ValidationException::withMessages(['checklist.'.$key => 'Todavía no existe evidencia real para: '.$key.'.']);
            }
        }
        $minimum = ['Básico' => 0, 'Integrado' => 3, 'Automatizado' => 4, 'Optimizado' => 6][$data['nivel']];
        if (count(array_filter($data['checklist'])) < $minimum || ($data['nivel'] === 'Automatizado' && ! $data['checklist']['movimientos'])) {
            throw ValidationException::withMessages(['nivel' => 'El nivel requiere '.$minimum.' evidencias; Automatizado también exige movimientos de inventario.']);
        }
        EstadoERP::findOrFail(1)->update($data);

        return $this->implementation($metrics);
    }

    public function resources()
    {
        return Recurso::with('responsable:id,name')->orderBy('nombre')->get();
    }

    public function saveResource(Request $request, ?Recurso $recurso = null)
    {
        $data = $request->validate(['nombre' => 'required|string|min:2|max:120', 'tipo' => 'required|in:humano,fisico,tecnologico,financiero',
            'descripcion' => 'nullable|string|max:500', 'disponible' => 'required|boolean', 'responsable_id' => 'nullable|integer|exists:users,id']);
        $creating = ! $recurso;
        $recurso ??= new Recurso;
        $recurso->fill($data)->save();

        return response()->json($recurso->load('responsable:id,name'), $creating ? 201 : 200);
    }

    public function resourceOwners()
    {
        return User::whereIn('role', ['admin', 'usuario', 'ventas', 'logistica'])->select('id', 'name')->orderBy('name')->get();
    }

    public function reports()
    {
        return ReporteErp::with('usuario:id,name')->latest('id')->paginate(10);
    }

    public function generateReport(Request $request, ErpMetrics $metrics)
    {
        return response()->json(DB::transaction(fn () => ReporteErp::create(['usuario_id' => $request->user()->id, 'resumen' => $metrics->summary()])->load('usuario:id,name')), 201);
    }

    public function export(ErpMetrics $metrics)
    {
        return $this->csv($metrics->summary(), 'reporte-erp.csv');
    }

    public function exportReport(ReporteErp $reporte)
    {
        return $this->csv($reporte->resumen, 'reporte-erp-'.$reporte->id.'.csv');
    }

    private function csv(array $m, string $filename)
    {
        return response()->streamDownload(function () use ($m) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $write = function (array $row) use ($out) {
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+@\\-]/u', $v) ? "'".$v : $v, $row), ',', '"', '');
            };
            $write(['Indicador', 'Valor']);
            foreach (['ventas_totales', 'ordenes_procesadas', 'productos_vendidos', 'clientes_activos', 'inventario_disponible', 'ordenes_pendientes', 'reposiciones_scm_pendientes'] as $key) {
                $write([$key, $m[$key]]);
            }
            $write(['Fecha del corte', $m['generado_at']]);
            $write([]);
            $write(['Mes', 'Ventas MXN']);
            foreach ($m['ventas_por_mes'] as $row) {
                $write([$row['mes'], $row['total']]);
            }
            $write([]);
            $write(['Producto', 'Unidades vendidas']);
            foreach ($m['productos_mas_vendidos'] as $row) {
                $write([$row['nombre'], $row['unidades']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
```

## app/Http/Controllers/ScmController.php

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScmProductRequest;
use App\Models\Pedido;
use App\Models\Product;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\ScmSetting;
use App\Services\InventoryService;
use App\Services\ScmReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScmController extends Controller
{
    public function products(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:120', 'estrategia' => 'nullable|in:PUSH,PULL', 'critico' => 'nullable|boolean', 'archivados' => 'nullable|boolean', 'page' => 'nullable|integer|min:1']);
        $query = Producto::with(['proveedor', 'inventario']);
        if ($request->boolean('archivados')) {
            $query->onlyTrashed();
        }
        $result = $query->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('description', 'like', "%$s%")))
            ->when($f['estrategia'] ?? null, fn ($q, $s) => $q->where('estrategia_logistica', $s))
            ->when($request->boolean('critico'), fn ($q) => $q->whereColumn('stock', '<=', 'stock_minimo'))->orderBy('id')->paginate(15);
        $result->through(fn ($p) => $p->scmPresentation());

        return $result;
    }

    public function product(Producto $producto)
    {
        return $producto->scmPresentation();
    }

    public function saveProduct(ScmProductRequest $request, InventoryService $inventory, ?Producto $producto = null)
    {
        return DB::transaction(function () use ($request, $inventory, $producto) {
            $d = $request->validated();
            $creating = ! $producto;
            if ($producto) {
                $producto = Producto::lockForUpdate()->findOrFail($producto->id);
            } else {
                $producto = new Producto;
            }
            $producto->fill(['name' => $d['nombre'], 'description' => $d['descripcion'], 'category' => $d['categoria'],
                'color' => $d['color'] ?? '', 'image' => $d['imagen'], 'active' => $d['activo'],
                'price_cents' => (int) round($d['precio_venta'] * 100), 'costo_unitario_cents' => (int) round($d['costo_unitario'] * 100),
                'stock_minimo' => $d['stock_minimo'], 'stock_objetivo' => $d['stock_objetivo'],
                'proveedor_id' => $d['proveedor_id'] ?? null, 'estrategia_logistica' => $d['estrategia_logistica']]);
            if ($creating) {
                $producto->stock = $d['stock_actual'];
            }
            $producto->save();
            $producto->inventario()->update(['ubicacion' => $d['ubicacion']]);
            $inventory->checkPush($producto->id, $request->user()->id);

            return response()->json($producto->fresh()->scmPresentation(), $creating ? 201 : 200);
        }, 3);
    }

    public function deleteProduct(Producto $producto)
    {
        return DB::transaction(function () use ($producto) {
            $product = Product::lockForUpdate()->findOrFail($producto->id);
            abort_if(\App\Models\Orden::where('estado', 'pendiente')->whereHas('productos', fn ($q) => $q->where('producto_id', $product->id))->exists(), 409, 'El producto está en una orden ERP pendiente. Procésala o cancélala antes de archivar.');
            abort_if($product->stock > 0, 409, 'Agota o ajusta el stock antes de archivar.');
            abort_if(Pedido::where('producto_id', $product->id)->where('estado', 'pendiente')->exists(), 409, 'El producto tiene pedidos SCM pendientes.');
            abort_if(DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->where('product_id', $product->id)->whereIn('status', ['Pendiente', 'Enviado'])->exists(), 409, 'El producto tiene pedidos de tienda abiertos.');
            $product->delete();

            return response()->noContent();
        }, 3);
    }

    public function restoreProduct(Producto $producto)
    {
        $producto->restore();

        return $producto->fresh()->scmPresentation();
    }

    public function suppliers()
    {
        return Proveedor::orderBy('nombre')->get();
    }

    public function saveSupplier(Request $request, ?Proveedor $proveedor = null)
    {
        $request->merge(['correo' => strtolower(trim((string) $request->correo))]);
        $d = $request->validate(['nombre' => 'required|string|min:2|max:120', 'contacto' => 'required|string|min:2|max:120',
            'correo' => ['required', 'email', 'max:255', Rule::unique('proveedores', 'correo')->ignore($proveedor)],
            'telefono' => ['required', 'string', 'max:25', 'regex:/^[0-9+() .-]{7,25}$/']]);
        $creating = ! $proveedor;
        $proveedor ??= new Proveedor;
        $proveedor->fill($d)->save();

        return response()->json($proveedor, $creating ? 201 : 200);
    }

    public function deleteSupplier(Proveedor $proveedor)
    {
        abort_if(Product::withTrashed()->where('proveedor_id', $proveedor->id)->exists(), 409, 'El proveedor está asociado a productos; conserva su trazabilidad.');
        $proveedor->delete();

        return response()->noContent();
    }

    public function movement(Request $request, InventoryService $inventory)
    {
        $d = $request->validate(['producto_id' => 'required|integer|exists:products,id', 'tipo' => 'required|in:entrada,salida', 'cantidad' => 'required|integer|between:1,1000000',
            'motivo' => 'required|in:venta,ajuste,reposición', 'referencia' => 'nullable|string|max:255', 'request_key' => 'nullable|uuid']);
        if (($d['motivo'] === 'venta' && $d['tipo'] !== 'salida') || ($d['motivo'] === 'reposición' && $d['tipo'] !== 'entrada')) {
            abort(422, 'Venta requiere salida y reposición requiere entrada.');
        }

        $extra = array_intersect_key($d, array_flip(['referencia', 'request_key']));
        $movement = $d['motivo'] === 'reposición' ? $inventory->receiveDirect($d, $request->user()->id) : $inventory->move($d['producto_id'], $d['tipo'], $d['cantidad'], $d['motivo'], $request->user()->id, $extra);

        return response()->json($movement->load('usuario:id,name'), 201);
    }

    public function history(Producto $producto)
    {
        return $producto->movimientos()->with('usuario:id,name')->orderByDesc('id')->paginate(20);
    }

    public function strategy(Request $request, Producto $producto, InventoryService $inventory)
    {
        $d = $request->validate(['estrategia_logistica' => 'required|in:PUSH,PULL']);

        return DB::transaction(function () use ($d, $producto, $inventory, $request) {
            $p = Product::lockForUpdate()->findOrFail($producto->id);
            abort_if($d['estrategia_logistica'] === 'PUSH' && ! $p->proveedor_id, 422, 'Asocia un proveedor antes de activar Push.');
            $p->update($d);
            $inventory->checkPush($p->id, $request->user()->id);

            return $p->fresh()->scmPresentation();
        }, 3);
    }

    public function orders(Request $request)
    {
        $f = $request->validate(['estado' => 'nullable|in:pendiente,surtido', 'tipo' => 'nullable|in:reposicion,venta', 'page' => 'nullable|integer|min:1']);

        return Pedido::with(['producto:id,name,stock,deleted_at', 'usuario:id,name'])
            ->when($f['estado'] ?? null, fn ($q, $s) => $q->where('estado', $s))->when($f['tipo'] ?? null, fn ($q, $s) => $q->where('tipo', $s))->latest('id')->paginate(15);
    }

    public function createOrder(Request $request, InventoryService $inventory)
    {
        $d = $request->validate(['producto_id' => 'required|integer|exists:products,id', 'cantidad' => 'required|integer|between:1,1000000', 'tipo' => 'required|in:reposicion,venta', 'request_key' => 'nullable|uuid']);

        return response()->json($inventory->createOrder($d, $request->user()->id)->load('producto:id,name,stock'), 201);
    }

    public function orderStatus(Request $request, Pedido $pedido, InventoryService $inventory)
    {
        $d = $request->validate(['estado' => 'required|in:pendiente,surtido']);
        abort_if($d['estado'] === 'pendiente' && $pedido->estado === 'surtido', 422, 'Un pedido surtido no puede volver a pendiente.');

        return $d['estado'] === 'surtido' ? $inventory->fulfill($pedido->id, $request->user()->id) : $pedido;
    }

    public function reports(ScmReports $reports)
    {
        return $reports->summary();
    }

    public function export(ScmReports $reports)
    {
        $data = $reports->summary();

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Reporte', 'Producto', 'Cantidad'], ',', '"', '');
            foreach ($data['productos_mas_vendidos'] as $p) {
                fputcsv($out, ['Más vendidos', $this->csvValue($p['nombre']), $p['unidades']], ',', '"', '');
            }
            foreach ($data['rotacion_lenta'] as $p) {
                fputcsv($out, ['Rotación lenta (30 días)', $this->csvValue($p['nombre']), $p['unidades_30_dias']], ',', '"', '');
            }
            foreach ($data['inventario_critico'] as $p) {
                fputcsv($out, ['Stock crítico', $this->csvValue($p['nombre']), $p['stock_actual']], ',', '"', '');
            }fclose($out);
        }, 'HFSTUDIOS-reportes-SCM.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvValue(string $value): string
    {
        return preg_match('/^[=+@\-\t\r]/u', $value) ? "'".$value : $value;
    }

    public function state()
    {
        return ScmSetting::findOrFail(1);
    }

    public function maturity(Request $request)
    {
        $d = $request->validate(['nivel_scm' => 'required|in:Inicial,En desarrollo,Optimizado', 'checklist' => 'required|array:catalogo,proveedores,inventario,estrategias,pedidos,reportes',
            'checklist.catalogo' => 'required|boolean', 'checklist.proveedores' => 'required|boolean', 'checklist.inventario' => 'required|boolean',
            'checklist.estrategias' => 'required|boolean', 'checklist.pedidos' => 'required|boolean', 'checklist.reportes' => 'required|boolean']);
        $checked = count(array_filter($d['checklist']));
        abort_if(($d['nivel_scm'] === 'Optimizado' && $checked !== 6) || ($d['nivel_scm'] === 'En desarrollo' && $checked < 3), 422, 'Optimizado requiere 6 evidencias marcadas; En desarrollo requiere al menos 3.');
        $setting = ScmSetting::findOrFail(1);
        $setting->update($d);

        return $setting;
    }
}
```

## app/Http/Controllers/UserRoleController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate(['usuario_id' => 'required|integer|exists:users,id', 'role' => ['required', Rule::in(Permissions::ROLES)]]);
        abort_if((int) $data['usuario_id'] === $request->user()->id && $data['role'] !== 'admin', 409, 'Conserva tu cuenta administradora. Cambia los permisos de otra cuenta.');

        return DB::transaction(function () use ($data) {
            $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $user = User::whereKey($data['usuario_id'])->lockForUpdate()->firstOrFail();
            abort_if($user->role === 'admin' && $data['role'] !== 'admin' && $admins->count() <= 1, 409, 'El sistema necesita al menos un administrador.');
            $user->role = $data['role'];
            $user->save();

            return $user;
        }, 3);
    }
}
```

## app/Http/Middleware/RequirePermission.php

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        abort_unless($request->user()?->hasPermission($permission), 403, 'Tu rol no tiene permiso para esta operación.');

        return $next($request);
    }
}
```

## app/Http/Requests/ClienteRequest.php

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('crm') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['correo' => strtolower(trim((string) $this->correo)), 'nombre' => trim((string) $this->nombre)]);
    }

    public function rules(): array
    {
        return ['nombre' => 'required|string|min:2|max:120',
            'correo' => ['required', 'email', 'max:255', Rule::unique('clientes', 'correo')->ignore($this->route('cliente'))],
            'telefono' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+() .-]{7,25}$/'],
            'empresa' => 'nullable|string|max:150', 'estado' => 'required|in:activo,inactivo',
            'etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo'];
    }
}
```

## app/Http/Requests/InteraccionRequest.php

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InteraccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('crm') ?? false;
    }

    public function rules(): array
    {
        return ['cliente_id' => 'required|integer|exists:clientes,id', 'tipo' => 'required|in:llamada,correo,reunión',
            'descripcion' => 'required|string|min:3|max:3000', 'fecha' => 'required|date|before_or_equal:now'];
    }
}
```

## app/Http/Requests/OrdenRequest.php

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('erp.write') ?? false;
    }

    public function rules(): array
    {
        return ['cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->where('estado', 'activo')],
            'request_key' => 'required|uuid', 'notas' => 'nullable|string|max:500',
            'productos' => 'required|array|min:1|max:50',
            'productos.*' => 'required|array:producto_id,cantidad,precio_unitario_cents',
            'productos.*.producto_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'productos.*.cantidad' => 'required|integer|between:1,1000000',
            'productos.*.precio_unitario_cents' => 'nullable|integer|between:1,100000000'];
    }

    public function messages(): array
    {
        return ['cliente_id.exists' => 'Selecciona un cliente activo del CRM.', 'productos.*.producto_id.distinct' => 'Agrega cada producto una sola vez y ajusta su cantidad.', 'productos.*.producto_id.exists' => 'Uno de los productos fue archivado o ya no existe.'];
    }
}
```

## app/Http/Requests/ScmProductRequest.php

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScmProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('scm.manage') ?? false;
    }

    public function rules(): array
    {
        return ['nombre' => 'required|string|min:2|max:120', 'descripcion' => 'required|string|min:3|max:3000',
            'categoria' => 'required|in:Hoodie,T-Shirt,Pants,Accessory', 'stock_actual' => $this->isMethod('POST') ? 'required|integer|between:0,1000000' : 'prohibited',
            'stock_minimo' => 'required|integer|between:0,999999', 'stock_objetivo' => 'required|integer|gt:stock_minimo|max:1000000',
            'proveedor_id' => ['nullable', Rule::requiredIf($this->estrategia_logistica === 'PUSH'), 'integer', 'exists:proveedores,id'],
            'costo_unitario' => 'required|numeric|between:0,1000000|decimal:0,2', 'precio_venta' => 'required|numeric|min:0.01|max:1000000|decimal:0,2',
            'estrategia_logistica' => 'required|in:PUSH,PULL', 'imagen' => 'required|url:http,https|max:2048',
            'color' => 'nullable|string|max:100', 'activo' => 'required|boolean', 'ubicacion' => 'required|string|max:120'];
    }
}
```

## app/Models/EstadoERP.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoERP extends Model
{
    protected $table = 'estado_erp';

    protected $guarded = ['id'];

    public $incrementing = false;

    protected function casts(): array
    {
        return ['checklist' => 'array'];
    }
}
```

## app/Models/Orden.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $table = 'ordenes_empresariales';

    protected $guarded = ['id'];

    protected $hidden = ['payload_hash', 'request_key'];

    protected $appends = ['total'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime', 'procesada_at' => 'datetime', 'cancelada_at' => 'datetime', 'total_cents' => 'integer'];
    }

    public function getTotalAttribute(): float
    {
        return $this->total_cents / 100;
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function productos()
    {
        return $this->hasMany(OrdenProducto::class);
    }

    public function historial()
    {
        return $this->hasMany(OrdenHistorial::class)->orderBy('id');
    }

    public function procesos()
    {
        return $this->hasMany(ProcesoInterno::class)->orderBy('id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'orden_empresarial_id');
    }
}
```

## app/Models/OrdenHistorial.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenHistorial extends Model
{
    protected $table = 'orden_historial';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
```

## app/Models/OrdenProducto.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenProducto extends Model
{
    protected $table = 'orden_productos';

    protected $guarded = ['id'];

    protected $appends = ['precio_unitario', 'subtotal'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'precio_unitario_cents' => 'integer', 'subtotal_cents' => 'integer'];
    }

    public function getPrecioUnitarioAttribute(): float
    {
        return $this->precio_unitario_cents / 100;
    }

    public function getSubtotalAttribute(): float
    {
        return $this->subtotal_cents / 100;
    }

    public function producto()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
```

## app/Models/ProcesoInterno.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcesoInterno extends Model
{
    protected $table = 'procesos_internos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['pasos' => 'array', 'finalizado_at' => 'datetime'];
    }
}
```

## app/Models/Recurso.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recurso extends Model
{
    protected $table = 'recursos_erp';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['disponible' => 'boolean'];
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
```

## app/Models/ReporteErp.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteErp extends Model
{
    protected $table = 'reportes_erp';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['resumen' => 'array'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
```

## app/Models/User.php

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, \App\Support\Permissions::forRole($this->role ?? 'cliente'), true);
    }

    public function getPermissionsAttribute(): array
    {
        return \App\Support\Permissions::forRole($this->role ?? 'cliente');
    }

    public function getRoleLabelAttribute(): string
    {
        return \App\Support\Permissions::label($this->role ?? 'cliente');
    }

    public function getHomeAttribute(): string
    {
        return $this->hasPermission('erp.read') ? '/erp' : '/?vista=perfil';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $attributes = ['role' => 'cliente'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

## app/Services/ErpMetrics.php

```php
<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Orden;
use App\Models\Order;
use App\Models\Pedido;
use App\Models\ProcesoInterno;
use App\Models\Product;
use App\Models\Proveedor;
use App\Models\Recurso;
use App\Models\ReporteErp;

class ErpMetrics
{
    public function summary(): array
    {
        $erp = Orden::with('productos')->whereIn('estado', ['procesada', 'completada'])->get();
        $store = Order::with('items')->whereIn('status', ['Enviado', 'Entregado'])->get();
        $catalog = Product::with('inventario')->get();
        $series = collect(range(5, 0))->map(function ($ago) use ($erp, $store) {
            $month = now()->startOfMonth()->subMonths($ago);
            $total = $erp->filter(fn ($o) => $o->procesada_at?->format('Y-m') === $month->format('Y-m'))->sum('total_cents')
                + $store->filter(fn ($o) => $o->created_at->format('Y-m') === $month->format('Y-m'))->sum('total_cents');

            return ['mes' => $month->format('Y-m'), 'etiqueta' => $month->locale('es')->translatedFormat('M Y'), 'total_cents' => $total, 'total' => $total / 100];
        });
        $sold = [];
        foreach ($erp as $o) {
            foreach ($o->productos as $line) {
                $key = $line->producto_id;
                $sold[$key] ??= ['id' => $key, 'nombre' => $line->nombre, 'unidades' => 0];
                $sold[$key]['unidades'] += $line->cantidad;
            }
        }
        foreach ($store as $o) {
            foreach ($o->items as $line) {
                $key = $line->product_id;
                $sold[$key] ??= ['id' => $key, 'nombre' => $line->name, 'unidades' => 0];
                $sold[$key]['unidades'] += $line->quantity;
            }
        }
        $top = collect($sold)->sortByDesc('unidades')->values();
        $stock = $catalog->sum('stock');
        $critical = $catalog->filter(fn ($p) => $p->stock <= $p->stock_minimo);
        $total = $erp->sum('total_cents') + $store->sum('total_cents');

        return ['ventas_totales' => $total / 100, 'ventas_totales_cents' => $total,
            'ventas_erp_cents' => $erp->sum('total_cents'), 'ventas_tienda_cents' => $store->sum('total_cents'),
            'ordenes_procesadas' => $erp->count(), 'productos_vendidos' => $top->sum('unidades'),
            'clientes_activos' => Cliente::where('estado', 'activo')->count(), 'inventario_disponible' => $stock,
            'ordenes_pendientes' => Orden::where('estado', 'pendiente')->count(), 'ordenes_canceladas' => Orden::where('estado', 'cancelada')->count(),
            'pedidos_tienda_surtidos' => $store->count(), 'reposiciones_scm_pendientes' => Pedido::where('tipo', 'reposicion')->where('estado', 'pendiente')->count(),
            'proveedores' => Proveedor::count(), 'recursos_disponibles' => Recurso::where('disponible', true)->count(),
            'ventas_por_mes' => $series, 'productos_mas_vendidos' => $top->take(10)->values(),
            'inventario_critico' => $critical->map->scmPresentation()->values(),
            'distribucion_inventario' => ['disponible' => $stock, 'criticos' => $critical->count(), 'productos' => $catalog->count()],
            'integracion' => ['clientes_crm' => Cliente::count(), 'productos_scm' => $catalog->count(), 'ordenes_erp' => Orden::count(), 'procesos_completados' => ProcesoInterno::where('estado', 'completado')->count()],
            'generado_at' => now()->toISOString()];
    }

    public function evidence(): array
    {
        return ['clientes' => Cliente::where('estado', 'activo')->exists(),
            'inventario' => Product::whereHas('inventario')->exists(),
            'ordenes' => Orden::whereIn('estado', ['procesada', 'completada'])->exists(),
            'movimientos' => \App\Models\MovimientoInventario::whereNotNull('orden_empresarial_id')->exists(),
            'reportes' => ReporteErp::exists(), 'procesos' => ProcesoInterno::where('estado', 'completado')->exists()];
    }
}
```

## app/Services/ErpOrders.php

```php
<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Orden;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ErpOrders
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function create(array $data, User $actor, bool $process): Orden
    {
        return DB::transaction(function () use ($data, $actor, $process) {
            // Serializa reintentos de una misma cuenta antes de consultar la clave única.
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $rows = collect($data['productos'])->sortBy('producto_id')->values();
            $hash = hash('sha256', json_encode(['cliente_id' => (int) $data['cliente_id'], 'notas' => $data['notas'] ?? '',
                'productos' => $rows->map(fn ($r) => ['producto_id' => (int) $r['producto_id'], 'cantidad' => (int) $r['cantidad'], 'precio_unitario_cents' => isset($r['precio_unitario_cents']) ? (int) $r['precio_unitario_cents'] : null])->all()], JSON_THROW_ON_ERROR));
            $order = Orden::where('request_key', $data['request_key'])->lockForUpdate()->first();
            if ($order) {
                abort_unless($order->usuario_id === $actor->id && hash_equals($order->payload_hash, $hash), 409, 'Esta clave ya pertenece a otra operación. Inicia una nueva orden.');

                return $process ? $this->process($order->id, $actor) : $order;
            }
            $client = Cliente::whereKey($data['cliente_id'])->lockForUpdate()->firstOrFail();
            abort_unless($client->estado === 'activo', 422, 'El cliente está inactivo en el CRM.');
            $products = Product::whereIn('id', $rows->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $order = Orden::create(['cliente_id' => $client->id, 'usuario_id' => $actor->id, 'fecha' => now(), 'estado' => 'pendiente',
                'total_cents' => 0, 'request_key' => $data['request_key'], 'payload_hash' => $hash, 'notas' => $data['notas'] ?? null]);
            $total = 0;
            foreach ($rows as $row) {
                $product = $products->get($row['producto_id']);
                abort_unless($product, 422, 'Un producto ya no está disponible. Actualiza el catálogo.');
                if (isset($row['precio_unitario_cents']) && (int) $row['precio_unitario_cents'] !== (int) $product->price_cents) {
                    throw ValidationException::withMessages(['productos' => 'El precio de '.$product->name.' cambió. Actualiza el catálogo y revisa el total.']);
                }
                $subtotal = (int) $product->price_cents * (int) $row['cantidad'];
                $order->productos()->create(['producto_id' => $product->id, 'nombre' => $product->name, 'cantidad' => (int) $row['cantidad'],
                    'precio_unitario_cents' => $product->price_cents, 'subtotal_cents' => $subtotal]);
                $total += $subtotal;
            }
            $order->update(['folio' => 'OR-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT), 'total_cents' => $total]);
            $this->history($order, $actor, 'creada', 'Orden creada con cliente CRM y precios del catálogo SCM.');

            return $process ? $this->process($order->id, $actor) : $order;
        }, 3);
    }

    public function process(int $id, User $actor): Orden
    {
        return DB::transaction(function () use ($id, $actor) {
            $order = Orden::lockForUpdate()->findOrFail($id);
            if (in_array($order->estado, ['procesada', 'completada'], true)) {
                return $order;
            }
            abort_unless($order->estado === 'pendiente', 409, 'Una orden cancelada no puede procesarse.');
            $client = Cliente::whereKey($order->cliente_id)->lockForUpdate()->firstOrFail();
            abort_unless($client->estado === 'activo', 422, 'Reactiva al cliente en el CRM antes de procesar la orden.');
            $lines = $order->productos()->orderBy('producto_id')->get();
            abort_if($lines->isEmpty(), 422, 'Agrega al menos un producto.');
            $products = Product::whereIn('id', $lines->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($lines as $line) {
                $product = $products->get($line->producto_id);
                if (! $product || $product->stock < $line->cantidad) {
                    throw ValidationException::withMessages(['productos' => 'Stock insuficiente o producto archivado: '.$line->nombre.'. Disponible: '.($product?->stock ?? 0).'.']);
                }
            }
            foreach ($lines as $line) {
                $this->inventory->move($line->producto_id, 'salida', $line->cantidad, 'venta', $actor->id,
                    ['orden_empresarial_id' => $order->id, 'referencia' => 'Venta ERP '.$order->folio]);
            }
            $order->update(['estado' => 'procesada', 'procesada_at' => now()]);
            if ($client->etapa_crm === 'Prospecto') {
                $client->update(['etapa_crm' => 'Activo']);
            }
            $this->history($order, $actor, 'procesada', 'Inventario descontado, movimientos SCM registrados y cliente CRM vinculado.');
            $order->procesos()->create(['usuario_id' => $actor->id, 'tipo' => 'venta', 'estado' => 'completado', 'finalizado_at' => now(),
                'pasos' => ['Cliente CRM validado', 'Precios de catálogo confirmados', 'Existencias verificadas', 'Stock descontado y reglas Push/Pull evaluadas', 'Movimientos e historial guardados']]);

            return $order->fresh();
        }, 3);
    }

    public function changeState(int $id, string $state, User $actor, ?string $reason = null): Orden
    {
        if ($state === 'procesada') {
            abort_unless($actor->hasPermission('erp.write'), 403);

            return $this->process($id, $actor);
        }

        return DB::transaction(function () use ($id, $state, $actor, $reason) {
            $order = Orden::lockForUpdate()->findOrFail($id);
            if ($state === 'completada') {
                abort_unless($actor->hasPermission('erp.complete'), 403, 'Solo administración o logística puede completar entregas.');
                if ($order->estado === 'completada') {
                    return $order;
                }
                abort_unless($order->estado === 'procesada', 409, 'Primero procesa la orden antes de completar su entrega.');
                $order->update(['estado' => 'completada']);
                $this->history($order, $actor, 'completada', 'Entrega confirmada. El inventario ya fue descontado al procesar.');
            } else {
                abort_unless($actor->hasPermission('erp.cancel') || $actor->role === 'admin', 403);
                abort_unless($actor->role === 'admin' || $order->estado === 'pendiente' || $order->estado === 'cancelada', 403, 'Solo el administrador puede cancelar una orden ya procesada.');
                if ($order->estado === 'cancelada') {
                    return $order;
                }
                if (in_array($order->estado, ['procesada', 'completada'], true)) {
                    foreach ($order->productos()->orderBy('producto_id')->get() as $line) {
                        abort_if(! Product::find($line->producto_id), 409, 'El producto está archivado. Reactívalo antes de devolver existencias.');
                        $this->inventory->move($line->producto_id, 'entrada', $line->cantidad, 'ajuste', $actor->id,
                            ['orden_empresarial_id' => $order->id, 'referencia' => 'Cancelación ERP '.$order->folio]);
                    }
                }
                $order->update(['estado' => 'cancelada', 'cancelada_at' => now()]);
                $this->history($order, $actor, 'cancelada', 'Orden cancelada. '.($reason ?? 'Sin observaciones.'));
                $order->procesos()->create(['usuario_id' => $actor->id, 'tipo' => 'cancelacion', 'estado' => 'completado', 'finalizado_at' => now(),
                    'pasos' => ['Orden bloqueada', 'Existencias restituidas si hubo venta', 'Venta excluida de reportes', 'Historial conservado']]);
            }

            return $order->fresh();
        }, 3);
    }

    private function history(Orden $order, User $actor, string $event, string $description): void
    {
        $order->historial()->create(['usuario_id' => $actor->id, 'evento' => $event, 'descripcion' => $description, 'fecha' => now()]);
    }
}
```

## app/Services/ScmReports.php

```php
<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ScmReports
{
    public function summary(): array
    {
        $products = Product::with(['proveedor', 'inventario'])->orderBy('id')->get();
        $cutoff = now()->subDays(30);
        $sales = MovimientoInventario::query()->where('tipo', 'salida')->where('motivo', 'venta')
            ->where(fn ($q) => $q->whereNull('order_id')->orWhereIn('order_id', DB::table('orders')->select('id')->whereIn('status', ['Enviado', 'Entregado'])))
            ->where(fn ($q) => $q->whereNull('orden_empresarial_id')->orWhereIn('orden_empresarial_id', DB::table('ordenes_empresariales')->select('id')->whereIn('estado', ['procesada', 'completada'])));
        $allSales = (clone $sales)->selectRaw('producto_id, SUM(cantidad) as total')->groupBy('producto_id')->pluck('total', 'producto_id');
        $recent = (clone $sales)->where('fecha', '>=', $cutoff)->selectRaw('producto_id, SUM(cantidad) as total')->groupBy('producto_id')->pluck('total', 'producto_id');
        $top = $products->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'unidades' => (int) ($allSales[$p->id] ?? 0)])->filter(fn ($p) => $p['unidades'] > 0)->sortByDesc('unidades')->take(10)->values();
        $slow = $products->filter(fn ($p) => $p->stock > 0 && (int) ($recent[$p->id] ?? 0) <= 2)->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'stock_actual' => $p->stock, 'unidades_30_dias' => (int) ($recent[$p->id] ?? 0)])->values();

        return ['total_productos' => $products->count(), 'unidades_stock' => $products->sum('stock'),
            'valor_inventario_cents' => $products->sum(fn ($p) => $p->stock * $p->costo_unitario_cents),
            'pedidos_pendientes' => Pedido::where('estado', 'pendiente')->count(),
            'inventario_critico' => $products->filter(fn ($p) => $p->stock <= $p->stock_minimo)->map->scmPresentation()->values(),
            'productos_mas_vendidos' => $top, 'rotacion_lenta' => $slow, 'periodo_rotacion_dias' => 30, 'umbral_rotacion_unidades' => 2,
            'comparacion' => collect(['PUSH', 'PULL'])->map(function ($strategy) use ($products) {
                $group = $products->where('estrategia_logistica', $strategy);

                return ['estrategia' => $strategy, 'productos' => $group->count(), 'stock' => $group->sum('stock'), 'criticos' => $group->filter(fn ($p) => $p->stock <= $p->stock_minimo)->count()];
            })->values()];
    }
}
```

## app/Support/Permissions.php

```php
<?php

namespace App\Support;

final class Permissions
{
    public const ROLES = ['admin', 'ventas', 'logistica', 'usuario', 'cliente'];

    public static function forRole(string $role): array
    {
        return match ($role) {
            'admin' => ['crm', 'scm.read', 'scm.operate', 'scm.manage', 'erp.read', 'erp.write', 'erp.complete', 'erp.cancel', 'erp.resources', 'erp.admin', 'users'],
            'ventas' => ['crm', 'scm.read', 'erp.read', 'erp.write', 'erp.cancel'],
            'logistica' => ['scm.read', 'scm.operate', 'scm.manage', 'erp.read', 'erp.complete', 'erp.resources'],
            // Conserva los permisos operativos de las cuentas de la Etapa 2.
            'usuario' => ['crm', 'scm.read', 'scm.operate', 'erp.read', 'erp.write', 'erp.complete', 'erp.cancel'],
            default => [],
        };
    }

    public static function label(string $role): string
    {
        return ['admin' => 'Administrador', 'ventas' => 'Ventas', 'logistica' => 'Logística', 'usuario' => 'Operador', 'cliente' => 'Cliente'][$role] ?? $role;
    }
}
```

## bootstrap/app.php

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => \App\Http\Middleware\RequireRole::class, 'permission' => \App\Http\Middleware\RequirePermission::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

## database/migrations/2026_10_29_000001_create_erp_tables.php

```php
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
```

## package-lock.json

```json
{
    "name": "Negocios-II-U3",
    "lockfileVersion": 3,
    "requires": true,
    "packages": {
        "": {
            "dependencies": {
                "alpinejs": "3.14.9",
                "jspdf": "3.0.4",
                "jspdf-autotable": "5.0.2"
            },
            "devDependencies": {
                "@tailwindcss/vite": "^4.0.0",
                "autoprefixer": "^10.4.24",
                "axios": "^1.11.0",
                "concurrently": "^9.0.1",
                "jsdom": "^26.1.0",
                "laravel-vite-plugin": "^2.0.0",
                "postcss": "^8.5.6",
                "tailwindcss": "^4.1.18",
                "vite": "^7.0.7"
            }
        },
        "node_modules/@asamuzakjp/css-color": {
            "version": "3.2.0",
            "resolved": "https://registry.npmjs.org/@asamuzakjp/css-color/-/css-color-3.2.0.tgz",
            "integrity": "sha512-K1A6z8tS3XsmCMM86xoWdn7Fkdn9m6RSVtocUrJYIwZnFVkng/PvkEoWtOWmP+Scc6saYWHWZYbndEEXxl24jw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@csstools/css-calc": "^2.1.3",
                "@csstools/css-color-parser": "^3.0.9",
                "@csstools/css-parser-algorithms": "^3.0.4",
                "@csstools/css-tokenizer": "^3.0.3",
                "lru-cache": "^10.4.3"
            }
        },
        "node_modules/@babel/runtime": {
            "version": "7.29.7",
            "resolved": "https://registry.npmjs.org/@babel/runtime/-/runtime-7.29.7.tgz",
            "integrity": "sha512-Nq8OhGWiZIZGV6hLHoyAKLLcJihP/xFeBMGJoUrxTX2psI8dCifzLhZISFb+VWS3wFMRDmCGw5R+dOySCqPLhw==",
            "license": "MIT",
            "engines": {
                "node": ">=6.9.0"
            }
        },
        "node_modules/@csstools/color-helpers": {
            "version": "5.1.0",
            "resolved": "https://registry.npmjs.org/@csstools/color-helpers/-/color-helpers-5.1.0.tgz",
            "integrity": "sha512-S11EXWJyy0Mz5SYvRmY8nJYTFFd1LCNV+7cXyAgQtOOuzb4EsgfqDufL+9esx72/eLhsRdGZwaldu/h+E4t4BA==",
            "dev": true,
            "funding": [
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/csstools"
                },
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/csstools"
                }
            ],
            "license": "MIT-0",
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@csstools/css-calc": {
            "version": "2.1.4",
            "resolved": "https://registry.npmjs.org/@csstools/css-calc/-/css-calc-2.1.4.tgz",
            "integrity": "sha512-3N8oaj+0juUw/1H3YwmDDJXCgTB1gKU6Hc/bB502u9zR0q2vd786XJH9QfrKIEgFlZmhZiq6epXl4rHqhzsIgQ==",
            "dev": true,
            "funding": [
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/csstools"
                },
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/csstools"
                }
            ],
            "license": "MIT",
            "engines": {
                "node": ">=18"
            },
            "peerDependencies": {
                "@csstools/css-parser-algorithms": "^3.0.5",
                "@csstools/css-tokenizer": "^3.0.4"
            }
        },
        "node_modules/@csstools/css-color-parser": {
            "version": "3.1.0",
            "resolved": "https://registry.npmjs.org/@csstools/css-color-parser/-/css-color-parser-3.1.0.tgz",
            "integrity": "sha512-nbtKwh3a6xNVIp/VRuXV64yTKnb1IjTAEEh3irzS+HkKjAOYLTGNb9pmVNntZ8iVBHcWDA2Dof0QtPgFI1BaTA==",
            "dev": true,
            "funding": [
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/csstools"
                },
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/csstools"
                }
            ],
            "license": "MIT",
            "dependencies": {
                "@csstools/color-helpers": "^5.1.0",
                "@csstools/css-calc": "^2.1.4"
            },
            "engines": {
                "node": ">=18"
            },
            "peerDependencies": {
                "@csstools/css-parser-algorithms": "^3.0.5",
                "@csstools/css-tokenizer": "^3.0.4"
            }
        },
        "node_modules/@csstools/css-parser-algorithms": {
            "version": "3.0.5",
            "resolved": "https://registry.npmjs.org/@csstools/css-parser-algorithms/-/css-parser-algorithms-3.0.5.tgz",
            "integrity": "sha512-DaDeUkXZKjdGhgYaHNJTV9pV7Y9B3b644jCLs9Upc3VeNGg6LWARAT6O+Q+/COo+2gg/bM5rhpMAtf70WqfBdQ==",
            "dev": true,
            "funding": [
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/csstools"
                },
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/csstools"
                }
            ],
            "license": "MIT",
            "engines": {
                "node": ">=18"
            },
            "peerDependencies": {
                "@csstools/css-tokenizer": "^3.0.4"
            }
        },
        "node_modules/@csstools/css-tokenizer": {
            "version": "3.0.4",
            "resolved": "https://registry.npmjs.org/@csstools/css-tokenizer/-/css-tokenizer-3.0.4.tgz",
            "integrity": "sha512-Vd/9EVDiu6PPJt9yAh6roZP6El1xHrdvIVGjyBsHR0RYwNHgL7FJPyIIW4fANJNG6FtyZfvlRPpFI4ZM/lubvw==",
            "dev": true,
            "funding": [
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/csstools"
                },
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/csstools"
                }
            ],
            "license": "MIT",
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/aix-ppc64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/aix-ppc64/-/aix-ppc64-0.27.3.tgz",
            "integrity": "sha512-9fJMTNFTWZMh5qwrBItuziu834eOCUcEqymSH7pY+zoMVEZg3gcPuBNxH1EvfVYe9h0x/Ptw8KBzv7qxb7l8dg==",
            "cpu": [
                "ppc64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "aix"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/android-arm": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/android-arm/-/android-arm-0.27.3.tgz",
            "integrity": "sha512-i5D1hPY7GIQmXlXhs2w8AWHhenb00+GxjxRncS2ZM7YNVGNfaMxgzSGuO8o8SJzRc/oZwU2bcScvVERk03QhzA==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/android-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/android-arm64/-/android-arm64-0.27.3.tgz",
            "integrity": "sha512-YdghPYUmj/FX2SYKJ0OZxf+iaKgMsKHVPF1MAq/P8WirnSpCStzKJFjOjzsW0QQ7oIAiccHdcqjbHmJxRb/dmg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/android-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/android-x64/-/android-x64-0.27.3.tgz",
            "integrity": "sha512-IN/0BNTkHtk8lkOM8JWAYFg4ORxBkZQf9zXiEOfERX/CzxW3Vg1ewAhU7QSWQpVIzTW+b8Xy+lGzdYXV6UZObQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/darwin-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/darwin-arm64/-/darwin-arm64-0.27.3.tgz",
            "integrity": "sha512-Re491k7ByTVRy0t3EKWajdLIr0gz2kKKfzafkth4Q8A5n1xTHrkqZgLLjFEHVD+AXdUGgQMq+Godfq45mGpCKg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/darwin-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/darwin-x64/-/darwin-x64-0.27.3.tgz",
            "integrity": "sha512-vHk/hA7/1AckjGzRqi6wbo+jaShzRowYip6rt6q7VYEDX4LEy1pZfDpdxCBnGtl+A5zq8iXDcyuxwtv3hNtHFg==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/freebsd-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/freebsd-arm64/-/freebsd-arm64-0.27.3.tgz",
            "integrity": "sha512-ipTYM2fjt3kQAYOvo6vcxJx3nBYAzPjgTCk7QEgZG8AUO3ydUhvelmhrbOheMnGOlaSFUoHXB6un+A7q4ygY9w==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/freebsd-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/freebsd-x64/-/freebsd-x64-0.27.3.tgz",
            "integrity": "sha512-dDk0X87T7mI6U3K9VjWtHOXqwAMJBNN2r7bejDsc+j03SEjtD9HrOl8gVFByeM0aJksoUuUVU9TBaZa2rgj0oA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-arm": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-arm/-/linux-arm-0.27.3.tgz",
            "integrity": "sha512-s6nPv2QkSupJwLYyfS+gwdirm0ukyTFNl3KTgZEAiJDd+iHZcbTPPcWCcRYH+WlNbwChgH2QkE9NSlNrMT8Gfw==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-arm64/-/linux-arm64-0.27.3.tgz",
            "integrity": "sha512-sZOuFz/xWnZ4KH3YfFrKCf1WyPZHakVzTiqji3WDc0BCl2kBwiJLCXpzLzUBLgmp4veFZdvN5ChW4Eq/8Fc2Fg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-ia32": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-ia32/-/linux-ia32-0.27.3.tgz",
            "integrity": "sha512-yGlQYjdxtLdh0a3jHjuwOrxQjOZYD/C9PfdbgJJF3TIZWnm/tMd/RcNiLngiu4iwcBAOezdnSLAwQDPqTmtTYg==",
            "cpu": [
                "ia32"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-loong64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-loong64/-/linux-loong64-0.27.3.tgz",
            "integrity": "sha512-WO60Sn8ly3gtzhyjATDgieJNet/KqsDlX5nRC5Y3oTFcS1l0KWba+SEa9Ja1GfDqSF1z6hif/SkpQJbL63cgOA==",
            "cpu": [
                "loong64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-mips64el": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-mips64el/-/linux-mips64el-0.27.3.tgz",
            "integrity": "sha512-APsymYA6sGcZ4pD6k+UxbDjOFSvPWyZhjaiPyl/f79xKxwTnrn5QUnXR5prvetuaSMsb4jgeHewIDCIWljrSxw==",
            "cpu": [
                "mips64el"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-ppc64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-ppc64/-/linux-ppc64-0.27.3.tgz",
            "integrity": "sha512-eizBnTeBefojtDb9nSh4vvVQ3V9Qf9Df01PfawPcRzJH4gFSgrObw+LveUyDoKU3kxi5+9RJTCWlj4FjYXVPEA==",
            "cpu": [
                "ppc64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-riscv64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-riscv64/-/linux-riscv64-0.27.3.tgz",
            "integrity": "sha512-3Emwh0r5wmfm3ssTWRQSyVhbOHvqegUDRd0WhmXKX2mkHJe1SFCMJhagUleMq+Uci34wLSipf8Lagt4LlpRFWQ==",
            "cpu": [
                "riscv64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-s390x": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-s390x/-/linux-s390x-0.27.3.tgz",
            "integrity": "sha512-pBHUx9LzXWBc7MFIEEL0yD/ZVtNgLytvx60gES28GcWMqil8ElCYR4kvbV2BDqsHOvVDRrOxGySBM9Fcv744hw==",
            "cpu": [
                "s390x"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/linux-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/linux-x64/-/linux-x64-0.27.3.tgz",
            "integrity": "sha512-Czi8yzXUWIQYAtL/2y6vogER8pvcsOsk5cpwL4Gk5nJqH5UZiVByIY8Eorm5R13gq+DQKYg0+JyQoytLQas4dA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/netbsd-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/netbsd-arm64/-/netbsd-arm64-0.27.3.tgz",
            "integrity": "sha512-sDpk0RgmTCR/5HguIZa9n9u+HVKf40fbEUt+iTzSnCaGvY9kFP0YKBWZtJaraonFnqef5SlJ8/TiPAxzyS+UoA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "netbsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/netbsd-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/netbsd-x64/-/netbsd-x64-0.27.3.tgz",
            "integrity": "sha512-P14lFKJl/DdaE00LItAukUdZO5iqNH7+PjoBm+fLQjtxfcfFE20Xf5CrLsmZdq5LFFZzb5JMZ9grUwvtVYzjiA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "netbsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/openbsd-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/openbsd-arm64/-/openbsd-arm64-0.27.3.tgz",
            "integrity": "sha512-AIcMP77AvirGbRl/UZFTq5hjXK+2wC7qFRGoHSDrZ5v5b8DK/GYpXW3CPRL53NkvDqb9D+alBiC/dV0Fb7eJcw==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openbsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/openbsd-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/openbsd-x64/-/openbsd-x64-0.27.3.tgz",
            "integrity": "sha512-DnW2sRrBzA+YnE70LKqnM3P+z8vehfJWHXECbwBmH/CU51z6FiqTQTHFenPlHmo3a8UgpLyH3PT+87OViOh1AQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openbsd"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/openharmony-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/openharmony-arm64/-/openharmony-arm64-0.27.3.tgz",
            "integrity": "sha512-NinAEgr/etERPTsZJ7aEZQvvg/A6IsZG/LgZy+81wON2huV7SrK3e63dU0XhyZP4RKGyTm7aOgmQk0bGp0fy2g==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openharmony"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/sunos-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/sunos-x64/-/sunos-x64-0.27.3.tgz",
            "integrity": "sha512-PanZ+nEz+eWoBJ8/f8HKxTTD172SKwdXebZ0ndd953gt1HRBbhMsaNqjTyYLGLPdoWHy4zLU7bDVJztF5f3BHA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "sunos"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/win32-arm64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/win32-arm64/-/win32-arm64-0.27.3.tgz",
            "integrity": "sha512-B2t59lWWYrbRDw/tjiWOuzSsFh1Y/E95ofKz7rIVYSQkUYBjfSgf6oeYPNWHToFRr2zx52JKApIcAS/D5TUBnA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/win32-ia32": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/win32-ia32/-/win32-ia32-0.27.3.tgz",
            "integrity": "sha512-QLKSFeXNS8+tHW7tZpMtjlNb7HKau0QDpwm49u0vUp9y1WOF+PEzkU84y9GqYaAVW8aH8f3GcBck26jh54cX4Q==",
            "cpu": [
                "ia32"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@esbuild/win32-x64": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/@esbuild/win32-x64/-/win32-x64-0.27.3.tgz",
            "integrity": "sha512-4uJGhsxuptu3OcpVAzli+/gWusVGwZZHTlS63hh++ehExkVT8SgiEf7/uC/PclrPPkLhZqGgCTjd0VWLo6xMqA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/@jridgewell/gen-mapping": {
            "version": "0.3.13",
            "resolved": "https://registry.npmjs.org/@jridgewell/gen-mapping/-/gen-mapping-0.3.13.tgz",
            "integrity": "sha512-2kkt/7niJ6MgEPxF0bYdQ6etZaA+fQvDcLKckhy1yIQOzaoKjBBjSj63/aLVjYE3qhRt5dvM+uUyfCg6UKCBbA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/sourcemap-codec": "^1.5.0",
                "@jridgewell/trace-mapping": "^0.3.24"
            }
        },
        "node_modules/@jridgewell/remapping": {
            "version": "2.3.5",
            "resolved": "https://registry.npmjs.org/@jridgewell/remapping/-/remapping-2.3.5.tgz",
            "integrity": "sha512-LI9u/+laYG4Ds1TDKSJW2YPrIlcVYOwi2fUC6xB43lueCjgxV4lffOCZCtYFiH6TNOX+tQKXx97T4IKHbhyHEQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/gen-mapping": "^0.3.5",
                "@jridgewell/trace-mapping": "^0.3.24"
            }
        },
        "node_modules/@jridgewell/resolve-uri": {
            "version": "3.1.2",
            "resolved": "https://registry.npmjs.org/@jridgewell/resolve-uri/-/resolve-uri-3.1.2.tgz",
            "integrity": "sha512-bRISgCIjP20/tbWSPWMEi54QVPRZExkuD9lJL+UIxUKtwVJA8wW1Trb1jMs1RFXo1CBTNZ/5hpC9QvmKWdopKw==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=6.0.0"
            }
        },
        "node_modules/@jridgewell/sourcemap-codec": {
            "version": "1.5.5",
            "resolved": "https://registry.npmjs.org/@jridgewell/sourcemap-codec/-/sourcemap-codec-1.5.5.tgz",
            "integrity": "sha512-cYQ9310grqxueWbl+WuIUIaiUaDcj7WOq5fVhEljNVgRfOUhY9fy2zTvfoqWsnebh8Sl70VScFbICvJnLKB0Og==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/@jridgewell/trace-mapping": {
            "version": "0.3.31",
            "resolved": "https://registry.npmjs.org/@jridgewell/trace-mapping/-/trace-mapping-0.3.31.tgz",
            "integrity": "sha512-zzNR+SdQSDJzc8joaeP8QQoCQr8NuYx2dIIytl1QeBEZHJ9uW6hebsrYgbz8hJwUQao3TWCMtmfV8Nu1twOLAw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/resolve-uri": "^3.1.0",
                "@jridgewell/sourcemap-codec": "^1.4.14"
            }
        },
        "node_modules/@rollup/rollup-android-arm-eabi": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-android-arm-eabi/-/rollup-android-arm-eabi-4.57.1.tgz",
            "integrity": "sha512-A6ehUVSiSaaliTxai040ZpZ2zTevHYbvu/lDoeAteHI8QnaosIzm4qwtezfRg1jOYaUmnzLX1AOD6Z+UJjtifg==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ]
        },
        "node_modules/@rollup/rollup-android-arm64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-android-arm64/-/rollup-android-arm64-4.57.1.tgz",
            "integrity": "sha512-dQaAddCY9YgkFHZcFNS/606Exo8vcLHwArFZ7vxXq4rigo2bb494/xKMMwRRQW6ug7Js6yXmBZhSBRuBvCCQ3w==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ]
        },
        "node_modules/@rollup/rollup-darwin-arm64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-darwin-arm64/-/rollup-darwin-arm64-4.57.1.tgz",
            "integrity": "sha512-crNPrwJOrRxagUYeMn/DZwqN88SDmwaJ8Cvi/TN1HnWBU7GwknckyosC2gd0IqYRsHDEnXf328o9/HC6OkPgOg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ]
        },
        "node_modules/@rollup/rollup-darwin-x64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-darwin-x64/-/rollup-darwin-x64-4.57.1.tgz",
            "integrity": "sha512-Ji8g8ChVbKrhFtig5QBV7iMaJrGtpHelkB3lsaKzadFBe58gmjfGXAOfI5FV0lYMH8wiqsxKQ1C9B0YTRXVy4w==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ]
        },
        "node_modules/@rollup/rollup-freebsd-arm64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-freebsd-arm64/-/rollup-freebsd-arm64-4.57.1.tgz",
            "integrity": "sha512-R+/WwhsjmwodAcz65guCGFRkMb4gKWTcIeLy60JJQbXrJ97BOXHxnkPFrP+YwFlaS0m+uWJTstrUA9o+UchFug==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ]
        },
        "node_modules/@rollup/rollup-freebsd-x64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-freebsd-x64/-/rollup-freebsd-x64-4.57.1.tgz",
            "integrity": "sha512-IEQTCHeiTOnAUC3IDQdzRAGj3jOAYNr9kBguI7MQAAZK3caezRrg0GxAb6Hchg4lxdZEI5Oq3iov/w/hnFWY9Q==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ]
        },
        "node_modules/@rollup/rollup-linux-arm-gnueabihf": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-arm-gnueabihf/-/rollup-linux-arm-gnueabihf-4.57.1.tgz",
            "integrity": "sha512-F8sWbhZ7tyuEfsmOxwc2giKDQzN3+kuBLPwwZGyVkLlKGdV1nvnNwYD0fKQ8+XS6hp9nY7B+ZeK01EBUE7aHaw==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-arm-musleabihf": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-arm-musleabihf/-/rollup-linux-arm-musleabihf-4.57.1.tgz",
            "integrity": "sha512-rGfNUfn0GIeXtBP1wL5MnzSj98+PZe/AXaGBCRmT0ts80lU5CATYGxXukeTX39XBKsxzFpEeK+Mrp9faXOlmrw==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-arm64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-arm64-gnu/-/rollup-linux-arm64-gnu-4.57.1.tgz",
            "integrity": "sha512-MMtej3YHWeg/0klK2Qodf3yrNzz6CGjo2UntLvk2RSPlhzgLvYEB3frRvbEF2wRKh1Z2fDIg9KRPe1fawv7C+g==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-arm64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-arm64-musl/-/rollup-linux-arm64-musl-4.57.1.tgz",
            "integrity": "sha512-1a/qhaaOXhqXGpMFMET9VqwZakkljWHLmZOX48R0I/YLbhdxr1m4gtG1Hq7++VhVUmf+L3sTAf9op4JlhQ5u1Q==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-loong64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-loong64-gnu/-/rollup-linux-loong64-gnu-4.57.1.tgz",
            "integrity": "sha512-QWO6RQTZ/cqYtJMtxhkRkidoNGXc7ERPbZN7dVW5SdURuLeVU7lwKMpo18XdcmpWYd0qsP1bwKPf7DNSUinhvA==",
            "cpu": [
                "loong64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-loong64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-loong64-musl/-/rollup-linux-loong64-musl-4.57.1.tgz",
            "integrity": "sha512-xpObYIf+8gprgWaPP32xiN5RVTi/s5FCR+XMXSKmhfoJjrpRAjCuuqQXyxUa/eJTdAE6eJ+KDKaoEqjZQxh3Gw==",
            "cpu": [
                "loong64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-ppc64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-ppc64-gnu/-/rollup-linux-ppc64-gnu-4.57.1.tgz",
            "integrity": "sha512-4BrCgrpZo4hvzMDKRqEaW1zeecScDCR+2nZ86ATLhAoJ5FQ+lbHVD3ttKe74/c7tNT9c6F2viwB3ufwp01Oh2w==",
            "cpu": [
                "ppc64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-ppc64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-ppc64-musl/-/rollup-linux-ppc64-musl-4.57.1.tgz",
            "integrity": "sha512-NOlUuzesGauESAyEYFSe3QTUguL+lvrN1HtwEEsU2rOwdUDeTMJdO5dUYl/2hKf9jWydJrO9OL/XSSf65R5+Xw==",
            "cpu": [
                "ppc64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-riscv64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-riscv64-gnu/-/rollup-linux-riscv64-gnu-4.57.1.tgz",
            "integrity": "sha512-ptA88htVp0AwUUqhVghwDIKlvJMD/fmL/wrQj99PRHFRAG6Z5nbWoWG4o81Nt9FT+IuqUQi+L31ZKAFeJ5Is+A==",
            "cpu": [
                "riscv64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-riscv64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-riscv64-musl/-/rollup-linux-riscv64-musl-4.57.1.tgz",
            "integrity": "sha512-S51t7aMMTNdmAMPpBg7OOsTdn4tySRQvklmL3RpDRyknk87+Sp3xaumlatU+ppQ+5raY7sSTcC2beGgvhENfuw==",
            "cpu": [
                "riscv64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-s390x-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-s390x-gnu/-/rollup-linux-s390x-gnu-4.57.1.tgz",
            "integrity": "sha512-Bl00OFnVFkL82FHbEqy3k5CUCKH6OEJL54KCyx2oqsmZnFTR8IoNqBF+mjQVcRCT5sB6yOvK8A37LNm/kPJiZg==",
            "cpu": [
                "s390x"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-x64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-x64-gnu/-/rollup-linux-x64-gnu-4.57.1.tgz",
            "integrity": "sha512-ABca4ceT4N+Tv/GtotnWAeXZUZuM/9AQyCyKYyKnpk4yoA7QIAuBt6Hkgpw8kActYlew2mvckXkvx0FfoInnLg==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-linux-x64-musl": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-linux-x64-musl/-/rollup-linux-x64-musl-4.57.1.tgz",
            "integrity": "sha512-HFps0JeGtuOR2convgRRkHCekD7j+gdAuXM+/i6kGzQtFhlCtQkpwtNzkNj6QhCDp7DRJ7+qC/1Vg2jt5iSOFw==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ]
        },
        "node_modules/@rollup/rollup-openbsd-x64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-openbsd-x64/-/rollup-openbsd-x64-4.57.1.tgz",
            "integrity": "sha512-H+hXEv9gdVQuDTgnqD+SQffoWoc0Of59AStSzTEj/feWTBAnSfSD3+Dql1ZruJQxmykT/JVY0dE8Ka7z0DH1hw==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openbsd"
            ]
        },
        "node_modules/@rollup/rollup-openharmony-arm64": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-openharmony-arm64/-/rollup-openharmony-arm64-4.57.1.tgz",
            "integrity": "sha512-4wYoDpNg6o/oPximyc/NG+mYUejZrCU2q+2w6YZqrAs2UcNUChIZXjtafAiiZSUc7On8v5NyNj34Kzj/Ltk6dQ==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "openharmony"
            ]
        },
        "node_modules/@rollup/rollup-win32-arm64-msvc": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-win32-arm64-msvc/-/rollup-win32-arm64-msvc-4.57.1.tgz",
            "integrity": "sha512-O54mtsV/6LW3P8qdTcamQmuC990HDfR71lo44oZMZlXU4tzLrbvTii87Ni9opq60ds0YzuAlEr/GNwuNluZyMQ==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ]
        },
        "node_modules/@rollup/rollup-win32-ia32-msvc": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-win32-ia32-msvc/-/rollup-win32-ia32-msvc-4.57.1.tgz",
            "integrity": "sha512-P3dLS+IerxCT/7D2q2FYcRdWRl22dNbrbBEtxdWhXrfIMPP9lQhb5h4Du04mdl5Woq05jVCDPCMF7Ub0NAjIew==",
            "cpu": [
                "ia32"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ]
        },
        "node_modules/@rollup/rollup-win32-x64-gnu": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-win32-x64-gnu/-/rollup-win32-x64-gnu-4.57.1.tgz",
            "integrity": "sha512-VMBH2eOOaKGtIJYleXsi2B8CPVADrh+TyNxJ4mWPnKfLB/DBUmzW+5m1xUrcwWoMfSLagIRpjUFeW5CO5hyciQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ]
        },
        "node_modules/@rollup/rollup-win32-x64-msvc": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/@rollup/rollup-win32-x64-msvc/-/rollup-win32-x64-msvc-4.57.1.tgz",
            "integrity": "sha512-mxRFDdHIWRxg3UfIIAwCm6NzvxG0jDX/wBN6KsQFTvKFqqg9vTrWUE68qEjHt19A5wwx5X5aUi2zuZT7YR0jrA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ]
        },
        "node_modules/@tailwindcss/node": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/node/-/node-4.1.18.tgz",
            "integrity": "sha512-DoR7U1P7iYhw16qJ49fgXUlry1t4CpXeErJHnQ44JgTSKMaZUdf17cfn5mHchfJ4KRBZRFA/Coo+MUF5+gOaCQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/remapping": "^2.3.4",
                "enhanced-resolve": "^5.18.3",
                "jiti": "^2.6.1",
                "lightningcss": "1.30.2",
                "magic-string": "^0.30.21",
                "source-map-js": "^1.2.1",
                "tailwindcss": "4.1.18"
            }
        },
        "node_modules/@tailwindcss/oxide": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide/-/oxide-4.1.18.tgz",
            "integrity": "sha512-EgCR5tTS5bUSKQgzeMClT6iCY3ToqE1y+ZB0AKldj809QXk1Y+3jB0upOYZrn9aGIzPtUsP7sX4QQ4XtjBB95A==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 10"
            },
            "optionalDependencies": {
                "@tailwindcss/oxide-android-arm64": "4.1.18",
                "@tailwindcss/oxide-darwin-arm64": "4.1.18",
                "@tailwindcss/oxide-darwin-x64": "4.1.18",
                "@tailwindcss/oxide-freebsd-x64": "4.1.18",
                "@tailwindcss/oxide-linux-arm-gnueabihf": "4.1.18",
                "@tailwindcss/oxide-linux-arm64-gnu": "4.1.18",
                "@tailwindcss/oxide-linux-arm64-musl": "4.1.18",
                "@tailwindcss/oxide-linux-x64-gnu": "4.1.18",
                "@tailwindcss/oxide-linux-x64-musl": "4.1.18",
                "@tailwindcss/oxide-wasm32-wasi": "4.1.18",
                "@tailwindcss/oxide-win32-arm64-msvc": "4.1.18",
                "@tailwindcss/oxide-win32-x64-msvc": "4.1.18"
            }
        },
        "node_modules/@tailwindcss/oxide-android-arm64": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-android-arm64/-/oxide-android-arm64-4.1.18.tgz",
            "integrity": "sha512-dJHz7+Ugr9U/diKJA0W6N/6/cjI+ZTAoxPf9Iz9BFRF2GzEX8IvXxFIi/dZBloVJX/MZGvRuFA9rqwdiIEZQ0Q==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-darwin-arm64": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-darwin-arm64/-/oxide-darwin-arm64-4.1.18.tgz",
            "integrity": "sha512-Gc2q4Qhs660bhjyBSKgq6BYvwDz4G+BuyJ5H1xfhmDR3D8HnHCmT/BSkvSL0vQLy/nkMLY20PQ2OoYMO15Jd0A==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-darwin-x64": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-darwin-x64/-/oxide-darwin-x64-4.1.18.tgz",
            "integrity": "sha512-FL5oxr2xQsFrc3X9o1fjHKBYBMD1QZNyc1Xzw/h5Qu4XnEBi3dZn96HcHm41c/euGV+GRiXFfh2hUCyKi/e+yw==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-freebsd-x64": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-freebsd-x64/-/oxide-freebsd-x64-4.1.18.tgz",
            "integrity": "sha512-Fj+RHgu5bDodmV1dM9yAxlfJwkkWvLiRjbhuO2LEtwtlYlBgiAT4x/j5wQr1tC3SANAgD+0YcmWVrj8R9trVMA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "freebsd"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-arm-gnueabihf": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-arm-gnueabihf/-/oxide-linux-arm-gnueabihf-4.1.18.tgz",
            "integrity": "sha512-Fp+Wzk/Ws4dZn+LV2Nqx3IilnhH51YZoRaYHQsVq3RQvEl+71VGKFpkfHrLM/Li+kt5c0DJe/bHXK1eHgDmdiA==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-arm64-gnu": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-arm64-gnu/-/oxide-linux-arm64-gnu-4.1.18.tgz",
            "integrity": "sha512-S0n3jboLysNbh55Vrt7pk9wgpyTTPD0fdQeh7wQfMqLPM/Hrxi+dVsLsPrycQjGKEQk85Kgbx+6+QnYNiHalnw==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-arm64-musl": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-arm64-musl/-/oxide-linux-arm64-musl-4.1.18.tgz",
            "integrity": "sha512-1px92582HkPQlaaCkdRcio71p8bc8i/ap5807tPRDK/uw953cauQBT8c5tVGkOwrHMfc2Yh6UuxaH4vtTjGvHg==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-x64-gnu": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-x64-gnu/-/oxide-linux-x64-gnu-4.1.18.tgz",
            "integrity": "sha512-v3gyT0ivkfBLoZGF9LyHmts0Isc8jHZyVcbzio6Wpzifg/+5ZJpDiRiUhDLkcr7f/r38SWNe7ucxmGW3j3Kb/g==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-linux-x64-musl": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-linux-x64-musl/-/oxide-linux-x64-musl-4.1.18.tgz",
            "integrity": "sha512-bhJ2y2OQNlcRwwgOAGMY0xTFStt4/wyU6pvI6LSuZpRgKQwxTec0/3Scu91O8ir7qCR3AuepQKLU/kX99FouqQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-wasm32-wasi": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-wasm32-wasi/-/oxide-wasm32-wasi-4.1.18.tgz",
            "integrity": "sha512-LffYTvPjODiP6PT16oNeUQJzNVyJl1cjIebq/rWWBF+3eDst5JGEFSc5cWxyRCJ0Mxl+KyIkqRxk1XPEs9x8TA==",
            "bundleDependencies": [
                "@napi-rs/wasm-runtime",
                "@emnapi/core",
                "@emnapi/runtime",
                "@tybys/wasm-util",
                "@emnapi/wasi-threads",
                "tslib"
            ],
            "cpu": [
                "wasm32"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "@emnapi/core": "^1.7.1",
                "@emnapi/runtime": "^1.7.1",
                "@emnapi/wasi-threads": "^1.1.0",
                "@napi-rs/wasm-runtime": "^1.1.0",
                "@tybys/wasm-util": "^0.10.1",
                "tslib": "^2.4.0"
            },
            "engines": {
                "node": ">=14.0.0"
            }
        },
        "node_modules/@tailwindcss/oxide-wasm32-wasi/node_modules/@emnapi/core": {
            "version": "1.7.1",
            "dev": true,
            "inBundle": true,
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "@emnapi/wasi-threads": "1.1.0",
                "tslib": "^2.4.0"
            }
        },
        "node_modules/@tailwindcss/oxide-wasm32-wasi/node_modules/@emnapi/runtime": {
            "version": "1.7.1",
            "dev": true,
            "inBundle": true,
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "tslib": "^2.4.0"
            }
        },
        "node_modules/@tailwindcss/oxide-wasm32-wasi/node_modules/@emnapi/wasi-threads": {
            "version": "1.1.0",
            "dev": true,
            "inBundle": true,
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "tslib": "^2.4.0"
            }
        },
        "node_modules/@tailwindcss/oxide-wasm32-wasi/node_modules/@napi-rs/wasm-runtime": {
            "version": "1.1.0",
            "dev": true,
            "inBundle": true,
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "@emnapi/core": "^1.7.1",
                "@emnapi/runtime": "^1.7.1",
                "@tybys/wasm-util": "^0.10.1"
            }
        },
        "node_modules/@tailwindcss/oxide-wasm32-wasi/node_modules/@tybys/wasm-util": {
            "version": "0.10.1",
            "dev": true,
            "inBundle": true,
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "tslib": "^2.4.0"
            }
        },
        "node_modules/@tailwindcss/oxide-wasm32-wasi/node_modules/tslib": {
            "version": "2.8.1",
            "dev": true,
            "inBundle": true,
            "license": "0BSD",
            "optional": true
        },
        "node_modules/@tailwindcss/oxide-win32-arm64-msvc": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-win32-arm64-msvc/-/oxide-win32-arm64-msvc-4.1.18.tgz",
            "integrity": "sha512-HjSA7mr9HmC8fu6bdsZvZ+dhjyGCLdotjVOgLA2vEqxEBZaQo9YTX4kwgEvPCpRh8o4uWc4J/wEoFzhEmjvPbA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/oxide-win32-x64-msvc": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/oxide-win32-x64-msvc/-/oxide-win32-x64-msvc-4.1.18.tgz",
            "integrity": "sha512-bJWbyYpUlqamC8dpR7pfjA0I7vdF6t5VpUGMWRkXVE3AXgIZjYUYAK7II1GNaxR8J1SSrSrppRar8G++JekE3Q==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">= 10"
            }
        },
        "node_modules/@tailwindcss/vite": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/@tailwindcss/vite/-/vite-4.1.18.tgz",
            "integrity": "sha512-jVA+/UpKL1vRLg6Hkao5jldawNmRo7mQYrZtNHMIVpLfLhDml5nMRUo/8MwoX2vNXvnaXNNMedrMfMugAVX1nA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@tailwindcss/node": "4.1.18",
                "@tailwindcss/oxide": "4.1.18",
                "tailwindcss": "4.1.18"
            },
            "peerDependencies": {
                "vite": "^5.2.0 || ^6 || ^7"
            }
        },
        "node_modules/@types/estree": {
            "version": "1.0.8",
            "resolved": "https://registry.npmjs.org/@types/estree/-/estree-1.0.8.tgz",
            "integrity": "sha512-dWHzHa2WqEXI/O1E9OjrocMTKJl2mSrEolh1Iomrv6U+JuNwaHXsXx9bLu5gG7BUWFIN0skIQJQ/L1rIex4X6w==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/@types/pako": {
            "version": "2.0.4",
            "resolved": "https://registry.npmjs.org/@types/pako/-/pako-2.0.4.tgz",
            "integrity": "sha512-VWDCbrLeVXJM9fihYodcLiIv0ku+AlOa/TQ1SvYOaBuyrSKgEcro95LJyIsJ4vSo6BXIxOKxiJAat04CmST9Fw==",
            "license": "MIT"
        },
        "node_modules/@types/raf": {
            "version": "3.4.3",
            "resolved": "https://registry.npmjs.org/@types/raf/-/raf-3.4.3.tgz",
            "integrity": "sha512-c4YAvMedbPZ5tEyxzQdMoOhhJ4RD3rngZIdwC2/qDN3d7JpEhB6fiBRKVY1lg5B7Wk+uPBjn5f39j1/2MY1oOw==",
            "license": "MIT",
            "optional": true
        },
        "node_modules/@types/trusted-types": {
            "version": "2.0.7",
            "resolved": "https://registry.npmjs.org/@types/trusted-types/-/trusted-types-2.0.7.tgz",
            "integrity": "sha512-ScaPdn1dQczgbl0QFTeTOmVHFULt394XJgOQNoyVhZ6r2vLnMLJfBPd53SB52T/3G36VI1/g2MZaX0cwDuXsfw==",
            "license": "MIT",
            "optional": true
        },
        "node_modules/@vue/reactivity": {
            "version": "3.1.5",
            "resolved": "https://registry.npmjs.org/@vue/reactivity/-/reactivity-3.1.5.tgz",
            "integrity": "sha512-1tdfLmNjWG6t/CsPldh+foumYFo3cpyCHgBYQ34ylaMsJ+SNHQ1kApMIa8jN+i593zQuaw3AdWH0nJTARzCFhg==",
            "license": "MIT",
            "dependencies": {
                "@vue/shared": "3.1.5"
            }
        },
        "node_modules/@vue/shared": {
            "version": "3.1.5",
            "resolved": "https://registry.npmjs.org/@vue/shared/-/shared-3.1.5.tgz",
            "integrity": "sha512-oJ4F3TnvpXaQwZJNF3ZK+kLPHKarDmJjJ6jyzVNDKH9md1dptjC7lWR//jrGuLdek/U6iltWxqAnYOu8gCiOvA==",
            "license": "MIT"
        },
        "node_modules/agent-base": {
            "version": "7.1.4",
            "resolved": "https://registry.npmjs.org/agent-base/-/agent-base-7.1.4.tgz",
            "integrity": "sha512-MnA+YT8fwfJPgBx3m60MNqakm30XOkyIoH1y6huTQvC0PwZG7ki8NacLBcrPbNoo8vEZy7Jpuk7+jMO+CUovTQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 14"
            }
        },
        "node_modules/alpinejs": {
            "version": "3.14.9",
            "resolved": "https://registry.npmjs.org/alpinejs/-/alpinejs-3.14.9.tgz",
            "integrity": "sha512-gqSOhTEyryU9FhviNqiHBHzgjkvtukq9tevew29fTj+ofZtfsYriw4zPirHHOAy9bw8QoL3WGhyk7QqCh5AYlw==",
            "license": "MIT",
            "dependencies": {
                "@vue/reactivity": "~3.1.1"
            }
        },
        "node_modules/ansi-regex": {
            "version": "5.0.1",
            "resolved": "https://registry.npmjs.org/ansi-regex/-/ansi-regex-5.0.1.tgz",
            "integrity": "sha512-quJQXlTSUGL2LH9SUXo8VwsY4soanhgo6LNSm84E1LBcE8s3O0wpdiRzyR9z/ZZJMlMWv37qOOb9pdJlMUEKFQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/ansi-styles": {
            "version": "4.3.0",
            "resolved": "https://registry.npmjs.org/ansi-styles/-/ansi-styles-4.3.0.tgz",
            "integrity": "sha512-zbB9rCJAT1rbjiVDb2hqKFHNYLxgtk8NURxZ3IZwD3F6NtxbXZQCnnSi1Lkx+IDohdPlFp222wVALIheZJQSEg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "color-convert": "^2.0.1"
            },
            "engines": {
                "node": ">=8"
            },
            "funding": {
                "url": "https://github.com/chalk/ansi-styles?sponsor=1"
            }
        },
        "node_modules/asynckit": {
            "version": "0.4.0",
            "resolved": "https://registry.npmjs.org/asynckit/-/asynckit-0.4.0.tgz",
            "integrity": "sha512-Oei9OH4tRh0YqU3GxhX79dM/mwVgvbZJaSNaRk+bshkj0S5cfHcgYakreBjrHwatXKbz+IoIdYLxrKim2MjW0Q==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/autoprefixer": {
            "version": "10.4.24",
            "resolved": "https://registry.npmjs.org/autoprefixer/-/autoprefixer-10.4.24.tgz",
            "integrity": "sha512-uHZg7N9ULTVbutaIsDRoUkoS8/h3bdsmVJYZ5l3wv8Cp/6UIIoRDm90hZ+BwxUj/hGBEzLxdHNSKuFpn8WOyZw==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/postcss/"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/autoprefixer"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "dependencies": {
                "browserslist": "^4.28.1",
                "caniuse-lite": "^1.0.30001766",
                "fraction.js": "^5.3.4",
                "picocolors": "^1.1.1",
                "postcss-value-parser": "^4.2.0"
            },
            "bin": {
                "autoprefixer": "bin/autoprefixer"
            },
            "engines": {
                "node": "^10 || ^12 || >=14"
            },
            "peerDependencies": {
                "postcss": "^8.1.0"
            }
        },
        "node_modules/axios": {
            "version": "1.13.5",
            "resolved": "https://registry.npmjs.org/axios/-/axios-1.13.5.tgz",
            "integrity": "sha512-cz4ur7Vb0xS4/KUN0tPWe44eqxrIu31me+fbang3ijiNscE129POzipJJA6zniq2C/Z6sJCjMimjS8Lc/GAs8Q==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "follow-redirects": "^1.15.11",
                "form-data": "^4.0.5",
                "proxy-from-env": "^1.1.0"
            }
        },
        "node_modules/base64-arraybuffer": {
            "version": "1.0.2",
            "resolved": "https://registry.npmjs.org/base64-arraybuffer/-/base64-arraybuffer-1.0.2.tgz",
            "integrity": "sha512-I3yl4r9QB5ZRY3XuJVEPfc2XhZO6YweFPI+UovAzn+8/hb3oJ6lnysaFcjVpkCPfVWFUDvoZ8kmVDP7WyRtYtQ==",
            "license": "MIT",
            "optional": true,
            "engines": {
                "node": ">= 0.6.0"
            }
        },
        "node_modules/baseline-browser-mapping": {
            "version": "2.9.19",
            "resolved": "https://registry.npmjs.org/baseline-browser-mapping/-/baseline-browser-mapping-2.9.19.tgz",
            "integrity": "sha512-ipDqC8FrAl/76p2SSWKSI+H9tFwm7vYqXQrItCuiVPt26Km0jS+NzSsBWAaBusvSbQcfJG+JitdMm+wZAgTYqg==",
            "dev": true,
            "license": "Apache-2.0",
            "bin": {
                "baseline-browser-mapping": "dist/cli.js"
            }
        },
        "node_modules/browserslist": {
            "version": "4.28.1",
            "resolved": "https://registry.npmjs.org/browserslist/-/browserslist-4.28.1.tgz",
            "integrity": "sha512-ZC5Bd0LgJXgwGqUknZY/vkUQ04r8NXnJZ3yYi4vDmSiZmC/pdSN0NbNRPxZpbtO4uAfDUAFffO8IZoM3Gj8IkA==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/browserslist"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/browserslist"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "dependencies": {
                "baseline-browser-mapping": "^2.9.0",
                "caniuse-lite": "^1.0.30001759",
                "electron-to-chromium": "^1.5.263",
                "node-releases": "^2.0.27",
                "update-browserslist-db": "^1.2.0"
            },
            "bin": {
                "browserslist": "cli.js"
            },
            "engines": {
                "node": "^6 || ^7 || ^8 || ^9 || ^10 || ^11 || ^12 || >=13.7"
            }
        },
        "node_modules/call-bind-apply-helpers": {
            "version": "1.0.2",
            "resolved": "https://registry.npmjs.org/call-bind-apply-helpers/-/call-bind-apply-helpers-1.0.2.tgz",
            "integrity": "sha512-Sp1ablJ0ivDkSzjcaJdxEunN5/XvksFJ2sMBFfq6x0ryhQV/2b/KwFe21cMpmHtPOSij8K99/wSfoEuTObmuMQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "es-errors": "^1.3.0",
                "function-bind": "^1.1.2"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/caniuse-lite": {
            "version": "1.0.30001769",
            "resolved": "https://registry.npmjs.org/caniuse-lite/-/caniuse-lite-1.0.30001769.tgz",
            "integrity": "sha512-BCfFL1sHijQlBGWBMuJyhZUhzo7wer5sVj9hqekB/7xn0Ypy+pER/edCYQm4exbXj4WiySGp40P8UuTh6w1srg==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/browserslist"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/caniuse-lite"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "CC-BY-4.0"
        },
        "node_modules/canvg": {
            "version": "3.0.11",
            "resolved": "https://registry.npmjs.org/canvg/-/canvg-3.0.11.tgz",
            "integrity": "sha512-5ON+q7jCTgMp9cjpu4Jo6XbvfYwSB2Ow3kzHKfIyJfaCAOHLbdKPQqGKgfED/R5B+3TFFfe8pegYA+b423SRyA==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "@babel/runtime": "^7.12.5",
                "@types/raf": "^3.4.0",
                "core-js": "^3.8.3",
                "raf": "^3.4.1",
                "regenerator-runtime": "^0.13.7",
                "rgbcolor": "^1.0.1",
                "stackblur-canvas": "^2.0.0",
                "svg-pathdata": "^6.0.3"
            },
            "engines": {
                "node": ">=10.0.0"
            }
        },
        "node_modules/chalk": {
            "version": "4.1.2",
            "resolved": "https://registry.npmjs.org/chalk/-/chalk-4.1.2.tgz",
            "integrity": "sha512-oKnbhFyRIXpUuez8iBMmyEa4nbj4IOQyuhc/wy9kY7/WVPcwIO9VA668Pu8RkO7+0G76SLROeyw9CpQ061i4mA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "ansi-styles": "^4.1.0",
                "supports-color": "^7.1.0"
            },
            "engines": {
                "node": ">=10"
            },
            "funding": {
                "url": "https://github.com/chalk/chalk?sponsor=1"
            }
        },
        "node_modules/chalk/node_modules/supports-color": {
            "version": "7.2.0",
            "resolved": "https://registry.npmjs.org/supports-color/-/supports-color-7.2.0.tgz",
            "integrity": "sha512-qpCAvRl9stuOHveKsn7HncJRvv501qIacKzQlO/+Lwxc9+0q2wLyv4Dfvt80/DPn2pqOBsJdDiogXGR9+OvwRw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "has-flag": "^4.0.0"
            },
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/cliui": {
            "version": "8.0.1",
            "resolved": "https://registry.npmjs.org/cliui/-/cliui-8.0.1.tgz",
            "integrity": "sha512-BSeNnyus75C4//NQ9gQt1/csTXyo/8Sb+afLAkzAptFuMsod9HFokGNudZpi/oQV73hnVK+sR+5PVRMd+Dr7YQ==",
            "dev": true,
            "license": "ISC",
            "dependencies": {
                "string-width": "^4.2.0",
                "strip-ansi": "^6.0.1",
                "wrap-ansi": "^7.0.0"
            },
            "engines": {
                "node": ">=12"
            }
        },
        "node_modules/color-convert": {
            "version": "2.0.1",
            "resolved": "https://registry.npmjs.org/color-convert/-/color-convert-2.0.1.tgz",
            "integrity": "sha512-RRECPsj7iu/xb5oKYcsFHSppFNnsj/52OVTRKb4zP5onXwVF3zVmmToNcOfGC+CRDpfK/U584fMg38ZHCaElKQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "color-name": "~1.1.4"
            },
            "engines": {
                "node": ">=7.0.0"
            }
        },
        "node_modules/color-name": {
            "version": "1.1.4",
            "resolved": "https://registry.npmjs.org/color-name/-/color-name-1.1.4.tgz",
            "integrity": "sha512-dOy+3AuW3a2wNbZHIuMZpTcgjGuLU/uBL/ubcZF9OXbDo8ff4O8yVp5Bf0efS8uEoYo5q4Fx7dY9OgQGXgAsQA==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/combined-stream": {
            "version": "1.0.8",
            "resolved": "https://registry.npmjs.org/combined-stream/-/combined-stream-1.0.8.tgz",
            "integrity": "sha512-FQN4MRfuJeHf7cBbBMJFXhKSDq+2kAArBlmRBvcvFE5BB1HZKXtSFASDhdlz9zOYwxh8lDdnvmMOe/+5cdoEdg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "delayed-stream": "~1.0.0"
            },
            "engines": {
                "node": ">= 0.8"
            }
        },
        "node_modules/concurrently": {
            "version": "9.2.1",
            "resolved": "https://registry.npmjs.org/concurrently/-/concurrently-9.2.1.tgz",
            "integrity": "sha512-fsfrO0MxV64Znoy8/l1vVIjjHa29SZyyqPgQBwhiDcaW8wJc2W3XWVOGx4M3oJBnv/zdUZIIp1gDeS98GzP8Ng==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "chalk": "4.1.2",
                "rxjs": "7.8.2",
                "shell-quote": "1.8.3",
                "supports-color": "8.1.1",
                "tree-kill": "1.2.2",
                "yargs": "17.7.2"
            },
            "bin": {
                "conc": "dist/bin/concurrently.js",
                "concurrently": "dist/bin/concurrently.js"
            },
            "engines": {
                "node": ">=18"
            },
            "funding": {
                "url": "https://github.com/open-cli-tools/concurrently?sponsor=1"
            }
        },
        "node_modules/core-js": {
            "version": "3.50.0",
            "resolved": "https://registry.npmjs.org/core-js/-/core-js-3.50.0.tgz",
            "integrity": "sha512-BRWgOLKkFeCgRudR6zrs8p9XJZcE14grzKMMssoYrk6krtuEZ7MTKPIY5RzOnqsEKIR9kst7wNzphttraT+Yqw==",
            "hasInstallScript": true,
            "license": "MIT",
            "optional": true,
            "engines": {
                "node": "*"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/core-js"
            }
        },
        "node_modules/css-line-break": {
            "version": "2.1.0",
            "resolved": "https://registry.npmjs.org/css-line-break/-/css-line-break-2.1.0.tgz",
            "integrity": "sha512-FHcKFCZcAha3LwfVBhCQbW2nCNbkZXn7KVUJcsT5/P8YmfsVja0FMPJr0B903j/E69HUphKiV9iQArX8SDYA4w==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "utrie": "^1.0.2"
            }
        },
        "node_modules/cssstyle": {
            "version": "4.6.0",
            "resolved": "https://registry.npmjs.org/cssstyle/-/cssstyle-4.6.0.tgz",
            "integrity": "sha512-2z+rWdzbbSZv6/rhtvzvqeZQHrBaqgogqt85sqFNbabZOuFbCVFb8kPeEtZjiKkbrm395irpNKiYeFeLiQnFPg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@asamuzakjp/css-color": "^3.2.0",
                "rrweb-cssom": "^0.8.0"
            },
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/data-urls": {
            "version": "5.0.0",
            "resolved": "https://registry.npmjs.org/data-urls/-/data-urls-5.0.0.tgz",
            "integrity": "sha512-ZYP5VBHshaDAiVZxjbRVcFJpc+4xGgT0bK3vzy1HLN8jTO975HEbuYzZJcHoQEY5K1a0z8YayJkyVETa08eNTg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "whatwg-mimetype": "^4.0.0",
                "whatwg-url": "^14.0.0"
            },
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/debug": {
            "version": "4.4.3",
            "resolved": "https://registry.npmjs.org/debug/-/debug-4.4.3.tgz",
            "integrity": "sha512-RGwwWnwQvkVfavKVt22FGLw+xYSdzARwm0ru6DhTVA3umU5hZc28V3kO4stgYryrTlLpuvgI9GiijltAjNbcqA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "ms": "^2.1.3"
            },
            "engines": {
                "node": ">=6.0"
            },
            "peerDependenciesMeta": {
                "supports-color": {
                    "optional": true
                }
            }
        },
        "node_modules/decimal.js": {
            "version": "10.6.0",
            "resolved": "https://registry.npmjs.org/decimal.js/-/decimal.js-10.6.0.tgz",
            "integrity": "sha512-YpgQiITW3JXGntzdUmyUR1V812Hn8T1YVXhCu+wO3OpS4eU9l4YdD3qjyiKdV6mvV29zapkMeD390UVEf2lkUg==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/delayed-stream": {
            "version": "1.0.0",
            "resolved": "https://registry.npmjs.org/delayed-stream/-/delayed-stream-1.0.0.tgz",
            "integrity": "sha512-ZySD7Nf91aLB0RxL4KGrKHBXl7Eds1DAmEdcoVawXnLD7SDhpNgtuII2aAkg7a7QS41jxPSZ17p4VdGnMHk3MQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=0.4.0"
            }
        },
        "node_modules/detect-libc": {
            "version": "2.1.2",
            "resolved": "https://registry.npmjs.org/detect-libc/-/detect-libc-2.1.2.tgz",
            "integrity": "sha512-Btj2BOOO83o3WyH59e8MgXsxEQVcarkUOpEYrubB0urwnN10yQ364rsiByU11nZlqWYZm05i/of7io4mzihBtQ==",
            "dev": true,
            "license": "Apache-2.0",
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/dompurify": {
            "version": "3.4.16",
            "resolved": "https://registry.npmjs.org/dompurify/-/dompurify-3.4.16.tgz",
            "integrity": "sha512-sqo+pNp3qRhCIpbgRi1y8Tgk27Bo2Ry7w0dC1NBeNTdZChWjz9Xb/KOoZbRP/R6pQZ80Qw8YhXw13hWWBbMRnQ==",
            "license": "(MPL-2.0 OR Apache-2.0)",
            "optional": true,
            "optionalDependencies": {
                "@types/trusted-types": "^2.0.7"
            }
        },
        "node_modules/dunder-proto": {
            "version": "1.0.1",
            "resolved": "https://registry.npmjs.org/dunder-proto/-/dunder-proto-1.0.1.tgz",
            "integrity": "sha512-KIN/nDJBQRcXw0MLVhZE9iQHmG68qAVIBg9CqmUYjmQIhgij9U5MFvrqkUL5FbtyyzZuOeOt0zdeRe4UY7ct+A==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "call-bind-apply-helpers": "^1.0.1",
                "es-errors": "^1.3.0",
                "gopd": "^1.2.0"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/electron-to-chromium": {
            "version": "1.5.286",
            "resolved": "https://registry.npmjs.org/electron-to-chromium/-/electron-to-chromium-1.5.286.tgz",
            "integrity": "sha512-9tfDXhJ4RKFNerfjdCcZfufu49vg620741MNs26a9+bhLThdB+plgMeou98CAaHu/WATj2iHOOHTp1hWtABj2A==",
            "dev": true,
            "license": "ISC"
        },
        "node_modules/emoji-regex": {
            "version": "8.0.0",
            "resolved": "https://registry.npmjs.org/emoji-regex/-/emoji-regex-8.0.0.tgz",
            "integrity": "sha512-MSjYzcWNOA0ewAHpz0MxpYFvwg6yjy1NG3xteoqz644VCo/RPgnr1/GGt+ic3iJTzQ8Eu3TdM14SawnVUmGE6A==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/enhanced-resolve": {
            "version": "5.19.0",
            "resolved": "https://registry.npmjs.org/enhanced-resolve/-/enhanced-resolve-5.19.0.tgz",
            "integrity": "sha512-phv3E1Xl4tQOShqSte26C7Fl84EwUdZsyOuSSk9qtAGyyQs2s3jJzComh+Abf4g187lUUAvH+H26omrqia2aGg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "graceful-fs": "^4.2.4",
                "tapable": "^2.3.0"
            },
            "engines": {
                "node": ">=10.13.0"
            }
        },
        "node_modules/entities": {
            "version": "6.0.1",
            "resolved": "https://registry.npmjs.org/entities/-/entities-6.0.1.tgz",
            "integrity": "sha512-aN97NXWF6AWBTahfVOIrB/NShkzi5H7F9r1s9mD3cDj4Ko5f2qhhVoYMibXF7GlLveb/D2ioWay8lxI97Ven3g==",
            "dev": true,
            "license": "BSD-2-Clause",
            "engines": {
                "node": ">=0.12"
            },
            "funding": {
                "url": "https://github.com/fb55/entities?sponsor=1"
            }
        },
        "node_modules/es-define-property": {
            "version": "1.0.1",
            "resolved": "https://registry.npmjs.org/es-define-property/-/es-define-property-1.0.1.tgz",
            "integrity": "sha512-e3nRfgfUZ4rNGL232gUgX06QNyyez04KdjFrF+LTRoOXmrOgFKDg4BCdsjW8EnT69eqdYGmRpJwiPVYNrCaW3g==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/es-errors": {
            "version": "1.3.0",
            "resolved": "https://registry.npmjs.org/es-errors/-/es-errors-1.3.0.tgz",
            "integrity": "sha512-Zf5H2Kxt2xjTvbJvP2ZWLEICxA6j+hAmMzIlypy4xcBg1vKVnx89Wy0GbS+kf5cwCVFFzdCFh2XSCFNULS6csw==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/es-object-atoms": {
            "version": "1.1.1",
            "resolved": "https://registry.npmjs.org/es-object-atoms/-/es-object-atoms-1.1.1.tgz",
            "integrity": "sha512-FGgH2h8zKNim9ljj7dankFPcICIK9Cp5bm+c2gQSYePhpaG5+esrLODihIorn+Pe6FGJzWhXQotPv73jTaldXA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "es-errors": "^1.3.0"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/es-set-tostringtag": {
            "version": "2.1.0",
            "resolved": "https://registry.npmjs.org/es-set-tostringtag/-/es-set-tostringtag-2.1.0.tgz",
            "integrity": "sha512-j6vWzfrGVfyXxge+O0x5sh6cvxAog0a/4Rdd2K36zCMV5eJ+/+tOAngRO8cODMNWbVRdVlmGZQL2YS3yR8bIUA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "es-errors": "^1.3.0",
                "get-intrinsic": "^1.2.6",
                "has-tostringtag": "^1.0.2",
                "hasown": "^2.0.2"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/esbuild": {
            "version": "0.27.3",
            "resolved": "https://registry.npmjs.org/esbuild/-/esbuild-0.27.3.tgz",
            "integrity": "sha512-8VwMnyGCONIs6cWue2IdpHxHnAjzxnw2Zr7MkVxB2vjmQ2ivqGFb4LEG3SMnv0Gb2F/G/2yA8zUaiL1gywDCCg==",
            "dev": true,
            "hasInstallScript": true,
            "license": "MIT",
            "bin": {
                "esbuild": "bin/esbuild"
            },
            "engines": {
                "node": ">=18"
            },
            "optionalDependencies": {
                "@esbuild/aix-ppc64": "0.27.3",
                "@esbuild/android-arm": "0.27.3",
                "@esbuild/android-arm64": "0.27.3",
                "@esbuild/android-x64": "0.27.3",
                "@esbuild/darwin-arm64": "0.27.3",
                "@esbuild/darwin-x64": "0.27.3",
                "@esbuild/freebsd-arm64": "0.27.3",
                "@esbuild/freebsd-x64": "0.27.3",
                "@esbuild/linux-arm": "0.27.3",
                "@esbuild/linux-arm64": "0.27.3",
                "@esbuild/linux-ia32": "0.27.3",
                "@esbuild/linux-loong64": "0.27.3",
                "@esbuild/linux-mips64el": "0.27.3",
                "@esbuild/linux-ppc64": "0.27.3",
                "@esbuild/linux-riscv64": "0.27.3",
                "@esbuild/linux-s390x": "0.27.3",
                "@esbuild/linux-x64": "0.27.3",
                "@esbuild/netbsd-arm64": "0.27.3",
                "@esbuild/netbsd-x64": "0.27.3",
                "@esbuild/openbsd-arm64": "0.27.3",
                "@esbuild/openbsd-x64": "0.27.3",
                "@esbuild/openharmony-arm64": "0.27.3",
                "@esbuild/sunos-x64": "0.27.3",
                "@esbuild/win32-arm64": "0.27.3",
                "@esbuild/win32-ia32": "0.27.3",
                "@esbuild/win32-x64": "0.27.3"
            }
        },
        "node_modules/escalade": {
            "version": "3.2.0",
            "resolved": "https://registry.npmjs.org/escalade/-/escalade-3.2.0.tgz",
            "integrity": "sha512-WUj2qlxaQtO4g6Pq5c29GTcWGDyd8itL8zTlipgECz3JesAiiOKotd8JU6otB3PACgG6xkJUyVhboMS+bje/jA==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=6"
            }
        },
        "node_modules/fast-png": {
            "version": "6.4.0",
            "resolved": "https://registry.npmjs.org/fast-png/-/fast-png-6.4.0.tgz",
            "integrity": "sha512-kAqZq1TlgBjZcLr5mcN6NP5Rv4V2f22z00c3g8vRrwkcqjerx7BEhPbOnWCPqaHUl2XWQBJQvOT/FQhdMT7X/Q==",
            "license": "MIT",
            "dependencies": {
                "@types/pako": "^2.0.3",
                "iobuffer": "^5.3.2",
                "pako": "^2.1.0"
            }
        },
        "node_modules/fdir": {
            "version": "6.5.0",
            "resolved": "https://registry.npmjs.org/fdir/-/fdir-6.5.0.tgz",
            "integrity": "sha512-tIbYtZbucOs0BRGqPJkshJUYdL+SDH7dVM8gjy+ERp3WAUjLEFJE+02kanyHtwjWOnwrKYBiwAmM0p4kLJAnXg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=12.0.0"
            },
            "peerDependencies": {
                "picomatch": "^3 || ^4"
            },
            "peerDependenciesMeta": {
                "picomatch": {
                    "optional": true
                }
            }
        },
        "node_modules/fflate": {
            "version": "0.8.3",
            "resolved": "https://registry.npmjs.org/fflate/-/fflate-0.8.3.tgz",
            "integrity": "sha512-tbZNuJrLwGUp3zshBtdy4W+ORxZuIh8a5ilyIEQDC5rY1f3U20JMry0Ll3WBzU58EZKsEuJFXhb5gwv8CsPvgA==",
            "license": "MIT"
        },
        "node_modules/follow-redirects": {
            "version": "1.15.11",
            "resolved": "https://registry.npmjs.org/follow-redirects/-/follow-redirects-1.15.11.tgz",
            "integrity": "sha512-deG2P0JfjrTxl50XGCDyfI97ZGVCxIpfKYmfyrQ54n5FO/0gfIES8C/Psl6kWVDolizcaaxZJnTS0QSMxvnsBQ==",
            "dev": true,
            "funding": [
                {
                    "type": "individual",
                    "url": "https://github.com/sponsors/RubenVerborgh"
                }
            ],
            "license": "MIT",
            "engines": {
                "node": ">=4.0"
            },
            "peerDependenciesMeta": {
                "debug": {
                    "optional": true
                }
            }
        },
        "node_modules/form-data": {
            "version": "4.0.5",
            "resolved": "https://registry.npmjs.org/form-data/-/form-data-4.0.5.tgz",
            "integrity": "sha512-8RipRLol37bNs2bhoV67fiTEvdTrbMUYcFTiy3+wuuOnUog2QBHCZWXDRijWQfAkhBj2Uf5UnVaiWwA5vdd82w==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "asynckit": "^0.4.0",
                "combined-stream": "^1.0.8",
                "es-set-tostringtag": "^2.1.0",
                "hasown": "^2.0.2",
                "mime-types": "^2.1.12"
            },
            "engines": {
                "node": ">= 6"
            }
        },
        "node_modules/fraction.js": {
            "version": "5.3.4",
            "resolved": "https://registry.npmjs.org/fraction.js/-/fraction.js-5.3.4.tgz",
            "integrity": "sha512-1X1NTtiJphryn/uLQz3whtY6jK3fTqoE3ohKs0tT+Ujr1W59oopxmoEh7Lu5p6vBaPbgoM0bzveAW4Qi5RyWDQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": "*"
            },
            "funding": {
                "type": "github",
                "url": "https://github.com/sponsors/rawify"
            }
        },
        "node_modules/fsevents": {
            "version": "2.3.3",
            "resolved": "https://registry.npmjs.org/fsevents/-/fsevents-2.3.3.tgz",
            "integrity": "sha512-5xoDfX+fL7faATnagmWPpbFtwh/R77WmMMqqHGS65C3vvB0YHrgF+B1YmZ3441tMj5n63k0212XNoJwzlhffQw==",
            "dev": true,
            "hasInstallScript": true,
            "license": "MIT",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": "^8.16.0 || ^10.6.0 || >=11.0.0"
            }
        },
        "node_modules/function-bind": {
            "version": "1.1.2",
            "resolved": "https://registry.npmjs.org/function-bind/-/function-bind-1.1.2.tgz",
            "integrity": "sha512-7XHNxH7qX9xG5mIwxkhumTox/MIRNcOgDrxWsMt2pAr23WHp6MrRlN7FBSFpCpr+oVO0F744iUgR82nJMfG2SA==",
            "dev": true,
            "license": "MIT",
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/get-caller-file": {
            "version": "2.0.5",
            "resolved": "https://registry.npmjs.org/get-caller-file/-/get-caller-file-2.0.5.tgz",
            "integrity": "sha512-DyFP3BM/3YHTQOCUL/w0OZHR0lpKeGrxotcHWcqNEdnltqFwXVfhEBQ94eIo34AfQpo0rGki4cyIiftY06h2Fg==",
            "dev": true,
            "license": "ISC",
            "engines": {
                "node": "6.* || 8.* || >= 10.*"
            }
        },
        "node_modules/get-intrinsic": {
            "version": "1.3.0",
            "resolved": "https://registry.npmjs.org/get-intrinsic/-/get-intrinsic-1.3.0.tgz",
            "integrity": "sha512-9fSjSaos/fRIVIp+xSJlE6lfwhES7LNtKaCBIamHsjr2na1BiABJPo0mOjjz8GJDURarmCPGqaiVg5mfjb98CQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "call-bind-apply-helpers": "^1.0.2",
                "es-define-property": "^1.0.1",
                "es-errors": "^1.3.0",
                "es-object-atoms": "^1.1.1",
                "function-bind": "^1.1.2",
                "get-proto": "^1.0.1",
                "gopd": "^1.2.0",
                "has-symbols": "^1.1.0",
                "hasown": "^2.0.2",
                "math-intrinsics": "^1.1.0"
            },
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/get-proto": {
            "version": "1.0.1",
            "resolved": "https://registry.npmjs.org/get-proto/-/get-proto-1.0.1.tgz",
            "integrity": "sha512-sTSfBjoXBp89JvIKIefqw7U2CCebsc74kiY6awiGogKtoSGbgjYE/G/+l9sF3MWFPNc9IcoOC4ODfKHfxFmp0g==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "dunder-proto": "^1.0.1",
                "es-object-atoms": "^1.0.0"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/gopd": {
            "version": "1.2.0",
            "resolved": "https://registry.npmjs.org/gopd/-/gopd-1.2.0.tgz",
            "integrity": "sha512-ZUKRh6/kUFoAiTAtTYPZJ3hw9wNxx+BIBOijnlG9PnrJsCcSjs1wyyD6vJpaYtgnzDrKYRSqf3OO6Rfa93xsRg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/graceful-fs": {
            "version": "4.2.11",
            "resolved": "https://registry.npmjs.org/graceful-fs/-/graceful-fs-4.2.11.tgz",
            "integrity": "sha512-RbJ5/jmFcNNCcDV5o9eTnBLJ/HszWV0P73bc+Ff4nS/rJj+YaS6IGyiOL0VoBYX+l1Wrl3k63h/KrH+nhJ0XvQ==",
            "dev": true,
            "license": "ISC"
        },
        "node_modules/has-flag": {
            "version": "4.0.0",
            "resolved": "https://registry.npmjs.org/has-flag/-/has-flag-4.0.0.tgz",
            "integrity": "sha512-EykJT/Q1KjTWctppgIAgfSO0tKVuZUjhgMr17kqTumMl6Afv3EISleU7qZUzoXDFTAHTDC4NOoG/ZxU3EvlMPQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/has-symbols": {
            "version": "1.1.0",
            "resolved": "https://registry.npmjs.org/has-symbols/-/has-symbols-1.1.0.tgz",
            "integrity": "sha512-1cDNdwJ2Jaohmb3sg4OmKaMBwuC48sYni5HUw2DvsC8LjGTLK9h+eb1X6RyuOHe4hT0ULCW68iomhjUoKUqlPQ==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/has-tostringtag": {
            "version": "1.0.2",
            "resolved": "https://registry.npmjs.org/has-tostringtag/-/has-tostringtag-1.0.2.tgz",
            "integrity": "sha512-NqADB8VjPFLM2V0VvHUewwwsw0ZWBaIdgo+ieHtK3hasLz4qeCRjYcqfB6AQrBggRKppKF8L52/VqdVsO47Dlw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "has-symbols": "^1.0.3"
            },
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/hasown": {
            "version": "2.0.2",
            "resolved": "https://registry.npmjs.org/hasown/-/hasown-2.0.2.tgz",
            "integrity": "sha512-0hJU9SCPvmMzIBdZFqNPXWa6dqh7WdH0cII9y+CyS8rG3nL48Bclra9HmKhVVUHyPWNH5Y7xDwAB7bfgSjkUMQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "function-bind": "^1.1.2"
            },
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/html-encoding-sniffer": {
            "version": "4.0.0",
            "resolved": "https://registry.npmjs.org/html-encoding-sniffer/-/html-encoding-sniffer-4.0.0.tgz",
            "integrity": "sha512-Y22oTqIU4uuPgEemfz7NDJz6OeKf12Lsu+QC+s3BVpda64lTiMYCyGwg5ki4vFxkMwQdeZDl2adZoqUgdFuTgQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "whatwg-encoding": "^3.1.1"
            },
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/html2canvas": {
            "version": "1.4.1",
            "resolved": "https://registry.npmjs.org/html2canvas/-/html2canvas-1.4.1.tgz",
            "integrity": "sha512-fPU6BHNpsyIhr8yyMpTLLxAbkaK8ArIBcmZIRiBLiDhjeqvXolaEmDGmELFuX9I4xDcaKKcJl+TKZLqruBbmWA==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "css-line-break": "^2.1.0",
                "text-segmentation": "^1.0.3"
            },
            "engines": {
                "node": ">=8.0.0"
            }
        },
        "node_modules/http-proxy-agent": {
            "version": "7.0.2",
            "resolved": "https://registry.npmjs.org/http-proxy-agent/-/http-proxy-agent-7.0.2.tgz",
            "integrity": "sha512-T1gkAiYYDWYx3V5Bmyu7HcfcvL7mUrTWiM6yOfa3PIphViJ/gFPbvidQ+veqSOHci/PxBcDabeUNCzpOODJZig==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "agent-base": "^7.1.0",
                "debug": "^4.3.4"
            },
            "engines": {
                "node": ">= 14"
            }
        },
        "node_modules/https-proxy-agent": {
            "version": "7.0.6",
            "resolved": "https://registry.npmjs.org/https-proxy-agent/-/https-proxy-agent-7.0.6.tgz",
            "integrity": "sha512-vK9P5/iUfdl95AI+JVyUuIcVtd4ofvtrOr3HNtM2yxC9bnMbEdp3x01OhQNnjb8IJYi38VlTE3mBXwcfvywuSw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "agent-base": "^7.1.2",
                "debug": "4"
            },
            "engines": {
                "node": ">= 14"
            }
        },
        "node_modules/iconv-lite": {
            "version": "0.6.3",
            "resolved": "https://registry.npmjs.org/iconv-lite/-/iconv-lite-0.6.3.tgz",
            "integrity": "sha512-4fCk79wshMdzMp2rH06qWrJE4iolqLhCUH+OiuIgU++RB0+94NlDL81atO7GX55uUKueo0txHNtvEyI6D7WdMw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "safer-buffer": ">= 2.1.2 < 3.0.0"
            },
            "engines": {
                "node": ">=0.10.0"
            }
        },
        "node_modules/iobuffer": {
            "version": "5.4.0",
            "resolved": "https://registry.npmjs.org/iobuffer/-/iobuffer-5.4.0.tgz",
            "integrity": "sha512-DRebOWuqDvxunfkNJAlc3IzWIPD5xVxwUNbHr7xKB8E6aLJxIPfNX3CoMJghcFjpv6RWQsrcJbghtEwSPoJqMA==",
            "license": "MIT"
        },
        "node_modules/is-fullwidth-code-point": {
            "version": "3.0.0",
            "resolved": "https://registry.npmjs.org/is-fullwidth-code-point/-/is-fullwidth-code-point-3.0.0.tgz",
            "integrity": "sha512-zymm5+u+sCsSWyD9qNaejV3DFvhCKclKdizYaJUuHA83RLjb7nSuGnddCHGv0hk+KY7BMAlsWeK4Ueg6EV6XQg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/is-potential-custom-element-name": {
            "version": "1.0.1",
            "resolved": "https://registry.npmjs.org/is-potential-custom-element-name/-/is-potential-custom-element-name-1.0.1.tgz",
            "integrity": "sha512-bCYeRA2rVibKZd+s2625gGnGF/t7DSqDs4dP7CrLA1m7jKWz6pps0LpYLJN8Q64HtmPKJ1hrN3nzPNKFEKOUiQ==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/jiti": {
            "version": "2.6.1",
            "resolved": "https://registry.npmjs.org/jiti/-/jiti-2.6.1.tgz",
            "integrity": "sha512-ekilCSN1jwRvIbgeg/57YFh8qQDNbwDb9xT/qu2DAHbFFZUicIl4ygVaAvzveMhMVr3LnpSKTNnwt8PoOfmKhQ==",
            "dev": true,
            "license": "MIT",
            "bin": {
                "jiti": "lib/jiti-cli.mjs"
            }
        },
        "node_modules/jsdom": {
            "version": "26.1.0",
            "resolved": "https://registry.npmjs.org/jsdom/-/jsdom-26.1.0.tgz",
            "integrity": "sha512-Cvc9WUhxSMEo4McES3P7oK3QaXldCfNWp7pl2NNeiIFlCoLr3kfq9kb1fxftiwk1FLV7CvpvDfonxtzUDeSOPg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "cssstyle": "^4.2.1",
                "data-urls": "^5.0.0",
                "decimal.js": "^10.5.0",
                "html-encoding-sniffer": "^4.0.0",
                "http-proxy-agent": "^7.0.2",
                "https-proxy-agent": "^7.0.6",
                "is-potential-custom-element-name": "^1.0.1",
                "nwsapi": "^2.2.16",
                "parse5": "^7.2.1",
                "rrweb-cssom": "^0.8.0",
                "saxes": "^6.0.0",
                "symbol-tree": "^3.2.4",
                "tough-cookie": "^5.1.1",
                "w3c-xmlserializer": "^5.0.0",
                "webidl-conversions": "^7.0.0",
                "whatwg-encoding": "^3.1.1",
                "whatwg-mimetype": "^4.0.0",
                "whatwg-url": "^14.1.1",
                "ws": "^8.18.0",
                "xml-name-validator": "^5.0.0"
            },
            "engines": {
                "node": ">=18"
            },
            "peerDependencies": {
                "canvas": "^3.0.0"
            },
            "peerDependenciesMeta": {
                "canvas": {
                    "optional": true
                }
            }
        },
        "node_modules/jspdf": {
            "version": "3.0.4",
            "resolved": "https://registry.npmjs.org/jspdf/-/jspdf-3.0.4.tgz",
            "integrity": "sha512-dc6oQ8y37rRcHn316s4ngz/nOjayLF/FFxBF4V9zamQKRqXxyiH1zagkCdktdWhtoQId5K20xt1lB90XzkB+hQ==",
            "license": "MIT",
            "dependencies": {
                "@babel/runtime": "^7.28.4",
                "fast-png": "^6.2.0",
                "fflate": "^0.8.1"
            },
            "optionalDependencies": {
                "canvg": "^3.0.11",
                "core-js": "^3.6.0",
                "dompurify": "^3.2.4",
                "html2canvas": "^1.0.0-rc.5"
            }
        },
        "node_modules/jspdf-autotable": {
            "version": "5.0.2",
            "resolved": "https://registry.npmjs.org/jspdf-autotable/-/jspdf-autotable-5.0.2.tgz",
            "integrity": "sha512-YNKeB7qmx3pxOLcNeoqAv3qTS7KuvVwkFe5AduCawpop3NOkBUtqDToxNc225MlNecxT4kP2Zy3z/y/yvGdXUQ==",
            "license": "MIT",
            "peerDependencies": {
                "jspdf": "^2 || ^3"
            }
        },
        "node_modules/laravel-vite-plugin": {
            "version": "2.1.0",
            "resolved": "https://registry.npmjs.org/laravel-vite-plugin/-/laravel-vite-plugin-2.1.0.tgz",
            "integrity": "sha512-z+ck2BSV6KWtYcoIzk9Y5+p4NEjqM+Y4i8/H+VZRLq0OgNjW2DqyADquwYu5j8qRvaXwzNmfCWl1KrMlV1zpsg==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "picocolors": "^1.0.0",
                "vite-plugin-full-reload": "^1.1.0"
            },
            "bin": {
                "clean-orphaned-assets": "bin/clean.js"
            },
            "engines": {
                "node": "^20.19.0 || >=22.12.0"
            },
            "peerDependencies": {
                "vite": "^7.0.0"
            }
        },
        "node_modules/lightningcss": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss/-/lightningcss-1.30.2.tgz",
            "integrity": "sha512-utfs7Pr5uJyyvDETitgsaqSyjCb2qNRAtuqUeWIAKztsOYdcACf2KtARYXg2pSvhkt+9NfoaNY7fxjl6nuMjIQ==",
            "dev": true,
            "license": "MPL-2.0",
            "dependencies": {
                "detect-libc": "^2.0.3"
            },
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            },
            "optionalDependencies": {
                "lightningcss-android-arm64": "1.30.2",
                "lightningcss-darwin-arm64": "1.30.2",
                "lightningcss-darwin-x64": "1.30.2",
                "lightningcss-freebsd-x64": "1.30.2",
                "lightningcss-linux-arm-gnueabihf": "1.30.2",
                "lightningcss-linux-arm64-gnu": "1.30.2",
                "lightningcss-linux-arm64-musl": "1.30.2",
                "lightningcss-linux-x64-gnu": "1.30.2",
                "lightningcss-linux-x64-musl": "1.30.2",
                "lightningcss-win32-arm64-msvc": "1.30.2",
                "lightningcss-win32-x64-msvc": "1.30.2"
            }
        },
        "node_modules/lightningcss-android-arm64": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-android-arm64/-/lightningcss-android-arm64-1.30.2.tgz",
            "integrity": "sha512-BH9sEdOCahSgmkVhBLeU7Hc9DWeZ1Eb6wNS6Da8igvUwAe0sqROHddIlvU06q3WyXVEOYDZ6ykBZQnjTbmo4+A==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "android"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-darwin-arm64": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-darwin-arm64/-/lightningcss-darwin-arm64-1.30.2.tgz",
            "integrity": "sha512-ylTcDJBN3Hp21TdhRT5zBOIi73P6/W0qwvlFEk22fkdXchtNTOU4Qc37SkzV+EKYxLouZ6M4LG9NfZ1qkhhBWA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-darwin-x64": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-darwin-x64/-/lightningcss-darwin-x64-1.30.2.tgz",
            "integrity": "sha512-oBZgKchomuDYxr7ilwLcyms6BCyLn0z8J0+ZZmfpjwg9fRVZIR5/GMXd7r9RH94iDhld3UmSjBM6nXWM2TfZTQ==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "darwin"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-freebsd-x64": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-freebsd-x64/-/lightningcss-freebsd-x64-1.30.2.tgz",
            "integrity": "sha512-c2bH6xTrf4BDpK8MoGG4Bd6zAMZDAXS569UxCAGcA7IKbHNMlhGQ89eRmvpIUGfKWNVdbhSbkQaWhEoMGmGslA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "freebsd"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-arm-gnueabihf": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-arm-gnueabihf/-/lightningcss-linux-arm-gnueabihf-1.30.2.tgz",
            "integrity": "sha512-eVdpxh4wYcm0PofJIZVuYuLiqBIakQ9uFZmipf6LF/HRj5Bgm0eb3qL/mr1smyXIS1twwOxNWndd8z0E374hiA==",
            "cpu": [
                "arm"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-arm64-gnu": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-arm64-gnu/-/lightningcss-linux-arm64-gnu-1.30.2.tgz",
            "integrity": "sha512-UK65WJAbwIJbiBFXpxrbTNArtfuznvxAJw4Q2ZGlU8kPeDIWEX1dg3rn2veBVUylA2Ezg89ktszWbaQnxD/e3A==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-arm64-musl": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-arm64-musl/-/lightningcss-linux-arm64-musl-1.30.2.tgz",
            "integrity": "sha512-5Vh9dGeblpTxWHpOx8iauV02popZDsCYMPIgiuw97OJ5uaDsL86cnqSFs5LZkG3ghHoX5isLgWzMs+eD1YzrnA==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-x64-gnu": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-x64-gnu/-/lightningcss-linux-x64-gnu-1.30.2.tgz",
            "integrity": "sha512-Cfd46gdmj1vQ+lR6VRTTadNHu6ALuw2pKR9lYq4FnhvgBc4zWY1EtZcAc6EffShbb1MFrIPfLDXD6Xprbnni4w==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-linux-x64-musl": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-linux-x64-musl/-/lightningcss-linux-x64-musl-1.30.2.tgz",
            "integrity": "sha512-XJaLUUFXb6/QG2lGIW6aIk6jKdtjtcffUT0NKvIqhSBY3hh9Ch+1LCeH80dR9q9LBjG3ewbDjnumefsLsP6aiA==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "linux"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-win32-arm64-msvc": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-win32-arm64-msvc/-/lightningcss-win32-arm64-msvc-1.30.2.tgz",
            "integrity": "sha512-FZn+vaj7zLv//D/192WFFVA0RgHawIcHqLX9xuWiQt7P0PtdFEVaxgF9rjM/IRYHQXNnk61/H/gb2Ei+kUQ4xQ==",
            "cpu": [
                "arm64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lightningcss-win32-x64-msvc": {
            "version": "1.30.2",
            "resolved": "https://registry.npmjs.org/lightningcss-win32-x64-msvc/-/lightningcss-win32-x64-msvc-1.30.2.tgz",
            "integrity": "sha512-5g1yc73p+iAkid5phb4oVFMB45417DkRevRbt/El/gKXJk4jid+vPFF/AXbxn05Aky8PapwzZrdJShv5C0avjw==",
            "cpu": [
                "x64"
            ],
            "dev": true,
            "license": "MPL-2.0",
            "optional": true,
            "os": [
                "win32"
            ],
            "engines": {
                "node": ">= 12.0.0"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/parcel"
            }
        },
        "node_modules/lru-cache": {
            "version": "10.4.3",
            "resolved": "https://registry.npmjs.org/lru-cache/-/lru-cache-10.4.3.tgz",
            "integrity": "sha512-JNAzZcXrCt42VGLuYz0zfAzDfAvJWW6AfYlDBQyDV5DClI2m5sAmK+OIO7s59XfsRsWHp02jAJrRadPRGTt6SQ==",
            "dev": true,
            "license": "ISC"
        },
        "node_modules/magic-string": {
            "version": "0.30.21",
            "resolved": "https://registry.npmjs.org/magic-string/-/magic-string-0.30.21.tgz",
            "integrity": "sha512-vd2F4YUyEXKGcLHoq+TEyCjxueSeHnFxyyjNp80yg0XV4vUhnDer/lvvlqM/arB5bXQN5K2/3oinyCRyx8T2CQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@jridgewell/sourcemap-codec": "^1.5.5"
            }
        },
        "node_modules/math-intrinsics": {
            "version": "1.1.0",
            "resolved": "https://registry.npmjs.org/math-intrinsics/-/math-intrinsics-1.1.0.tgz",
            "integrity": "sha512-/IXtbwEk5HTPyEwyKX6hGkYXxM9nbj64B+ilVJnC/R6B0pH5G4V3b0pVbL7DBj4tkhBAppbQUlf6F6Xl9LHu1g==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            }
        },
        "node_modules/mime-db": {
            "version": "1.52.0",
            "resolved": "https://registry.npmjs.org/mime-db/-/mime-db-1.52.0.tgz",
            "integrity": "sha512-sPU4uV7dYlvtWJxwwxHD0PuihVNiE7TyAbQ5SWxDCB9mUYvOgroQOwYQQOKPJ8CIbE+1ETVlOoK1UC2nU3gYvg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.6"
            }
        },
        "node_modules/mime-types": {
            "version": "2.1.35",
            "resolved": "https://registry.npmjs.org/mime-types/-/mime-types-2.1.35.tgz",
            "integrity": "sha512-ZDY+bPm5zTTF+YpCrAU9nK0UgICYPT0QtT1NZWFv4s++TNkcgVaT0g6+4R2uI4MjQjzysHB1zxuWL50hzaeXiw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "mime-db": "1.52.0"
            },
            "engines": {
                "node": ">= 0.6"
            }
        },
        "node_modules/ms": {
            "version": "2.1.3",
            "resolved": "https://registry.npmjs.org/ms/-/ms-2.1.3.tgz",
            "integrity": "sha512-6FlzubTLZG3J2a/NVCAleEhjzq5oxgHyaCU9yYXvcLsvoVaHJq/s5xXI6/XXP6tz7R9xAOtHnSO/tXtF3WRTlA==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/nanoid": {
            "version": "3.3.11",
            "resolved": "https://registry.npmjs.org/nanoid/-/nanoid-3.3.11.tgz",
            "integrity": "sha512-N8SpfPUnUp1bK+PMYW8qSWdl9U+wwNWI4QKxOYDy9JAro3WMX7p2OeVRF9v+347pnakNevPmiHhNmZ2HbFA76w==",
            "dev": true,
            "funding": [
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "bin": {
                "nanoid": "bin/nanoid.cjs"
            },
            "engines": {
                "node": "^10 || ^12 || ^13.7 || ^14 || >=15.0.1"
            }
        },
        "node_modules/node-releases": {
            "version": "2.0.27",
            "resolved": "https://registry.npmjs.org/node-releases/-/node-releases-2.0.27.tgz",
            "integrity": "sha512-nmh3lCkYZ3grZvqcCH+fjmQ7X+H0OeZgP40OierEaAptX4XofMh5kwNbWh7lBduUzCcV/8kZ+NDLCwm2iorIlA==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/nwsapi": {
            "version": "2.2.28",
            "resolved": "https://registry.npmjs.org/nwsapi/-/nwsapi-2.2.28.tgz",
            "integrity": "sha512-IlVB7OS7qrOsVYlpnFIkETjMwT9jwvmocJmmM+GZU/PAB3uGi9Ezd7vcWhWBUnSc0ya4ppmQITOyP1ez9gg8cg==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/pako": {
            "version": "2.2.0",
            "resolved": "https://registry.npmjs.org/pako/-/pako-2.2.0.tgz",
            "integrity": "sha512-zJq6RP/5q+TO2OpFV3FHzlPnFjmkb7Nc99a5SNjJE+uu/PkpChs+NIZSSzbBoD+6kjiISXjfYdwj1ZRQ81dz/w==",
            "funding": [
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/puzrin"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/nodeca"
                }
            ],
            "license": "(MIT AND Zlib)"
        },
        "node_modules/parse5": {
            "version": "7.3.0",
            "resolved": "https://registry.npmjs.org/parse5/-/parse5-7.3.0.tgz",
            "integrity": "sha512-IInvU7fabl34qmi9gY8XOVxhYyMyuH2xUNpb2q8/Y+7552KlejkRvqvD19nMoUW/uQGGbqNpA6Tufu5FL5BZgw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "entities": "^6.0.0"
            },
            "funding": {
                "url": "https://github.com/inikulin/parse5?sponsor=1"
            }
        },
        "node_modules/performance-now": {
            "version": "2.1.0",
            "resolved": "https://registry.npmjs.org/performance-now/-/performance-now-2.1.0.tgz",
            "integrity": "sha512-7EAHlyLHI56VEIdK57uwHdHKIaAGbnXPiw0yWbarQZOKaKpvUIgW0jWRVLiatnM+XXlSwsanIBH/hzGMJulMow==",
            "license": "MIT",
            "optional": true
        },
        "node_modules/picocolors": {
            "version": "1.1.1",
            "resolved": "https://registry.npmjs.org/picocolors/-/picocolors-1.1.1.tgz",
            "integrity": "sha512-xceH2snhtb5M9liqDsmEw56le376mTZkEX/jEb/RxNFyegNul7eNslCXP9FDj/Lcu0X8KEyMceP2ntpaHrDEVA==",
            "dev": true,
            "license": "ISC"
        },
        "node_modules/picomatch": {
            "version": "4.0.3",
            "resolved": "https://registry.npmjs.org/picomatch/-/picomatch-4.0.3.tgz",
            "integrity": "sha512-5gTmgEY/sqK6gFXLIsQNH19lWb4ebPDLA4SdLP7dsWkIXHWlG66oPuVvXSGFPppYZz8ZDZq0dYYrbHfBCVUb1Q==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=12"
            },
            "funding": {
                "url": "https://github.com/sponsors/jonschlinkert"
            }
        },
        "node_modules/postcss": {
            "version": "8.5.6",
            "resolved": "https://registry.npmjs.org/postcss/-/postcss-8.5.6.tgz",
            "integrity": "sha512-3Ybi1tAuwAP9s0r1UQ2J4n5Y0G05bJkpUIO0/bI9MhwmD70S5aTWbXGBwxHrelT+XM1k6dM0pk+SwNkpTRN7Pg==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/postcss/"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/postcss"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "dependencies": {
                "nanoid": "^3.3.11",
                "picocolors": "^1.1.1",
                "source-map-js": "^1.2.1"
            },
            "engines": {
                "node": "^10 || ^12 || >=14"
            }
        },
        "node_modules/postcss-value-parser": {
            "version": "4.2.0",
            "resolved": "https://registry.npmjs.org/postcss-value-parser/-/postcss-value-parser-4.2.0.tgz",
            "integrity": "sha512-1NNCs6uurfkVbeXG4S8JFT9t19m45ICnif8zWLd5oPSZ50QnwMfK+H3jv408d4jw/7Bttv5axS5IiHoLaVNHeQ==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/proxy-from-env": {
            "version": "1.1.0",
            "resolved": "https://registry.npmjs.org/proxy-from-env/-/proxy-from-env-1.1.0.tgz",
            "integrity": "sha512-D+zkORCbA9f1tdWRK0RaCR3GPv50cMxcrz4X8k5LTSUD1Dkw47mKJEZQNunItRTkWwgtaUSo1RVFRIG9ZXiFYg==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/punycode": {
            "version": "2.3.1",
            "resolved": "https://registry.npmjs.org/punycode/-/punycode-2.3.1.tgz",
            "integrity": "sha512-vYt7UD1U9Wg6138shLtLOvdAu+8DsC/ilFtEVHcH+wydcSpNE20AfSOduf6MkRFahL5FY7X1oU7nKVZFtfq8Fg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=6"
            }
        },
        "node_modules/raf": {
            "version": "3.4.1",
            "resolved": "https://registry.npmjs.org/raf/-/raf-3.4.1.tgz",
            "integrity": "sha512-Sq4CW4QhwOHE8ucn6J34MqtZCeWFP2aQSmrlroYgqAV1PjStIhJXxYuTgUIfkEk7zTLjmIjLmU5q+fbD1NnOJA==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "performance-now": "^2.1.0"
            }
        },
        "node_modules/regenerator-runtime": {
            "version": "0.13.11",
            "resolved": "https://registry.npmjs.org/regenerator-runtime/-/regenerator-runtime-0.13.11.tgz",
            "integrity": "sha512-kY1AZVr2Ra+t+piVaJ4gxaFaReZVH40AKNo7UCX6W+dEwBo/2oZJzqfuN1qLq1oL45o56cPaTXELwrTh8Fpggg==",
            "license": "MIT",
            "optional": true
        },
        "node_modules/require-directory": {
            "version": "2.1.1",
            "resolved": "https://registry.npmjs.org/require-directory/-/require-directory-2.1.1.tgz",
            "integrity": "sha512-fGxEI7+wsG9xrvdjsrlmL22OMTTiHRwAMroiEeMgq8gzoLC/PQr7RsRDSTLUg/bZAZtF+TVIkHc6/4RIKrui+Q==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=0.10.0"
            }
        },
        "node_modules/rgbcolor": {
            "version": "1.0.1",
            "resolved": "https://registry.npmjs.org/rgbcolor/-/rgbcolor-1.0.1.tgz",
            "integrity": "sha512-9aZLIrhRaD97sgVhtJOW6ckOEh6/GnvQtdVNfdZ6s67+3/XwLS9lBcQYzEEhYVeUowN7pRzMLsyGhK2i/xvWbw==",
            "license": "MIT OR SEE LICENSE IN FEEL-FREE.md",
            "optional": true,
            "engines": {
                "node": ">= 0.8.15"
            }
        },
        "node_modules/rollup": {
            "version": "4.57.1",
            "resolved": "https://registry.npmjs.org/rollup/-/rollup-4.57.1.tgz",
            "integrity": "sha512-oQL6lgK3e2QZeQ7gcgIkS2YZPg5slw37hYufJ3edKlfQSGGm8ICoxswK15ntSzF/a8+h7ekRy7k7oWc3BQ7y8A==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "@types/estree": "1.0.8"
            },
            "bin": {
                "rollup": "dist/bin/rollup"
            },
            "engines": {
                "node": ">=18.0.0",
                "npm": ">=8.0.0"
            },
            "optionalDependencies": {
                "@rollup/rollup-android-arm-eabi": "4.57.1",
                "@rollup/rollup-android-arm64": "4.57.1",
                "@rollup/rollup-darwin-arm64": "4.57.1",
                "@rollup/rollup-darwin-x64": "4.57.1",
                "@rollup/rollup-freebsd-arm64": "4.57.1",
                "@rollup/rollup-freebsd-x64": "4.57.1",
                "@rollup/rollup-linux-arm-gnueabihf": "4.57.1",
                "@rollup/rollup-linux-arm-musleabihf": "4.57.1",
                "@rollup/rollup-linux-arm64-gnu": "4.57.1",
                "@rollup/rollup-linux-arm64-musl": "4.57.1",
                "@rollup/rollup-linux-loong64-gnu": "4.57.1",
                "@rollup/rollup-linux-loong64-musl": "4.57.1",
                "@rollup/rollup-linux-ppc64-gnu": "4.57.1",
                "@rollup/rollup-linux-ppc64-musl": "4.57.1",
                "@rollup/rollup-linux-riscv64-gnu": "4.57.1",
                "@rollup/rollup-linux-riscv64-musl": "4.57.1",
                "@rollup/rollup-linux-s390x-gnu": "4.57.1",
                "@rollup/rollup-linux-x64-gnu": "4.57.1",
                "@rollup/rollup-linux-x64-musl": "4.57.1",
                "@rollup/rollup-openbsd-x64": "4.57.1",
                "@rollup/rollup-openharmony-arm64": "4.57.1",
                "@rollup/rollup-win32-arm64-msvc": "4.57.1",
                "@rollup/rollup-win32-ia32-msvc": "4.57.1",
                "@rollup/rollup-win32-x64-gnu": "4.57.1",
                "@rollup/rollup-win32-x64-msvc": "4.57.1",
                "fsevents": "~2.3.2"
            }
        },
        "node_modules/rrweb-cssom": {
            "version": "0.8.0",
            "resolved": "https://registry.npmjs.org/rrweb-cssom/-/rrweb-cssom-0.8.0.tgz",
            "integrity": "sha512-guoltQEx+9aMf2gDZ0s62EcV8lsXR+0w8915TC3ITdn2YueuNjdAYh/levpU9nFaoChh9RUS5ZdQMrKfVEN9tw==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/rxjs": {
            "version": "7.8.2",
            "resolved": "https://registry.npmjs.org/rxjs/-/rxjs-7.8.2.tgz",
            "integrity": "sha512-dhKf903U/PQZY6boNNtAGdWbG85WAbjT/1xYoZIC7FAY0yWapOBQVsVrDl58W86//e1VpMNBtRV4MaXfdMySFA==",
            "dev": true,
            "license": "Apache-2.0",
            "dependencies": {
                "tslib": "^2.1.0"
            }
        },
        "node_modules/safer-buffer": {
            "version": "2.1.2",
            "resolved": "https://registry.npmjs.org/safer-buffer/-/safer-buffer-2.1.2.tgz",
            "integrity": "sha512-YZo3K82SD7Riyi0E1EQPojLz7kpepnSQI9IyPbHHg1XXXevb5dJI7tpyN2ADxGcQbHG7vcyRHk0cbwqcQriUtg==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/saxes": {
            "version": "6.0.0",
            "resolved": "https://registry.npmjs.org/saxes/-/saxes-6.0.0.tgz",
            "integrity": "sha512-xAg7SOnEhrm5zI3puOOKyy1OMcMlIJZYNJY7xLBwSze0UjhPLnWfj2GF2EpT0jmzaJKIWKHLsaSSajf35bcYnA==",
            "dev": true,
            "license": "ISC",
            "dependencies": {
                "xmlchars": "^2.2.0"
            },
            "engines": {
                "node": ">=v12.22.7"
            }
        },
        "node_modules/shell-quote": {
            "version": "1.8.3",
            "resolved": "https://registry.npmjs.org/shell-quote/-/shell-quote-1.8.3.tgz",
            "integrity": "sha512-ObmnIF4hXNg1BqhnHmgbDETF8dLPCggZWBjkQfhZpbszZnYur5DUljTcCHii5LC3J5E0yeO/1LIMyH+UvHQgyw==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">= 0.4"
            },
            "funding": {
                "url": "https://github.com/sponsors/ljharb"
            }
        },
        "node_modules/source-map-js": {
            "version": "1.2.1",
            "resolved": "https://registry.npmjs.org/source-map-js/-/source-map-js-1.2.1.tgz",
            "integrity": "sha512-UXWMKhLOwVKb728IUtQPXxfYU+usdybtUrK/8uGE8CQMvrhOpwvzDBwj0QhSL7MQc7vIsISBG8VQ8+IDQxpfQA==",
            "dev": true,
            "license": "BSD-3-Clause",
            "engines": {
                "node": ">=0.10.0"
            }
        },
        "node_modules/stackblur-canvas": {
            "version": "2.7.0",
            "resolved": "https://registry.npmjs.org/stackblur-canvas/-/stackblur-canvas-2.7.0.tgz",
            "integrity": "sha512-yf7OENo23AGJhBriGx0QivY5JP6Y1HbrrDI6WLt6C5auYZXlQrheoY8hD4ibekFKz1HOfE48Ww8kMWMnJD/zcQ==",
            "license": "MIT",
            "optional": true,
            "engines": {
                "node": ">=0.1.14"
            }
        },
        "node_modules/string-width": {
            "version": "4.2.3",
            "resolved": "https://registry.npmjs.org/string-width/-/string-width-4.2.3.tgz",
            "integrity": "sha512-wKyQRQpjJ0sIp62ErSZdGsjMJWsap5oRNihHhu6G7JVO/9jIB6UyevL+tXuOqrng8j/cxKTWyWUwvSTriiZz/g==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "emoji-regex": "^8.0.0",
                "is-fullwidth-code-point": "^3.0.0",
                "strip-ansi": "^6.0.1"
            },
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/strip-ansi": {
            "version": "6.0.1",
            "resolved": "https://registry.npmjs.org/strip-ansi/-/strip-ansi-6.0.1.tgz",
            "integrity": "sha512-Y38VPSHcqkFrCpFnQ9vuSXmquuv5oXOKpGeT6aGrr3o3Gc9AlVa6JBfUSOCnbxGGZF+/0ooI7KrPuUSztUdU5A==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "ansi-regex": "^5.0.1"
            },
            "engines": {
                "node": ">=8"
            }
        },
        "node_modules/supports-color": {
            "version": "8.1.1",
            "resolved": "https://registry.npmjs.org/supports-color/-/supports-color-8.1.1.tgz",
            "integrity": "sha512-MpUEN2OodtUzxvKQl72cUF7RQ5EiHsGvSsVG0ia9c5RbWGL2CI4C7EpPS8UTBIplnlzZiNuV56w+FuNxy3ty2Q==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "has-flag": "^4.0.0"
            },
            "engines": {
                "node": ">=10"
            },
            "funding": {
                "url": "https://github.com/chalk/supports-color?sponsor=1"
            }
        },
        "node_modules/svg-pathdata": {
            "version": "6.0.3",
            "resolved": "https://registry.npmjs.org/svg-pathdata/-/svg-pathdata-6.0.3.tgz",
            "integrity": "sha512-qsjeeq5YjBZ5eMdFuUa4ZosMLxgr5RZ+F+Y1OrDhuOCEInRMA3x74XdBtggJcj9kOeInz0WE+LgCPDkZFlBYJw==",
            "license": "MIT",
            "optional": true,
            "engines": {
                "node": ">=12.0.0"
            }
        },
        "node_modules/symbol-tree": {
            "version": "3.2.4",
            "resolved": "https://registry.npmjs.org/symbol-tree/-/symbol-tree-3.2.4.tgz",
            "integrity": "sha512-9QNk5KwDF+Bvz+PyObkmSYjI5ksVUYtjW7AU22r2NKcfLJcXp96hkDWU3+XndOsUb+AQ9QhfzfCT2O+CNWT5Tw==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/tailwindcss": {
            "version": "4.1.18",
            "resolved": "https://registry.npmjs.org/tailwindcss/-/tailwindcss-4.1.18.tgz",
            "integrity": "sha512-4+Z+0yiYyEtUVCScyfHCxOYP06L5Ne+JiHhY2IjR2KWMIWhJOYZKLSGZaP5HkZ8+bY0cxfzwDE5uOmzFXyIwxw==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/tapable": {
            "version": "2.3.0",
            "resolved": "https://registry.npmjs.org/tapable/-/tapable-2.3.0.tgz",
            "integrity": "sha512-g9ljZiwki/LfxmQADO3dEY1CbpmXT5Hm2fJ+QaGKwSXUylMybePR7/67YW7jOrrvjEgL1Fmz5kzyAjWVWLlucg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=6"
            },
            "funding": {
                "type": "opencollective",
                "url": "https://opencollective.com/webpack"
            }
        },
        "node_modules/text-segmentation": {
            "version": "1.0.3",
            "resolved": "https://registry.npmjs.org/text-segmentation/-/text-segmentation-1.0.3.tgz",
            "integrity": "sha512-iOiPUo/BGnZ6+54OsWxZidGCsdU8YbE4PSpdPinp7DeMtUJNJBoJ/ouUSTJjHkh1KntHaltHl/gDs2FC4i5+Nw==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "utrie": "^1.0.2"
            }
        },
        "node_modules/tinyglobby": {
            "version": "0.2.15",
            "resolved": "https://registry.npmjs.org/tinyglobby/-/tinyglobby-0.2.15.tgz",
            "integrity": "sha512-j2Zq4NyQYG5XMST4cbs02Ak8iJUdxRM0XI5QyxXuZOzKOINmWurp3smXu3y5wDcJrptwpSjgXHzIQxR0omXljQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "fdir": "^6.5.0",
                "picomatch": "^4.0.3"
            },
            "engines": {
                "node": ">=12.0.0"
            },
            "funding": {
                "url": "https://github.com/sponsors/SuperchupuDev"
            }
        },
        "node_modules/tldts": {
            "version": "6.1.86",
            "resolved": "https://registry.npmjs.org/tldts/-/tldts-6.1.86.tgz",
            "integrity": "sha512-WMi/OQ2axVTf/ykqCQgXiIct+mSQDFdH2fkwhPwgEwvJ1kSzZRiinb0zF2Xb8u4+OqPChmyI6MEu4EezNJz+FQ==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "tldts-core": "^6.1.86"
            },
            "bin": {
                "tldts": "bin/cli.js"
            }
        },
        "node_modules/tldts-core": {
            "version": "6.1.86",
            "resolved": "https://registry.npmjs.org/tldts-core/-/tldts-core-6.1.86.tgz",
            "integrity": "sha512-Je6p7pkk+KMzMv2XXKmAE3McmolOQFdxkKw0R8EYNr7sELW46JqnNeTX8ybPiQgvg1ymCoF8LXs5fzFaZvJPTA==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/tough-cookie": {
            "version": "5.1.2",
            "resolved": "https://registry.npmjs.org/tough-cookie/-/tough-cookie-5.1.2.tgz",
            "integrity": "sha512-FVDYdxtnj0G6Qm/DhNPSb8Ju59ULcup3tuJxkFb5K8Bv2pUXILbf0xZWU8PX8Ov19OXljbUyveOFwRMwkXzO+A==",
            "dev": true,
            "license": "BSD-3-Clause",
            "dependencies": {
                "tldts": "^6.1.32"
            },
            "engines": {
                "node": ">=16"
            }
        },
        "node_modules/tr46": {
            "version": "5.1.1",
            "resolved": "https://registry.npmjs.org/tr46/-/tr46-5.1.1.tgz",
            "integrity": "sha512-hdF5ZgjTqgAntKkklYw0R03MG2x/bSzTtkxmIRw/sTNV8YXsCJ1tfLAX23lhxhHJlEf3CRCOCGGWw3vI3GaSPw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "punycode": "^2.3.1"
            },
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/tree-kill": {
            "version": "1.2.2",
            "resolved": "https://registry.npmjs.org/tree-kill/-/tree-kill-1.2.2.tgz",
            "integrity": "sha512-L0Orpi8qGpRG//Nd+H90vFB+3iHnue1zSSGmNOOCh1GLJ7rUKVwV2HvijphGQS2UmhUZewS9VgvxYIdgr+fG1A==",
            "dev": true,
            "license": "MIT",
            "bin": {
                "tree-kill": "cli.js"
            }
        },
        "node_modules/tslib": {
            "version": "2.8.1",
            "resolved": "https://registry.npmjs.org/tslib/-/tslib-2.8.1.tgz",
            "integrity": "sha512-oJFu94HQb+KVduSUQL7wnpmqnfmLsOA/nAh6b6EH0wCEoK0/mPeXU6c3wKDV83MkOuHPRHtSXKKU99IBazS/2w==",
            "dev": true,
            "license": "0BSD"
        },
        "node_modules/update-browserslist-db": {
            "version": "1.2.3",
            "resolved": "https://registry.npmjs.org/update-browserslist-db/-/update-browserslist-db-1.2.3.tgz",
            "integrity": "sha512-Js0m9cx+qOgDxo0eMiFGEueWztz+d4+M3rGlmKPT+T4IS/jP4ylw3Nwpu6cpTTP8R1MAC1kF4VbdLt3ARf209w==",
            "dev": true,
            "funding": [
                {
                    "type": "opencollective",
                    "url": "https://opencollective.com/browserslist"
                },
                {
                    "type": "tidelift",
                    "url": "https://tidelift.com/funding/github/npm/browserslist"
                },
                {
                    "type": "github",
                    "url": "https://github.com/sponsors/ai"
                }
            ],
            "license": "MIT",
            "dependencies": {
                "escalade": "^3.2.0",
                "picocolors": "^1.1.1"
            },
            "bin": {
                "update-browserslist-db": "cli.js"
            },
            "peerDependencies": {
                "browserslist": ">= 4.21.0"
            }
        },
        "node_modules/utrie": {
            "version": "1.0.2",
            "resolved": "https://registry.npmjs.org/utrie/-/utrie-1.0.2.tgz",
            "integrity": "sha512-1MLa5ouZiOmQzUbjbu9VmjLzn1QLXBhwpUa7kdLUQK+KQ5KA9I1vk5U4YHe/X2Ch7PYnJfWuWT+VbuxbGwljhw==",
            "license": "MIT",
            "optional": true,
            "dependencies": {
                "base64-arraybuffer": "^1.0.2"
            }
        },
        "node_modules/vite": {
            "version": "7.3.1",
            "resolved": "https://registry.npmjs.org/vite/-/vite-7.3.1.tgz",
            "integrity": "sha512-w+N7Hifpc3gRjZ63vYBXA56dvvRlNWRczTdmCBBa+CotUzAPf5b7YMdMR/8CQoeYE5LX3W4wj6RYTgonm1b9DA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "esbuild": "^0.27.0",
                "fdir": "^6.5.0",
                "picomatch": "^4.0.3",
                "postcss": "^8.5.6",
                "rollup": "^4.43.0",
                "tinyglobby": "^0.2.15"
            },
            "bin": {
                "vite": "bin/vite.js"
            },
            "engines": {
                "node": "^20.19.0 || >=22.12.0"
            },
            "funding": {
                "url": "https://github.com/vitejs/vite?sponsor=1"
            },
            "optionalDependencies": {
                "fsevents": "~2.3.3"
            },
            "peerDependencies": {
                "@types/node": "^20.19.0 || >=22.12.0",
                "jiti": ">=1.21.0",
                "less": "^4.0.0",
                "lightningcss": "^1.21.0",
                "sass": "^1.70.0",
                "sass-embedded": "^1.70.0",
                "stylus": ">=0.54.8",
                "sugarss": "^5.0.0",
                "terser": "^5.16.0",
                "tsx": "^4.8.1",
                "yaml": "^2.4.2"
            },
            "peerDependenciesMeta": {
                "@types/node": {
                    "optional": true
                },
                "jiti": {
                    "optional": true
                },
                "less": {
                    "optional": true
                },
                "lightningcss": {
                    "optional": true
                },
                "sass": {
                    "optional": true
                },
                "sass-embedded": {
                    "optional": true
                },
                "stylus": {
                    "optional": true
                },
                "sugarss": {
                    "optional": true
                },
                "terser": {
                    "optional": true
                },
                "tsx": {
                    "optional": true
                },
                "yaml": {
                    "optional": true
                }
            }
        },
        "node_modules/vite-plugin-full-reload": {
            "version": "1.2.0",
            "resolved": "https://registry.npmjs.org/vite-plugin-full-reload/-/vite-plugin-full-reload-1.2.0.tgz",
            "integrity": "sha512-kz18NW79x0IHbxRSHm0jttP4zoO9P9gXh+n6UTwlNKnviTTEpOlum6oS9SmecrTtSr+muHEn5TUuC75UovQzcA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "picocolors": "^1.0.0",
                "picomatch": "^2.3.1"
            }
        },
        "node_modules/vite-plugin-full-reload/node_modules/picomatch": {
            "version": "2.3.1",
            "resolved": "https://registry.npmjs.org/picomatch/-/picomatch-2.3.1.tgz",
            "integrity": "sha512-JU3teHTNjmE2VCGFzuY8EXzCDVwEqB2a8fsIvwaStHhAWJEeVd1o1QD80CU6+ZdEXXSLbSsuLwJjkCBWqRQUVA==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=8.6"
            },
            "funding": {
                "url": "https://github.com/sponsors/jonschlinkert"
            }
        },
        "node_modules/w3c-xmlserializer": {
            "version": "5.0.0",
            "resolved": "https://registry.npmjs.org/w3c-xmlserializer/-/w3c-xmlserializer-5.0.0.tgz",
            "integrity": "sha512-o8qghlI8NZHU1lLPrpi2+Uq7abh4GGPpYANlalzWxyWteJOCsr/P+oPBA49TOLu5FTZO4d3F9MnWJfiMo4BkmA==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "xml-name-validator": "^5.0.0"
            },
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/webidl-conversions": {
            "version": "7.0.0",
            "resolved": "https://registry.npmjs.org/webidl-conversions/-/webidl-conversions-7.0.0.tgz",
            "integrity": "sha512-VwddBukDzu71offAQR975unBIGqfKZpM+8ZX6ySk8nYhVoo5CYaZyzt3YBvYtRtO+aoGlqxPg/B87NGVZ/fu6g==",
            "dev": true,
            "license": "BSD-2-Clause",
            "engines": {
                "node": ">=12"
            }
        },
        "node_modules/whatwg-encoding": {
            "version": "3.1.1",
            "resolved": "https://registry.npmjs.org/whatwg-encoding/-/whatwg-encoding-3.1.1.tgz",
            "integrity": "sha512-6qN4hJdMwfYBtE3YBTTHhoeuUrDBPZmbQaxWAqSALV/MeEnR5z1xd8UKud2RAkFoPkmB+hli1TZSnyi84xz1vQ==",
            "deprecated": "Use @exodus/bytes instead for a more spec-conformant and faster implementation",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "iconv-lite": "0.6.3"
            },
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/whatwg-mimetype": {
            "version": "4.0.0",
            "resolved": "https://registry.npmjs.org/whatwg-mimetype/-/whatwg-mimetype-4.0.0.tgz",
            "integrity": "sha512-QaKxh0eNIi2mE9p2vEdzfagOKHCcj1pJ56EEHGQOVxp8r9/iszLUUV7v89x9O1p/T+NlTM5W7jW6+cz4Fq1YVg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/whatwg-url": {
            "version": "14.2.0",
            "resolved": "https://registry.npmjs.org/whatwg-url/-/whatwg-url-14.2.0.tgz",
            "integrity": "sha512-De72GdQZzNTUBBChsXueQUnPKDkg/5A5zp7pFDuQAj5UFoENpiACU0wlCvzpAGnTkj++ihpKwKyYewn/XNUbKw==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "tr46": "^5.1.0",
                "webidl-conversions": "^7.0.0"
            },
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/wrap-ansi": {
            "version": "7.0.0",
            "resolved": "https://registry.npmjs.org/wrap-ansi/-/wrap-ansi-7.0.0.tgz",
            "integrity": "sha512-YVGIj2kamLSTxw6NsZjoBxfSwsn0ycdesmc4p+Q21c5zPuZ1pl+NfxVdxPtdHvmNVOQ6XSYG4AUtyt/Fi7D16Q==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "ansi-styles": "^4.0.0",
                "string-width": "^4.1.0",
                "strip-ansi": "^6.0.0"
            },
            "engines": {
                "node": ">=10"
            },
            "funding": {
                "url": "https://github.com/chalk/wrap-ansi?sponsor=1"
            }
        },
        "node_modules/ws": {
            "version": "8.22.0",
            "resolved": "https://registry.npmjs.org/ws/-/ws-8.22.0.tgz",
            "integrity": "sha512-Ydggc987+RO0AnWtZ/7Wq9FtNvcrL1b/RO0ud9mWjUPgDrsAAwQSF51sm2hm1XofbU/4jkpGEsLFsZZxU+1DOg==",
            "dev": true,
            "license": "MIT",
            "engines": {
                "node": ">=10.0.0"
            },
            "peerDependencies": {
                "bufferutil": "^4.0.1",
                "utf-8-validate": ">=5.0.2"
            },
            "peerDependenciesMeta": {
                "bufferutil": {
                    "optional": true
                },
                "utf-8-validate": {
                    "optional": true
                }
            }
        },
        "node_modules/xml-name-validator": {
            "version": "5.0.0",
            "resolved": "https://registry.npmjs.org/xml-name-validator/-/xml-name-validator-5.0.0.tgz",
            "integrity": "sha512-EvGK8EJ3DhaHfbRlETOWAS5pO9MZITeauHKJyb8wyajUfQUenkIg2MvLDTZ4T/TgIcm3HU0TFBgWWboAZ30UHg==",
            "dev": true,
            "license": "Apache-2.0",
            "engines": {
                "node": ">=18"
            }
        },
        "node_modules/xmlchars": {
            "version": "2.2.0",
            "resolved": "https://registry.npmjs.org/xmlchars/-/xmlchars-2.2.0.tgz",
            "integrity": "sha512-JZnDKK8B0RCDw84FNdDAIpZK+JuJw+s7Lz8nksI7SIuU3UXJJslUthsi+uWBUYOwPFwW7W7PRLRfUKpxjtjFCw==",
            "dev": true,
            "license": "MIT"
        },
        "node_modules/y18n": {
            "version": "5.0.8",
            "resolved": "https://registry.npmjs.org/y18n/-/y18n-5.0.8.tgz",
            "integrity": "sha512-0pfFzegeDWJHJIAmTLRP2DwHjdF5s7jo9tuztdQxAhINCdvS+3nGINqPd00AphqJR/0LhANUS6/+7SCb98YOfA==",
            "dev": true,
            "license": "ISC",
            "engines": {
                "node": ">=10"
            }
        },
        "node_modules/yargs": {
            "version": "17.7.2",
            "resolved": "https://registry.npmjs.org/yargs/-/yargs-17.7.2.tgz",
            "integrity": "sha512-7dSzzRQ++CKnNI/krKnYRV7JKKPUXMEh61soaHKg9mrWEhzFWhFnxPxGl+69cD1Ou63C13NUPCnmIcrvqCuM6w==",
            "dev": true,
            "license": "MIT",
            "dependencies": {
                "cliui": "^8.0.1",
                "escalade": "^3.1.1",
                "get-caller-file": "^2.0.5",
                "require-directory": "^2.1.1",
                "string-width": "^4.2.3",
                "y18n": "^5.0.5",
                "yargs-parser": "^21.1.1"
            },
            "engines": {
                "node": ">=12"
            }
        },
        "node_modules/yargs-parser": {
            "version": "21.1.1",
            "resolved": "https://registry.npmjs.org/yargs-parser/-/yargs-parser-21.1.1.tgz",
            "integrity": "sha512-tVpsJW7DdjecAiFpbIB1e3qxIQsE6NoPc5/eTdrbbIC4h0LVsWhnoa3g+m2HclBIujHzsxZ4VJVA+GUuc2/LBw==",
            "dev": true,
            "license": "ISC",
            "engines": {
                "node": ">=12"
            }
        }
    }
}
```

## package.json

```json
{
    "$schema": "https://www.schemastore.org/package.json",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite",
        "test:ui": "node tests/ui/run.cjs"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "autoprefixer": "^10.4.24",
        "axios": "^1.11.0",
        "concurrently": "^9.0.1",
        "jsdom": "^26.1.0",
        "laravel-vite-plugin": "^2.0.0",
        "postcss": "^8.5.6",
        "tailwindcss": "^4.1.18",
        "vite": "^7.0.7"
    },
    "dependencies": {
        "alpinejs": "3.14.9",
        "jspdf": "3.0.4",
        "jspdf-autotable": "5.0.2"
    }
}
```

## public/css/erp.css

```css
:root{--ink:#172c29;--muted:#63736d;--paper:#f3f5f1;--line:#dce4dd;--green:#1b5e4c;--lime:#cae9b3;--danger:#a13130;--blue:#315cc4}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:15px/1.6 system-ui,-apple-system,sans-serif}a{color:var(--green)}button,input,select,textarea{font:inherit}button,.button{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;background:#fff;border:1px solid var(--line);color:var(--ink);border-radius:7px;font-size:13px;font-weight:650;text-decoration:none;cursor:pointer}button:hover,.button:hover{background:#eaf0e7;border-color:#a7b9a9}button:disabled{opacity:.5;cursor:wait}.primary{background:var(--green);color:#fff;border-color:var(--green)}.primary:hover{background:#174737;color:#fff}.danger{color:var(--danger)}.sidebar{position:fixed;inset:0 auto 0 0;width:240px;padding:32px 20px;background:#142e29;color:#e0eee6;display:flex;flex-direction:column}.brand{color:#fff;text-decoration:none;font-size:25px;font-weight:850;letter-spacing:-1px;line-height:1.1}.brand span{font-weight:450}.brand small{display:block;font-size:9px;letter-spacing:2.4px;color:#a4c6b5;margin-top:13px}.workspace{font-size:9px;letter-spacing:2px;color:#8eb19f;margin:44px 10px 16px}nav{display:flex;flex-direction:column;gap:7px}nav button,nav a{justify-content:flex-start;border:0;background:transparent;color:#c7d7ce;padding:12px 13px;font-size:12px;text-decoration:none;text-align:left}nav button span,nav a span{width:22px;font-size:10px;color:#8dbba7}nav button[aria-current],nav button:hover,nav a:hover{background:#244b3d;color:#fff}nav button[aria-current] span{color:var(--lime)}.sidebar-note{margin-top:auto;border-top:1px solid #385449;padding:22px 8px 0;font-size:11px;font-weight:650}.sidebar-note p{font-size:11px;color:#9ab2a5;font-weight:400}.live-dot{display:inline-block;width:7px;height:7px;background:var(--lime);border-radius:50%;margin-right:6px}.workspace-content{margin-left:240px}.topbar{background:#fff;border-bottom:1px solid var(--line);min-height:90px;padding:20px 38px;display:flex;align-items:center;justify-content:space-between;gap:15px}.eyebrow{display:block;font-size:10px;font-weight:750;letter-spacing:1.7px;color:var(--green);margin:0 0 6px}.topbar strong{font-size:15px;font-weight:600}.account{display:flex;align-items:center;gap:22px;font-size:13px}.account small{display:block;color:var(--muted);font-size:11px}.account button{font-size:11px}main{max-width:1600px;margin:auto;padding:35px 38px}.page-heading{display:flex;justify-content:space-between;align-items:center;gap:24px;margin:0 0 28px}.page-heading p{margin:8px 0 0;color:var(--muted)}.page-heading .eyebrow{color:var(--green);margin:0}h1{font-size:clamp(27px,3vw,39px);letter-spacing:-1.4px;line-height:1.15;margin:8px 0 14px}h2{font-size:18px;letter-spacing:-.4px;margin:0 0 18px}h3{font-size:16px;margin:0 0 8px}p{color:var(--muted)}.kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:24px}.kpi{background:#fff;border:1px solid var(--line);padding:23px 19px;border-radius:10px;min-height:127px}.kpi:first-child{background:#e5efdd;border-color:#cadbc0}.kpi span{display:block;font-size:11px;color:var(--muted)}.kpi strong{display:block;font-size:clamp(22px,2vw,30px);line-height:1.3;margin:10px 0 0;letter-spacing:-.8px}.kpi small{display:block;font-size:10px;color:var(--muted);margin-top:8px}.grid-main{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:22px}article{background:#fff;border:1px solid var(--line);padding:26px;border-radius:10px;margin:0 0 22px;min-width:0}.article-heading{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:18px}.article-heading h2{margin:0}.article-heading a{font-size:12px;white-space:nowrap}.pill,.badge{display:inline-block;font-size:10px;font-weight:650;padding:4px 9px;background:#edf2e9;border-radius:5px;color:var(--green);white-space:nowrap}.badge-pendiente{background:#fff0d6;color:#855100}.badge-procesada{background:#e0f0e7;color:#1d674c}.badge-completada{background:#e7ecfb;color:#3153a7}.badge-cancelada{background:#fbe8e5;color:#923e34}.sales-chart{height:230px;display:flex;align-items:flex-end;gap:14px;padding:24px 8px 0;border-bottom:1px solid var(--line);margin-bottom:10px}.month-bar{height:100%;flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;min-width:0;gap:8px}.month-bar .bar{width:min(100%,42px);background:#88ab80;border-radius:4px 4px 0 0;min-height:2px}.month-bar:last-child .bar{background:var(--green)}.month-bar small{font-size:9px;color:var(--muted);white-space:nowrap;padding:5px 0 10px}.month-bar span{font-size:10px;white-space:nowrap}.horizontal-bar{margin:18px 0}.horizontal-bar>div:first-child{display:flex;justify-content:space-between;gap:10px;font-size:12px}.track{background:#eef2ea;border-radius:4px;height:8px;margin-top:8px}.track div{background:var(--green);height:100%;border-radius:4px}.list-row{display:flex;align-items:center;justify-content:space-between;gap:14px;border-bottom:1px solid var(--line);padding:13px 0;font-size:13px}.list-row:last-child{border-bottom:0}.list-row small{display:block;color:var(--muted);font-size:11px}.integration-row{display:flex;gap:15px;padding:14px;background:#f4f7f0;border-radius:7px;margin-top:10px}.integration-row b{font-size:12px;min-width:40px;color:var(--green)}.integration-row span{font-size:12px}.integration-row small{display:block;font-size:11px;color:var(--muted)}.hint{font-size:11px;color:var(--muted);line-height:1.7}.filters{display:flex;gap:14px;align-items:end;flex-wrap:wrap;margin-bottom:22px}.filters label{flex:1;min-width:170px;margin:0}label{display:block;font-size:12px;font-weight:650;margin-bottom:18px}input,select,textarea{display:block;width:100%;border:1px solid #c4d1c7;border-radius:6px;padding:11px 12px;background:#fff;color:var(--ink);margin-top:6px;font-size:14px}textarea{resize:vertical}.check{display:flex;gap:10px;align-items:center}.check input{width:17px;height:17px;margin:0;accent-color:var(--green)}.table-wrap{overflow:auto}.table-article{padding:0 0 18px}table{border-collapse:collapse;width:100%;min-width:630px;font-size:12px}th{text-align:left;font-weight:650;color:var(--muted);background:#f5f7f2;white-space:nowrap}td,th{padding:15px 16px;border-bottom:1px solid var(--line);vertical-align:top}td small{display:block;font-size:11px;color:var(--muted)}td button{padding:7px 10px;font-size:11px;margin:1px}td input[type=number]{width:80px;margin:0;padding:7px}.pagination{display:flex;align-items:center;justify-content:flex-end;gap:12px;font-size:11px;margin-top:18px;padding:0 10px}.actions{display:flex;flex-wrap:wrap;gap:10px}.steps{display:flex;list-style:none;padding:0;margin:0 0 28px;background:#fff;border:1px solid var(--line);border-radius:8px}.steps li{flex:1;padding:20px;font-size:13px;color:var(--muted)}.steps li b{background:#eef2eb;border-radius:50%;padding:5px 10px;margin-right:9px}.steps li[aria-current]{color:var(--green);font-weight:700}.steps li[aria-current] b{background:var(--green);color:#fff}.selection-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.client-option{padding:18px;text-align:left;display:block;font-weight:400}.client-option strong,.client-option small{display:block}.client-option[aria-pressed=true]{background:#e4f1de;border-color:#83a97b}.client-option small{font-size:11px;color:var(--muted);overflow-wrap:anywhere}.wizard-actions{display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin:20px 0}.total-box{display:flex;justify-content:space-between;align-items:center;background:#eff4e9;border-radius:7px;padding:18px;margin-top:20px}.total-box strong{font-size:28px}.success-card{max-width:740px;margin:45px auto;text-align:center;padding:45px}.success-card .wizard-actions{justify-content:center}.success-icon{background:#e6f0de;color:var(--green);width:65px;height:65px;border-radius:50%;font-size:35px;line-height:65px;margin:0 auto 20px}.process-steps{text-align:left;background:#f2f6ee;border-radius:7px;padding:12px 20px;font-size:12px;margin-top:22px}.process-steps p{color:var(--green);margin:10px 0}.levels{display:flex;justify-content:space-between;gap:10px;margin-bottom:28px}.levels span{padding:12px 18px;background:#f0f3eb;border-radius:6px;font-size:12px}.levels .active{background:var(--green);color:#fff}progress{width:100%;height:14px;accent-color:var(--green)}.evidence-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:24px 0}.evidence-list label{border:1px solid var(--line);border-radius:6px;padding:15px;font-weight:400;margin:0}.evidence-list small{display:block;font-size:10px;color:var(--muted)}.notice{padding:16px 20px;background:#e1efda;border:1px solid #b7d0ab;border-radius:7px;margin-bottom:22px;white-space:pre-line;overflow-wrap:anywhere}.notice.error{color:#832b2c;background:#fcece9;border-color:#e3bcba}dialog{width:min(940px,94vw);max-height:90vh;overflow:auto;border:1px solid var(--line);border-radius:12px;padding:30px;color:var(--ink);box-shadow:0 30px 100px #102b2944}dialog::backdrop{background:#122c2877}dialog form{max-width:650px}dialog .grid-main{grid-template-columns:repeat(2,minmax(0,1fr))}.empty{padding:16px 0;color:var(--muted);font-size:13px}footer{display:flex;justify-content:space-between;gap:10px;font-size:10px;color:var(--muted);padding:20px 0;letter-spacing:.3px}[hidden]{display:none!important}:focus-visible{outline:3px solid #8bb47e;outline-offset:3px}@media(max-width:1200px){.sidebar{width:210px;padding:26px 15px}.workspace-content{margin-left:210px}.topbar,main{padding-left:25px;padding-right:25px}.kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.grid-main{grid-template-columns:1fr 1fr}}@media(max-width:900px){.sidebar{position:static;width:100%;padding:20px}.brand{font-size:21px}.brand small,.workspace,.sidebar-note{display:none}nav{flex-direction:row;flex-wrap:wrap;margin-top:18px;gap:5px}nav button,nav a{padding:8px 10px;font-size:11px}nav span{display:none}.workspace-content{margin-left:0}.topbar{min-height:80px}.grid-main{grid-template-columns:1fr}.account{gap:12px}.kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.page-heading{align-items:flex-start}}@media(max-width:600px){main{padding:25px 15px}.topbar{padding:15px;align-items:flex-start}.topbar>div:first-child{max-width:125px}.topbar strong{font-size:12px}.topbar .eyebrow{font-size:8px}.account{font-size:11px}.account button{padding:7px}.page-heading{flex-direction:column;gap:15px}h1{font-size:29px}.kpis{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.kpi{padding:17px 13px;min-height:105px}.kpi strong{font-size:24px}article{padding:20px 16px}.selection-list,.evidence-list{grid-template-columns:1fr}.steps li{padding:14px 9px;font-size:11px}.steps li b{margin-right:3px;padding:3px 7px}.levels{flex-wrap:wrap}.levels span{flex:1;text-align:center;padding:10px;font-size:11px}.success-card{padding:30px 20px;margin:15px auto}footer{flex-direction:column}dialog{padding:20px}.sales-chart{gap:7px}.month-bar span{font-size:8px}.month-bar small{font-size:8px}}@media print{body{background:#fff}.sidebar,.workspace-content,dialog> .article-heading button,#print-order{display:none!important}dialog[open]{display:block;position:static;width:100%;max-height:none;border:0;box-shadow:none;padding:0}dialog::backdrop{background:transparent}dialog .grid-main{display:block}dialog article{break-inside:avoid}table{min-width:0}td,th{padding:8px;font-size:10px}}
```

## public/js/erp.js

```javascript
(() => {
    'use strict';
    const $ = s => document.querySelector(s);
    const $$ = s => [...document.querySelectorAll(s)];
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = v => new Intl.NumberFormat('es-MX', {style:'currency',currency:'MXN'}).format(Number(v || 0));
    const date = v => v ? new Date(v).toLocaleString('es-MX') : '—';
    const permissions = JSON.parse(document.body.dataset.permissions || '[]');
    const can = p => permissions.includes(p);
    const state = {client:null,clients:[],catalog:[],cart:new Map(),step:1,key:null,created:null,saving:false,
        orderPage:1,reportPage:1,clientRevision:0,catalogRevision:0,orderRevision:0,resources:[],reports:[],users:[]};
    const roles = {admin:'Administrador',ventas:'Ventas',logistica:'Logística',usuario:'Operador',cliente:'Cliente'};
    const evidenceLabels = {clientes:'Clientes activos en CRM',inventario:'Catálogo con inventario SCM',ordenes:'Órdenes de clientes procesadas',movimientos:'Movimientos automáticos de inventario',reportes:'Cortes empresariales guardados',procesos:'Procesos internos completados'};

    function notice(message, error = false) {
        const el = $('#notice'), host = $('dialog[open]') || $('main');
        if (el.parentElement !== host) host.prepend(el);
        el.textContent = message; el.classList.toggle('error', error); el.hidden = false;
        el.setAttribute('role', error ? 'alert' : 'status');
        el.scrollIntoView?.({block:'nearest'});
    }
    async function run(button, fn) {
        if (button?.dataset.busy) return;
        const label = button?.innerHTML;
        if (button) {button.dataset.busy='1';button.disabled=true;}
        try {await fn();}
        catch (e) {console.error(e);notice(e.message || 'No se pudo completar la operación.', true);}
        finally {if(button){delete button.dataset.busy;button.disabled=false;button.innerHTML=label;}}
    }
    function pager(root, result, kind) {
        const el = $(root); if (!el) return;
        el.innerHTML = '<button type="button" data-page="'+kind+'" data-number="'+(result.current_page-1)+'" '+(result.current_page<=1?'disabled':'')+'>Anterior</button><span>'+result.total+' registros · '+result.current_page+'/'+result.last_page+'</span><button type="button" data-page="'+kind+'" data-number="'+(result.current_page+1)+'" '+(result.current_page>=result.last_page?'disabled':'')+'>Siguiente</button>';
    }
    function showDialog(id) {
        $('#notice').hidden=true;
        $(id).showModal();
    }
    function clearKey() {state.key=null;}
    function clientCard(c) {
        return '<h3>'+esc(c.nombre)+'</h3><p>'+esc(c.empresa || 'Cliente CRM')+'<br>'+esc(c.correo)+'<br>'+esc(c.telefono || 'Sin teléfono')+'</p>';
    }
    function kpis(m) {
        return [
            ['Ventas totales',money(m.ventas_totales),'ERP + tienda surtida'],
            ['Órdenes ERP',m.ordenes_procesadas,'Procesadas y completadas'],
            ['Productos vendidos',m.productos_vendidos,'Unidades ERP + tienda'],
            ['Clientes activos',m.clientes_activos,'Directorio del CRM'],
            ['Inventario disponible',m.inventario_disponible,'Unidades en SCM']
        ].map(r=>'<div class="kpi"><span>'+r[0]+'</span><strong>'+esc(r[1])+'</strong><small>'+r[2]+'</small></div>').join('');
    }
    async function dashboard() {
        const m=await HF.request('/erp/metricas');
        $('#kpis').innerHTML=kpis(m);
        const max=Math.max(1,...m.ventas_por_mes.map(r=>r.total));
        $('#sales-chart').innerHTML=m.ventas_por_mes.map(r=>'<div class="month-bar"><span>'+money(r.total)+'</span><div class="bar" style="height:'+Math.max(1,r.total/max*150)+'px"></div><small>'+esc(r.etiqueta)+'</small></div>').join('');
        const top=Math.max(1,...m.productos_mas_vendidos.map(r=>r.unidades));
        $('#top-chart').innerHTML=m.productos_mas_vendidos.map(r=>'<div class="horizontal-bar"><div><span>'+esc(r.nombre)+'</span><strong>'+r.unidades+'</strong></div><div class="track"><div style="width:'+r.unidades/top*100+'%"></div></div></div>').join('') || '<p class="empty">Procesa una venta para ver los productos más vendidos.</p>';
        $('#critical-stock').innerHTML=m.inventario_critico.slice(0,6).map(p=>'<div class="list-row"><div><strong>'+esc(p.nombre)+'</strong><small>'+esc(p.estrategia_logistica)+' · mínimo '+p.stock_minimo+'</small></div><span class="badge badge-pendiente">'+p.stock_actual+' unidades</span></div>').join('') || '<p class="empty">El inventario se encuentra por encima de los mínimos.</p>';
        $('#integration').innerHTML='<div class="integration-row"><b>CRM</b><span>'+m.integracion.clientes_crm+' clientes<small>Datos del directorio existente</small></span></div><div class="integration-row"><b>SCM</b><span>'+m.integracion.productos_scm+' productos · '+m.reposiciones_scm_pendientes+' reposiciones pendientes<small>Inventario y proveedores compartidos</small></span></div><div class="integration-row"><b>ERP</b><span>'+m.integracion.ordenes_erp+' órdenes · '+m.ordenes_pendientes+' pendientes<small>'+m.integracion.procesos_completados+' procesos internos completados</small></span></div>';
    }
    async function orders(page=1) {
        state.orderPage=page;const rev=++state.orderRevision;
        const query=new URLSearchParams(new FormData($('#order-filter')));query.set('page',page);
        const r=await HF.request('/ordenes?'+query);if(rev!==state.orderRevision)return;
        $('#order-rows').innerHTML=r.data.map(o=>{
            let actions='<button type="button" data-view="'+o.id+'">Ver orden</button>';
            if(o.estado==='pendiente' && can('erp.write')) actions+='<button type="button" data-process="'+o.id+'">Procesar</button>';
            if(o.estado==='procesada' && can('erp.complete')) actions+='<button type="button" data-state="completada" data-order="'+o.id+'">Completar entrega</button>';
            if(o.estado!=='cancelada' && can('erp.cancel') && (document.body.dataset.role==='admin'||o.estado==='pendiente')) actions+='<button type="button" class="danger" data-state="cancelada" data-order="'+o.id+'">Cancelar</button>';
            return '<tr><td><strong>'+esc(o.folio)+'</strong><small>'+esc(date(o.fecha))+'</small></td><td>'+esc(o.cliente.nombre)+'<small>'+esc(o.cliente.correo)+'</small></td><td>'+esc(o.usuario.name)+'</td><td>'+money(o.total)+'</td><td><span class="badge badge-'+o.estado+'">'+esc(o.estado)+'</span></td><td>'+actions+'</td></tr>';
        }).join('')||'<tr><td colspan="6">No hay órdenes con estos filtros.</td></tr>';
        pager('#order-pages',r,'orders');
    }
    async function clients(page=1) {
        if(!can('erp.write'))return;
        const rev=++state.clientRevision;
        const query=new URLSearchParams(new FormData($('#client-search')));query.set('estado','activo');query.set('page',page);
        const r=await HF.request('/clientes?'+query);if(rev!==state.clientRevision)return;state.clients=r.data;
        $('#client-results').innerHTML=r.data.map(c=>'<button type="button" class="client-option" data-client="'+c.id+'" aria-pressed="'+(state.client?.id===c.id)+'"><strong>'+esc(c.nombre)+'</strong><small>'+esc(c.empresa||'Cliente CRM')+'</small><small>'+esc(c.correo)+'</small></button>').join('')||'<p class="empty">No hay clientes activos con esa búsqueda. Registra uno desde Nuevo cliente CRM.</p>';
        pager('#client-pages',r,'clients');
    }
    function selectClient(c) {
        state.client=c;clearKey();
        $('#selected-client').hidden=false;$('#selected-client').innerHTML='<p class="eyebrow">CLIENTE SELECCIONADO</p>'+clientCard(c);
        $$('[data-client]').forEach(b=>b.setAttribute('aria-pressed',String(Number(b.dataset.client)===c.id)));
    }
    async function catalog(page=1) {
        if(!can('erp.write'))return;
        const rev=++state.catalogRevision;
        const query=new URLSearchParams(new FormData($('#product-search')));query.set('page',page);
        const r=await HF.request('/productos?'+query);if(rev!==state.catalogRevision)return;state.catalog=r.data;
        for(const p of r.data) {
            const line=state.cart.get(p.id);
            if(line){const cents=Math.round(p.precio_venta*100);if(line.price!==cents)clearKey();line.price=cents;line.stock=p.stock_actual;}
        }
        $('#catalog-rows').innerHTML=r.data.map(p=>'<tr><td><strong>'+esc(p.nombre)+'</strong><small>'+esc(p.categoria)+' · '+esc(p.estrategia_logistica)+'</small></td><td><span class="badge '+(p.stock_actual<=p.stock_minimo?'badge-pendiente':'')+'">'+p.stock_actual+'</span></td><td>'+money(p.precio_venta)+'</td><td><input type="number" data-quantity="'+p.id+'" min="1" max="'+Math.max(1,p.stock_actual)+'" step="1" value="1" aria-label="Cantidad de '+esc(p.nombre)+'" '+(p.stock_actual<1?'disabled':'')+'></td><td><button type="button" data-add="'+p.id+'" '+(p.stock_actual<1?'disabled':'')+'>＋ Agregar</button></td></tr>').join('')||'<tr><td colspan="5">No hay productos disponibles con esa búsqueda.</td></tr>';
        pager('#catalog-pages',r,'catalog');renderCart();
    }
    function itemTable(editable) {
        if(!state.cart.size)return '<p class="empty">Aún no has agregado productos.</p>';
        return '<div class="table-wrap"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th>'+(editable?'<th>Acción</th>':'')+'</tr></thead><tbody>'+[...state.cart.values()].map(p=>'<tr><td>'+esc(p.name)+'</td><td>'+(editable?'<input type="number" data-cart-quantity="'+p.id+'" value="'+p.quantity+'" min="1" max="'+p.stock+'" step="1" aria-label="Cantidad en orden de '+esc(p.name)+'">':p.quantity)+'</td><td>'+money(p.price/100)+'</td><td>'+money(p.price*p.quantity/100)+'</td>'+(editable?'<td><button type="button" data-remove="'+p.id+'">Quitar</button></td>':'')+'</tr>').join('')+'</tbody></table></div>';
    }
    function total() {return [...state.cart.values()].reduce((s,p)=>s+p.price*p.quantity,0)/100;}
    function renderCart() {
        if(!$('#cart-preview'))return;
        $('#cart-preview').innerHTML=itemTable(true)+'<div class="total-box"><span>Total MXN</span><strong>'+money(total())+'</strong></div>';
        $('#review-items').innerHTML=itemTable(false);$('#order-total').textContent=money(total());
    }
    function step(number) {
        if(number>1 && !state.client)throw Error('Selecciona un cliente activo.');
        if(number>2 && !state.cart.size)throw Error('Agrega al menos un producto.');
        state.step=number;$$('[data-step]').forEach(el=>el.hidden=Number(el.dataset.step)!==number);
        $$('[data-step-indicator]').forEach(el=>{if(Number(el.dataset.stepIndicator)===number)el.setAttribute('aria-current','step');else el.removeAttribute('aria-current');});
        if(number===3){$('#review-client').innerHTML=clientCard(state.client);renderCart();}
        $('#notice').hidden=true;
    }
    function resetWizard() {
        state.client=null;state.cart.clear();state.created=null;clearKey();
        if($('#selected-client')){$('#selected-client').hidden=true;$('#order-notes').value='';$('#process-status').textContent='';step(1);renderCart();}
    }
    async function submitOrder(button, process) {
        if(state.saving)return;
        if(!state.client || !state.cart.size)throw Error('Selecciona cliente y productos.');
        state.saving=true;button.textContent=process?'Procesando…':'Guardando…';
        $('#process-status').textContent=process?'Validando existencias y registrando inventario e historial…':'Guardando la orden pendiente…';
        state.key ||= HF.uuid();
        const body={cliente_id:state.client.id,request_key:state.key,notas:$('#order-notes').value,
            productos:[...state.cart.values()].map(p=>({producto_id:p.id,cantidad:p.quantity,precio_unitario_cents:p.price}))};
        try {
            const o=await HF.request(process?'/ordenes/procesar':'/ordenes','POST',body);state.created=o;
            $('#success-title').textContent=process?'Orden procesada con éxito.':'Orden pendiente registrada.';
            $('#success-summary').innerHTML='<h2>'+esc(o.folio)+'</h2><p>'+esc(o.cliente.nombre)+'<br>'+esc(date(o.fecha))+'</p><div class="total-box"><span>Total MXN</span><strong>'+money(o.total)+'</strong></div>';
            $('#process-steps').innerHTML=process?o.procesos.flatMap(p=>p.pasos).map(s=>'<p>✓ '+esc(s)+'</p>').join(''):'<p>✓ Cotización registrada. El inventario se descontará al procesar.</p>';
            $('#process-status').textContent='';await panel('success');
        } catch(e) {$('#process-status').textContent='La operación no se confirmó. Revisa el mensaje y conserva los datos para reintentar.';throw e;}
        finally {state.saving=false;}
    }
    async function detail(id) {
        const o=await HF.request('/ordenes/'+id);
        $('#order-title').textContent=o.folio+' · '+o.estado;
        $('#order-detail').innerHTML='<div class="grid-main"><article><p class="eyebrow">CLIENTE CRM</p>'+clientCard(o.cliente)+'</article><article><p class="eyebrow">OPERACIÓN</p><p>Responsable: '+esc(o.usuario.name)+'<br>Fecha: '+esc(date(o.fecha))+'<br>Procesada: '+esc(date(o.procesada_at))+'</p><strong>Total: '+money(o.total)+'</strong></article></div><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>'+o.productos.map(p=>'<tr><td>'+esc(p.nombre)+'</td><td>'+p.cantidad+'</td><td>'+money(p.precio_unitario)+'</td><td>'+money(p.subtotal)+'</td></tr>').join('')+'</tbody></table></div><p>'+esc(o.notas||'Sin observaciones.')+'</p><h3>Historial de la orden</h3>'+o.historial.map(h=>'<div class="list-row"><div><strong>'+esc(h.evento)+'</strong><small>'+esc(h.descripcion)+'</small><small>'+esc(h.usuario.name)+' · '+esc(date(h.fecha))+'</small></div></div>').join('')+'<h3>Movimientos de inventario SCM</h3>'+o.movimientos.map(m=>'<div class="list-row"><span>'+esc(m.referencia)+'</span><span>'+esc(m.tipo)+' · '+m.cantidad+' unidades<small>Stock: '+m.stock_anterior+' → '+m.stock_resultante+'</small></span></div>').join('')+(o.movimientos.length?'':'<p class="hint">La orden pendiente no ha modificado inventario.</p>');
        showDialog('#order-dialog');
    }
    async function reports(page=1) {
        state.reportPage=page;
        const [m,r]=await Promise.all([HF.request('/erp/metricas'),HF.request('/erp/reportes?page='+page)]);
        $('#report-kpis').innerHTML=kpis(m);state.reports=r.data;
        $('#report-rows').innerHTML=r.data.map(s=>'<tr><td>#'+s.id+'</td><td>'+esc(date(s.created_at))+'</td><td>'+esc(s.usuario.name)+'</td><td>'+money(s.resumen.ventas_totales)+'</td><td>'+s.resumen.ordenes_procesadas+'</td><td><button type="button" data-snapshot="'+s.id+'">Ver corte</button><a class="button" href="/erp/reportes/'+s.id+'/csv">CSV</a></td></tr>').join('')||'<tr><td colspan="6">Guarda un corte para conservar los indicadores de este momento.</td></tr>';
        pager('#report-pages',r,'reports');
    }
    async function resources() {
        state.resources=await HF.request('/erp/recursos');
        $('#resource-rows').innerHTML=state.resources.map(r=>'<tr><td><strong>'+esc(r.nombre)+'</strong><small>'+esc(r.descripcion||'')+'</small></td><td>'+esc(r.tipo)+'</td><td>'+esc(r.responsable?.name||'Sin asignar')+'</td><td><span class="badge '+(r.disponible?'':'badge-pendiente')+'">'+(r.disponible?'Disponible':'No disponible')+'</span></td><td>'+(can('erp.resources')?'<button type="button" data-resource="'+r.id+'">Editar</button>':'Consulta')+'</td></tr>').join('')||'<tr><td colspan="5">Registra los recursos internos de la empresa.</td></tr>';
    }
    async function resourceEditor(id) {
        const form=$('#resource-form');form.reset();form.elements.id.value=id||'';
        const owners=await HF.request('/erp/responsables');
        form.elements.responsable_id.innerHTML='<option value="">Sin asignar</option>'+owners.map(u=>'<option value="'+u.id+'">'+esc(u.name)+'</option>').join('');
        if(id){const r=state.resources.find(x=>x.id===id);for(const key of ['nombre','tipo','descripcion','responsable_id'])form.elements[key].value=r[key]??'';form.elements.disponible.checked=r.disponible;}
        showDialog('#resource-dialog');
    }
    async function maturity() {
        const m=await HF.request('/erp/estado');
        $('#erp-levels').innerHTML=m.niveles.map(n=>'<span class="'+(n===m.nivel?'active':'')+'">'+esc(n)+'</span>').join('');
        $('#level-name').textContent='Nivel actual: '+m.nivel;
        $('#level-description').textContent={Básico:'La empresa comienza a centralizar sus datos.',Integrado:'CRM, SCM y ERP comparten clientes, catálogo y operaciones.',Automatizado:'Las órdenes generan inventario e historial de forma automática.',Optimizado:'El equipo verifica procesos completos y conserva reportes para tomar decisiones.'}[m.nivel];
        $('#erp-progress').value=m.progreso;$('#progress-text').textContent=m.progreso+'% de evidencias operativas disponibles.';
        $('#evidence-list').innerHTML=Object.entries(evidenceLabels).map(([key,label])=>'<label class="check"><input type="checkbox" name="'+key+'" '+(m.checklist[key]?'checked ':'')+(!can('erp.admin')||!m.evidencias[key]?'disabled ':'')+'><span>'+label+'<small>'+(m.evidencias[key]?'Evidencia registrada en el sistema':'Pendiente de registrar una operación real')+'</small></span></label>').join('');
        if($('#maturity-form').elements.nivel)$('#maturity-form').elements.nivel.value=m.nivel;
    }
    async function users() {
        state.users=await HF.request('/usuarios');
        $('#user-rows').innerHTML=state.users.map(u=>'<tr><td>'+esc(u.name)+'</td><td>'+esc(u.email)+'</td><td>'+esc(roles[u.role]||u.role)+'</td><td><select data-role-for="'+u.id+'" aria-label="Rol de '+esc(u.name)+'" '+(u.id===Number(document.body.dataset.userId)?'disabled':'')+'>'+Object.entries(roles).map(([key,label])=>'<option value="'+key+'" '+(key===u.role?'selected':'')+'>'+label+'</option>').join('')+'</select></td><td><button type="button" data-role-user="'+u.id+'" '+(u.id===Number(document.body.dataset.userId)?'disabled':'')+'>Guardar rol</button></td></tr>').join('');
    }
    async function panel(id) {
        if(!document.getElementById(id)?.classList.contains('panel')) id='dashboard';
        $$('.panel').forEach(el=>el.hidden=el.id!==id);
        $$('[data-panel]').forEach(b=>{if(b.dataset.panel===id)b.setAttribute('aria-current','page');else b.removeAttribute('aria-current');});
        history.replaceState(null,'','#'+(id==='success'?'orders':id));
        if(id==='dashboard')await dashboard();
        if(id==='orders')await orders(1);
        if(id==='new'){step(state.step);await Promise.all([clients(),catalog()]);}
        if(id==='reports')await reports(1);
        if(id==='resources')await resources();
        if(id==='maturity')await maturity();
        if(id==='users' && can('users'))await users();
    }
    document.addEventListener('click', e=>{
        const b=e.target.closest('button');if(!b || b.disabled || (b.form&&b.type==='submit'))return;
        run(b,async()=>{
            if(state.saving && !['confirm-order','save-draft'].includes(b.id)) throw Error('La orden se está procesando. Espera la confirmación.');
            if(b.dataset.close){$('#'+b.dataset.close).close();return;}
            if(b.dataset.panel){await panel(b.dataset.panel);return;}
            if(b.dataset.page){const n=Number(b.dataset.number);if(b.dataset.page==='orders')await orders(n);if(b.dataset.page==='clients')await clients(n);if(b.dataset.page==='catalog')await catalog(n);if(b.dataset.page==='reports')await reports(n);return;}
            if(b.dataset.client){selectClient(state.clients.find(c=>c.id===Number(b.dataset.client)));return;}
            if(b.dataset.add){const p=state.catalog.find(p=>p.id===Number(b.dataset.add)),input=$('[data-quantity="'+p.id+'"]');const count=Number(input.value);if(!input.reportValidity()||!Number.isInteger(count)||count<1)throw Error('Escribe una cantidad válida.');const line=state.cart.get(p.id),quantity=(line?.quantity||0)+count;if(quantity>p.stock_actual)throw Error('La cantidad total supera las existencias disponibles.');state.cart.set(p.id,{id:p.id,name:p.nombre,price:Math.round(p.precio_venta*100),stock:p.stock_actual,quantity});clearKey();renderCart();return;}
            if(b.dataset.remove){state.cart.delete(Number(b.dataset.remove));clearKey();renderCart();return;}
            if(b.dataset.view){await detail(Number(b.dataset.view));return;}
            if(b.dataset.process){if(!confirm('¿Procesar esta orden y descontar inventario?'))return;await HF.request('/ordenes/procesar','POST',{orden_id:Number(b.dataset.process)});await orders(state.orderPage);notice('Orden procesada. Inventario e historial actualizados.');return;}
            if(b.dataset.state){if(!confirm(b.dataset.state==='cancelada'?'¿Cancelar la orden? Si fue procesada, se devolverán sus existencias.':'¿Confirmar que esta orden fue entregada?'))return;await HF.request('/ordenes/'+b.dataset.order+'/estado','PUT',{estado:b.dataset.state});await orders(state.orderPage);notice('Estado actualizado.');return;}
            if(b.dataset.resource){await resourceEditor(Number(b.dataset.resource));return;}
            if(b.dataset.roleUser){const id=Number(b.dataset.roleUser);await HF.request('/usuarios/rol','PUT',{usuario_id:id,role:$('[data-role-for="'+id+'"]').value});await users();notice('Rol actualizado. Los permisos se aplican a las siguientes solicitudes.');return;}
            if(b.dataset.snapshot){const s=state.reports.find(r=>r.id===Number(b.dataset.snapshot));$('#snapshot-title').textContent='Corte #'+s.id+' · '+date(s.created_at);$('#snapshot-detail').innerHTML='<div class="kpis">'+kpis(s.resumen)+'</div><p>Responsable: '+esc(s.usuario.name)+'</p><a class="button primary" href="/erp/reportes/'+s.id+'/csv">Descargar este corte CSV</a>';showDialog('#snapshot-dialog');return;}
            switch(b.id){
                case 'logout':await HF.request('/logout','POST',{});location.href='/';break;
                case 'refresh':await dashboard();notice('Indicadores actualizados.');break;
                case 'clear-order-filter':$('#order-filter').reset();await orders(1);break;
                case 'new-client':$('#client-form').reset();showDialog('#client-dialog');break;
                case 'client-next':step(2);break;
                case 'product-back':step(1);break;
                case 'product-next':step(3);break;
                case 'review-back':step(2);break;
                case 'confirm-order':await submitOrder(b,true);break;
                case 'save-draft':await submitOrder(b,false);break;
                case 'cancel-wizard':if(confirm('¿Descartar los datos de esta nueva orden?')){resetWizard();await panel('orders');}break;
                case 'another-order':resetWizard();await panel('new');break;
                case 'view-created':await detail(state.created.id);break;
                case 'generate-report':await HF.request('/erp/reportes','POST',{});await reports(1);notice('Corte empresarial guardado.');break;
                case 'new-resource':await resourceEditor();break;
                case 'new-user':$('#user-form').reset();showDialog('#user-dialog');break;
                case 'print-order':window.print();break;
            }
        });
    });
    document.addEventListener('change',e=>{
        if(e.target.matches('[data-cart-quantity]'))run(null,async()=>{const p=state.cart.get(Number(e.target.dataset.cartQuantity));const n=Number(e.target.value);if(!Number.isInteger(n)||n<1||n>p.stock){e.target.value=p.quantity;throw Error('La cantidad debe estar entre 1 y el stock disponible.');}p.quantity=n;clearKey();renderCart();});
        if(e.target.id==='order-notes')clearKey();
    });
    document.addEventListener('invalid',e=>notice(e.target.validationMessage,true),true);
    document.addEventListener('submit',e=>{
        const form=e.target;e.preventDefault();run(e.submitter,async()=>{
            if(form.id==='order-filter')await orders(1);
            else if(form.id==='client-search')await clients(1);
            else if(form.id==='product-search')await catalog(1);
            else if(form.id==='client-form'){const c=await HF.request('/clientes','POST',Object.fromEntries(new FormData(form)));selectClient(c);$('#client-dialog').close();await clients();notice('Cliente guardado en CRM y seleccionado.');}
            else if(form.id==='resource-form'){const d=Object.fromEntries(new FormData(form)),id=d.id;delete d.id;d.disponible=form.elements.disponible.checked;d.responsable_id=d.responsable_id?Number(d.responsable_id):null;await HF.request(id?'/erp/recursos/'+id:'/erp/recursos',id?'PUT':'POST',d);$('#resource-dialog').close();await resources();notice('Recurso guardado.');}
            else if(form.id==='user-form'){await HF.request('/usuarios','POST',Object.fromEntries(new FormData(form)));$('#user-dialog').close();form.reset();await users();notice('Cuenta del equipo creada.');}
            else if(form.id==='maturity-form'){const checklist={};Object.keys(evidenceLabels).forEach(k=>checklist[k]=form.elements[k].checked);await HF.request('/erp/estado','PUT',{nivel:form.elements.nivel.value,checklist});await maturity();notice('Nivel ERP actualizado.');}
        });
    });
    run(null,()=>panel(location.hash.slice(1)||'dashboard'));
})();
```

## public/js/http.js

```javascript
window.HF = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    uuid() {
        if (crypto.randomUUID) return crypto.randomUUID();
        const bytes = crypto.getRandomValues(new Uint8Array(16));
        bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
        const hex = [...bytes].map(b => b.toString(16).padStart(2, '0')).join('');
        return [hex.slice(0,8),hex.slice(8,12),hex.slice(12,16),hex.slice(16,20),hex.slice(20)].join('-');
    },
    async request(path, method = 'GET', data) {
        const response = await fetch(path, {
            method, credentials: 'same-origin',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf},
            ...(data === undefined ? {} : {body: JSON.stringify(data)})
        });
        let json = null;
        if (response.status !== 204) {
            try {json = await response.json();}
            catch {
                if (response.ok) throw new Error('El servidor devolvió una respuesta inesperada. Comprueba la lista antes de volver a guardar.');
                json = {};
            }
        }
        if (!response.ok) {
            const message = json?.errors ? Object.values(json.errors).flat().join(' ') :
                ({401:'Inicia sesión para continuar.',403:'No tienes permiso.',404:'No se encontró la dirección solicitada.',419:'La sesión venció. Recarga la página.',429:'Demasiados intentos. Espera un minuto.'}[response.status] || json?.message || 'No se pudo completar la operación.');
            const error = new Error(`HTTP ${response.status}: ${message}`); error.status = response.status; throw error;
        }
        if (json?.csrf_token) this.csrf = json.csrf_token;
        return json;
    }
};
```

## public/js/scm.js

```javascript
(() => {
    const $ = selector => document.querySelector(selector);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = n => new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'}).format(n);
    const date = v => v ? new Date(v).toLocaleString('es-MX') : '—';
    const admin = document.body.dataset.role === 'admin';
    const manage = ['admin','logistica'].includes(document.body.dataset.role);
    const operate = ['admin','logistica','usuario'].includes(document.body.dataset.role);
    const state = {suppliers:[], products:[], productPage:1, orderPage:1, historyPage:1, selected:null, productRevision:0, orderRevision:0};
    const keys = new WeakMap();
    let noticeTimer;
    function notice(text, error = false) {
        document.querySelectorAll('.dialog-notice').forEach(n => n.hidden = true);
        const dialog = document.querySelector('dialog[open]');
        let el = $('#notice');
        if (dialog) {
            el = dialog.querySelector('.dialog-notice');
            if (!el) {
                el = document.createElement('p');
                el.className = 'dialog-notice';
                el.setAttribute('role', 'alert');
                const form = dialog.querySelector('form');
                if (form) form.before(el); else dialog.append(el);
            }
            el.style.cssText = error ? 'color:#b00020;font-weight:600;margin:0 0 12px' : 'margin:0 0 12px';
        }
        el.textContent = text; el.classList.toggle('error', error); el.hidden = false;
        clearTimeout(noticeTimer); noticeTimer = setTimeout(() => el.hidden = true, error ? 12000 : 6000);
    }
    async function action(element, fn) {
        if (element?.dataset.busy) return;
        const submitting = element?.form && element.type === 'submit';
        const label = element?.innerHTML;
        if (element) {element.dataset.busy = '1'; element.disabled = true;}
        if (submitting) element.textContent = 'Procesando…';
        try {await fn();} catch (e) {console.error(e); notice(e.message || 'No se pudo completar la operación.', true);} finally {if (element) {delete element.dataset.busy; element.disabled = false;} if (submitting) element.innerHTML = label;}
    }
    function tab(id) {
        document.querySelectorAll('.panel').forEach(el => el.hidden = el.id !== id);
        document.querySelectorAll('[data-tab]').forEach(el => {if (el.dataset.tab === id) el.setAttribute('aria-current','page'); else el.removeAttribute('aria-current');});
    }
    function open(id) {$(id).showModal();}
    function close(id) {$(id).close();}
    function field(form, name) {return form.elements.namedItem(name);}
    function values(form) {return Object.fromEntries(new FormData(form));}
    function requestKey(form) {if (!keys.has(form)) keys.set(form, HF.uuid()); return keys.get(form);}
    function pagination(selector, data, kind) {
        $(selector).innerHTML = `<button data-page="${kind}" data-number="${data.current_page-1}" ${data.current_page<=1?'disabled':''}>Anterior</button><span>${data.total} registros · ${data.current_page}/${data.last_page}</span><button data-page="${kind}" data-number="${data.current_page+1}" ${data.current_page>=data.last_page?'disabled':''}>Siguiente</button>`;
    }
    async function loadProducts(page = state.productPage) {
        state.productPage = page; const revision = ++state.productRevision;
        const query = new URLSearchParams(values($('#product-filter'))); query.set('page', page);
        const data = await HF.request('/productos?'+query);
        if (revision !== state.productRevision) return;
        state.products = data.data;
        $('#product-rows').innerHTML = data.data.map(p => `<tr><td><strong>${esc(p.nombre)}</strong><small>${esc(p.categoria)} · ${esc(p.proveedor||'Sin proveedor')}</small><small>${esc(p.ubicacion)}${p.activo?'':' · Oculto en tienda'}</small></td><td><span class="badge ${p.stock_bajo?'critical':''}">${p.stock_actual} ${p.stock_bajo?'· CRÍTICO':''}</span></td><td>${p.stock_minimo} / ${p.stock_objetivo}</td><td>${money(p.costo_unitario)}<small>Venta: ${money(p.precio_venta)}</small></td><td><span class="badge">${esc(p.estrategia_logistica)}</span></td><td><button data-history="${p.id}">Historial</button>${!p.deleted_at && manage?`<button data-edit-product="${p.id}">Editar</button><button data-strategy="${p.id}" data-value="${p.estrategia_logistica==='PUSH'?'PULL':'PUSH'}">Cambiar a ${p.estrategia_logistica==='PUSH'?'Pull':'Push'}</button><button class="danger" data-delete-product="${p.id}">Archivar</button>`:''}${p.deleted_at?'<span class="badge">Archivado</span>'+(manage?'<button data-restore-product="'+p.id+'" type="button">Restaurar</button>':''):''}</td></tr>`).join('') || '<tr><td colspan="6">Sin productos con estos filtros.</td></tr>';
        pagination('#product-pages', data, 'product');
    }
    async function loadSuppliers() {
        state.suppliers = await HF.request('/proveedores');
        $('#supplier-rows').innerHTML = state.suppliers.map(p => `<tr><td>${esc(p.nombre)}</td><td>${esc(p.contacto)}</td><td>${esc(p.correo)}</td><td>${esc(p.telefono)}</td><td>${manage?`<button data-edit-supplier="${p.id}">Editar</button><button class="danger" data-delete-supplier="${p.id}">Eliminar</button>`:'Consulta'}</td></tr>`).join('') || '<tr><td colspan="5">Registra el primer proveedor para asociarlo al catálogo.</td></tr>';
        const select = $('#product-form select[name="proveedor_id"]');
        if (select) {const chosen = select.value; select.innerHTML = '<option value="">Sin proveedor</option>'+state.suppliers.map(p=>`<option value="${p.id}">${esc(p.nombre)}</option>`).join(''); select.value = chosen;}
    }
    async function loadOrders(page = state.orderPage) {
        state.orderPage = page; const revision = ++state.orderRevision;
        const query = new URLSearchParams(values($('#order-filter'))); query.set('page', page);
        const data = await HF.request('/pedidos?'+query);
        if (revision !== state.orderRevision) return;
        $('#order-rows').innerHTML = data.data.map(p=>`<tr><td><strong>#${p.id} · ${esc(p.producto?.name)}</strong><small>Responsable: ${esc(p.usuario?.name||'Sistema')}</small></td><td>${p.cantidad}</td><td>${p.tipo==='reposicion'?'Reposición':'Venta'}<small>${p.automatico?'Push automático':'Manual'}</small></td><td><span class="badge ${p.estado==='pendiente'?'critical':''}">${esc(p.estado)}</span></td><td>${date(p.created_at)}<small>Surtido: ${date(p.fecha_surtido)}</small></td><td>${p.estado==='pendiente' && operate?`<button class="primary" data-fulfill="${p.id}">Marcar surtido</button>`:'Registrado'}</td></tr>`).join('') || '<tr><td colspan="6">Sin pedidos con estos filtros.</td></tr>';
        pagination('#order-pages', data, 'order');
    }
    function bars(rows, label, amount, suffix) {
        const max = Math.max(1, ...rows.map(amount));
        return rows.map(r=>`<div class="bar-row"><div><span>${esc(label(r))}</span><strong>${amount(r)} ${suffix}</strong></div><div class="bar-track"><div class="bar-fill" style="width:${Math.max(0,Math.min(100,amount(r)/max*100))}%"></div></div></div>`).join('') || '<p>Aún no hay ventas surtidas registradas.</p>';
    }
    async function loadReports() {
        const d = await HF.request('/scm/reportes');
        $('#counters').innerHTML = [['Productos',d.total_productos],['Unidades disponibles',d.unidades_stock],['Valor a costo',money(d.valor_inventario_cents/100)],['Pedidos pendientes',d.pedidos_pendientes]].map(([label,value])=>`<div class="card"><small>${label}</small><strong>${esc(value)}</strong></div>`).join('');
        $('#top-chart').innerHTML = bars(d.productos_mas_vendidos,r=>r.nombre,r=>r.unidades,'u.');
        $('#strategy-chart').innerHTML = bars(d.comparacion,r=>`${r.estrategia}: ${r.productos} productos · ${r.criticos} críticos`,r=>r.stock,'u.');
        $('#critical').innerHTML = d.inventario_critico.map(p=>`<div class="list-row"><div><strong>${esc(p.nombre)}</strong><small> · ${esc(p.estrategia_logistica)}</small></div><button data-history="${p.id}">${p.stock_actual} / mín. ${p.stock_minimo}</button></div>`).join('') || '<p>No hay productos en nivel crítico.</p>';
        $('#slow').innerHTML = d.rotacion_lenta.map(p=>`<div class="list-row"><span>${esc(p.nombre)}<small> · stock ${p.stock_actual}</small></span><strong>${p.unidades_30_dias} ventas</strong></div>`).join('') || '<p>No hay productos con rotación lenta.</p>';
    }
    const descriptions = {'Inicial':'Se registra el catálogo y se establecen controles básicos de proveedores e inventario.','En desarrollo':'Se aplican movimientos trazables, pedidos y estrategias. El equipo revisa al menos tres evidencias.','Optimizado':'Las seis evidencias están verificadas y el equipo revisa reportes para ajustar decisiones logísticas.'};
    async function loadMaturity() {
        const data = await HF.request('/scm/estado'); const form = $('#maturity-form');
        field(form,'nivel_scm').value = data.nivel_scm;
        Object.entries(data.checklist).forEach(([k,v])=>field(form,k).checked = Boolean(v));
        if (!admin) [...form.elements].forEach(el=>el.disabled = true);
        $('#level-indicator').textContent = data.nivel_scm; $('#level-description').textContent = descriptions[data.nivel_scm];
    }
    async function refresh() {await Promise.all([loadProducts(),loadSuppliers(),loadOrders(),loadReports(),loadMaturity()]);}
    async function refreshInventory() {await Promise.all([loadProducts(),loadOrders(),loadReports()]);}
    async function productEditor(id) {
        const form = $('#product-form'); form.reset();
        const p = id ? await HF.request('/productos/'+id) : {id:'',nombre:'',descripcion:'',categoria:'Hoodie',proveedor_id:'',stock_actual:0,stock_minimo:0,stock_objetivo:10,costo_unitario:0,precio_venta:1,estrategia_logistica:'PULL',ubicacion:'Almacén principal',imagen:'',color:'',activo:true};
        Object.entries(p).forEach(([k,v])=>{const el = field(form,k);if(el) {if(el.type==='checkbox')el.checked=Boolean(v);else el.value=v??'';}});
        field(form,'stock_actual').disabled = Boolean(id); $('#product-title').textContent = id?'Editar producto':'Nuevo producto'; open('#product-dialog');
    }
    function supplierEditor(id) {
        const form = $('#supplier-form'); form.reset(); field(form,'id').value = id||'';
        if (id) Object.entries(state.suppliers.find(p=>p.id===id)).forEach(([k,v])=>{if(field(form,k))field(form,k).value=v;});
        open('#supplier-dialog');
    }
    async function loadHistory(page = 1) {
        state.historyPage = page;
        const [p,data] = await Promise.all([HF.request('/productos/'+state.selected),HF.request(`/productos/${state.selected}/movimientos?page=${page}`)]);
        $('#history-title').textContent = 'Movimientos · '+p.nombre; $('#history-stock').textContent = `Stock disponible: ${p.stock_actual} · mínimo: ${p.stock_minimo} · ${p.estrategia_logistica}${p.deleted_at?' · Archivado':''}`;
        const form = $('#movement-form'); form.hidden = Boolean(p.deleted_at) || !operate; $('#movement-hint').hidden = Boolean(p.deleted_at); field(form,'producto_id').value = p.id;
        $('#history-rows').innerHTML = data.data.map(m=>`<tr><td>${date(m.fecha)}<small>${esc(m.usuario?.name||'Sistema')}</small></td><td>${esc(m.tipo)}<small>${esc(m.motivo)}</small></td><td>${m.cantidad}</td><td>${m.stock_anterior} → ${m.stock_resultante}</td><td>${esc(m.referencia||'—')}${m.pedido_id?`<small>Pedido SCM #${m.pedido_id}</small>`:''}</td></tr>`).join('') || '<tr><td colspan="5">Sin movimientos registrados.</td></tr>';
        pagination('#history-pages', data, 'history');
    }
    async function findOrderProducts() {
        const data = await HF.request('/productos?q='+encodeURIComponent($('#order-search').value));
        field($('#order-form'),'producto_id').innerHTML = data.data.map(p=>`<option value="${p.id}">${esc(p.nombre)} · ${p.estrategia_logistica} · stock ${p.stock_actual}</option>`).join('') || '<option value="">Sin resultados</option>';
    }
    document.addEventListener('click', e => {
        const b = e.target.closest('button'); if (!b || (b.form && b.type === 'submit')) return;
        if (b.dataset.tab) {tab(b.dataset.tab); return;}
        if (b.dataset.close) {close('#'+b.dataset.close); return;}
        action(b, async()=>{
            if (b.id==='logout') {await HF.request('/logout','POST',{}); location.href='/';}
            else if (b.id==='refresh') {await refresh(); notice('Datos actualizados.');}
            else if (b.id==='new-product') await productEditor();
            else if (b.dataset.editProduct) await productEditor(Number(b.dataset.editProduct));
            else if (b.dataset.deleteProduct) {if(!confirm('¿Archivar este producto? Su historial se conserva. Requiere stock cero y ningún pedido pendiente.'))return; await HF.request('/productos/'+b.dataset.deleteProduct,'DELETE'); await refreshInventory(); notice('Producto archivado.');}
            else if (b.dataset.restoreProduct) {await HF.request('/productos/'+b.dataset.restoreProduct+'/restaurar','PUT',{});await refreshInventory();notice('Producto restaurado.');}
            else if (b.dataset.strategy) {await HF.request(`/productos/${b.dataset.strategy}/estrategia`,'PUT',{estrategia_logistica:b.dataset.value}); await refreshInventory(); notice('Estrategia actualizada. Revisa los pedidos pendientes.');}
            else if (b.id==='new-supplier') supplierEditor();
            else if (b.dataset.editSupplier) supplierEditor(Number(b.dataset.editSupplier));
            else if (b.dataset.deleteSupplier) {if(!confirm('¿Eliminar proveedor? Solo se permite si no está asociado a productos.'))return;await HF.request('/proveedores/'+b.dataset.deleteSupplier,'DELETE');await loadSuppliers();notice('Proveedor eliminado.');}
            else if (b.dataset.history) {state.selected = Number(b.dataset.history); const form=$('#movement-form');form.reset();keys.delete(form);await loadHistory();open('#history-dialog');}
            else if (b.id==='new-order') {const form=$('#order-form');form.reset();keys.delete(form);await findOrderProducts();open('#order-dialog');}
            else if (b.id==='order-search-button') await findOrderProducts();
            else if (b.dataset.fulfill) {if(!confirm('¿Confirmas que este pedido fue surtido físicamente? Esta acción registra el movimiento de inventario.'))return;await HF.request(`/pedidos/${b.dataset.fulfill}/estado`,'PUT',{estado:'surtido'});await refreshInventory();notice('Pedido surtido e inventario actualizado.');}
            else if (b.dataset.page) {const page=Number(b.dataset.number);if(b.dataset.page==='product')await loadProducts(page);if(b.dataset.page==='order')await loadOrders(page);if(b.dataset.page==='history')await loadHistory(page);}
        });
    });
    document.addEventListener('invalid', e => notice(e.target.validationMessage, true), true);
    document.addEventListener('submit', e => {
        const form = e.target;
        e.preventDefault();
        // IMPORTANTE: los formularios con <input name="id"> tapan la propiedad form.id,
        // por eso se lee el atributo con getAttribute.
        const formId = form.getAttribute('id');
        const b = e.submitter || form.querySelector('button[type="submit"], button.primary, button:not([type]), button');
        action(b, async()=>{
            const d = values(form);
            if(formId==='product-filter') await loadProducts(1);
            else if(formId==='order-filter') await loadOrders(1);
            else if(formId==='product-form') {
                const id=d.id; delete d.id; d.activo=field(form,'activo').checked; d.proveedor_id=d.proveedor_id?Number(d.proveedor_id):null;
                ['stock_actual','stock_minimo','stock_objetivo','costo_unitario','precio_venta'].forEach(k=>{if(k in d)d[k]=Number(d[k]);});
                await HF.request(id?'/productos/'+id:'/productos',id?'PUT':'POST',d);close('#product-dialog');await refreshInventory();notice('Producto guardado.');
            } else if(formId==='supplier-form') {
                const id=d.id; delete d.id;
                await HF.request(id?'/proveedores/'+id:'/proveedores',id?'PUT':'POST',d);
                close('#supplier-dialog');
                await Promise.all([loadSuppliers(),loadProducts()]);
                notice('Proveedor guardado.');
            }
            else if(formId==='movement-form') {d.producto_id=Number(d.producto_id);d.cantidad=Number(d.cantidad);d.request_key=requestKey(form);await HF.request('/inventario/movimiento','POST',d);keys.delete(form);field(form,'cantidad').value='';field(form,'referencia').value='';await Promise.all([loadHistory(1),refreshInventory()]);notice('Movimiento registrado.');}
            else if(formId==='order-form') {d.producto_id=Number(d.producto_id);d.cantidad=Number(d.cantidad);d.request_key=requestKey(form);await HF.request('/pedidos','POST',d);keys.delete(form);close('#order-dialog');await Promise.all([loadOrders(1),loadReports()]);notice('Pedido generado. Recíbelo desde la lista cuando esté surtido.');}
            else if(formId==='maturity-form' && admin) {const checklist={};['catalogo','proveedores','inventario','estrategias','pedidos','reportes'].forEach(k=>checklist[k]=field(form,k).checked);await HF.request('/scm/nivel','PUT',{nivel_scm:d.nivel_scm,checklist});await loadMaturity();notice('Nivel SCM guardado.');}
        });
    });
    // Una edición del formulario inicia una nueva operación; un reintento sin cambios conserva la clave.
    ['movement-form','order-form'].forEach(id=>$('#'+id).addEventListener('input',e=>keys.delete(e.currentTarget)));
    action(null, refresh);
})();
```

## public/js/storefront.js

```javascript
document.addEventListener('alpine:init', () => {
            Alpine.data('app', () => ({
                currentRoute: 'inicio',
                searchQuery: '',
                toast: { show: false, message: '' },
                mobileMenuOpen: false,
                landingEmail: '', newsletterEmail: '',
                contactForm: { name: '', email: '', message: '' },
                
                get isAnyModalOpen() {
                    return this.cartOpen || this.checkoutOpen || this.productDetailOpen || 
                           this.orderSuccessOpen || this.invoiceModalOpen || this.editModalOpen || 
                           this.mobileMenuOpen || this.productEditorOpen;
                },
                
                allProducts: [],
                selectedCategory: 'All', selectedProduct: {}, productDetailOpen: false,
                
                get filteredProducts() {
                    let p = this.allProducts.filter(p => p.active);
                    if (this.searchQuery.trim()) {
                        const q = this.searchQuery.toLowerCase().trim();
                        p = p.filter(x => (x.name || '').toLowerCase().includes(q) || (x.category || '').toLowerCase().includes(q) || (x.color || '').toLowerCase().includes(q));
                    } else {
                        if (this.selectedCategory === 'Promociones') p = p.filter(x => x.badge && x.badge.includes('PROMO'));
                        else if (this.selectedCategory !== 'All') p = p.filter(x => x.category === this.selectedCategory);
                    }
                    return p;
                },
                
                cart: [], cartOpen: false, discountCode: '', discountApplied: false,
                
                checkoutOpen: false, checkoutStep: 1, paymentMethod: 'tarjeta', checkoutForm: { address: '', city: '', zip: '' },
                isCheckingOut: false, orderSuccessOpen: false, orderNumber: '', lastPurchaseSummary: [],

                get cartTotalItems() { return this.cart.reduce((acc, item) => acc + (Number(item.quantity) || 1), 0); },
                get cartCount() { return this.cart.length; },
                get cartTotalSinDescuento() { return this.cart.reduce((t, item) => t + ((Number(item.price) || 0) * (Number(item.quantity) || 1)), 0); },
                get cartTotal() { return this.discountApplied ? this.cartTotalSinDescuento * 0.9 : this.cartTotalSinDescuento; },
                
                userOrders: [],
                invoiceModalOpen: false, selectedOrder: null,

                isLoggedIn: false, isAdmin: false, loginRole: 'user', isLoggingIn: false,
                user: { name: '', email: '' }, registroForm: { name: '', email: '', password: '' }, loginForm: { email: '', password: '' },

                userProducts: [],
                publishForm: { name: '', price: '', description: '', image: '' },
                editModalOpen: false, editingIndex: -1, editForm: { name: '', price: '', description: '', image: '' },
                
                newComment: '', comments: [],

                auction: {timerSeconds:0, product:{name:'',image:'',currentBid:0},offers:[],newOffer:''},
                adminTab: 'pedidos', adminStats:{sales:'$0',orders:0,users:0},
                adminOrdersList: [], adminInvoicesList: [], adminUsersList: [],
                checkoutKey: '', isSaving: false, ready:false,
                productEditorOpen:false, productEditingId:null,
                productForm:{name:'',color:'',category:'Hoodie',price:'',originalPrice:'',stock:0,image:'',badge:'',description:'',active:true},
                get isStaff() {return ['admin','usuario','ventas','logistica'].includes(this.user.role);},
                can(permission) {return (this.user.permissions || []).includes(permission);},
                get formattedTimer() {
                    const h = Math.floor(this.auction.timerSeconds / 3600).toString().padStart(2, '0');
                    const m = Math.floor((this.auction.timerSeconds % 3600) / 60).toString().padStart(2, '0');
                    const s = (this.auction.timerSeconds % 60).toString().padStart(2, '0');
                    return `${h}:${m}:${s}`;
                },

                async init() {
                    try {
                        const savedCart=JSON.parse(localStorage.getItem('hfstudios_cart')||'[]');
                        this.cart=Array.isArray(savedCart)?savedCart.filter(i=>Number.isInteger(i.id)&&Number.isInteger(i.quantity)&&i.quantity>0&&i.quantity<=99).map(i=>({...i,cartItemId:i.id})):[];
                    } catch {this.cart=[];}
                    localStorage.removeItem('hfstudios_user');
                    this.$watch('cart',value=>{try{localStorage.setItem('hfstudios_cart',JSON.stringify(value));}catch{}});
                    try {
                        const session=await HF.request('/sesion'); this.setSession(session.user);
                        await this.refreshPublic(); if(this.isLoggedIn) await this.refreshPrivate();
                        if(new URLSearchParams(location.search).get('vista')==='login') this.currentRoute='login';
                    } catch(e) {this.showToast(e.message);} finally {this.ready=true;}
                    this.timer=setInterval(()=>{if(this.auction.timerSeconds>0)this.auction.timerSeconds--;},1000);
                },
                destroy(){clearInterval(this.timer);},
                setSession(user){this.user=user||{name:'',email:''};this.isLoggedIn=!!user;this.isAdmin=user?.role==='admin';},
                async refreshPublic(){
                    [this.allProducts,this.comments]=await Promise.all([HF.request('/tienda/productos'),HF.request('/tienda/comentarios')]);
                    const auction=await HF.request('/tienda/subasta');this.auction={...auction,newOffer:this.auction.newOffer||''};
                    this.cart=this.cart.filter(i=>this.allProducts.some(p=>p.id===i.id&&p.active)).map(i=>({...i,...this.allProducts.find(p=>p.id===i.id),quantity:i.quantity,cartItemId:i.id}));
                },
                async refreshPrivate(){
                    this.userOrders=await HF.request('/tienda/pedidos');this.userProducts=await HF.request('/tienda/publicaciones');
                    if(this.isAdmin){this.adminOrdersList=this.userOrders;this.adminStats=await HF.request('/tienda/estadisticas');this.adminInvoicesList=this.userOrders.filter(o=>o.status!=='Cancelado').map(o=>({...o,orderId:o.id}));}
                },
                navigateTo(route) {
                    if(route==='admin'&&!this.isAdmin){this.showToast('Acceso solo para administradores.');return;}
                    if(['perfil','mis-publicaciones','publicar','subasta'].includes(route)&&!this.isLoggedIn){this.currentRoute='login';this.showToast('Inicia sesión para continuar.');return;}
                    if(['crm','scm','erp'].includes(route)){if(this.can({crm:'crm',scm:'scm.read',erp:'erp.read'}[route]))location.href='/'+route;return;}
                    this.currentRoute=route;window.scrollTo({top:0,behavior:'smooth'});
                },
                showToast(message) { this.toast.message = message; this.toast.show = true; setTimeout(() => { this.toast.show = false; }, 3500); },
                async subscribeNewsletter(){try{await HF.request('/tienda/suscripciones','POST',{email:this.newsletterEmail});this.newsletterEmail='';this.showToast('Suscripción guardada.');}catch(e){this.showToast(e.message);}},
                async handleLandingSubmit(){try{await HF.request('/tienda/suscripciones','POST',{email:this.landingEmail});this.showToast('Suscripción guardada.');this.landingEmail='';this.navigateTo('catalogo');}catch(e){this.showToast(e.message);}},
                viewProductDetail(product) { this.selectedProduct = product; this.productDetailOpen = true; },
                
                addToCart(product) {
                    const item=this.cart.find(i=>i.id===product.id);
                    if(!product.active||product.stock<1||(item&&item.quantity>=Math.min(product.stock,99))){this.showToast('Existencias insuficientes.');return;}
                    if(item)item.quantity++;else this.cart.push({...product,cartItemId:product.id,quantity:1});
                    this.showToast('Agregado a la bolsa');this.cartOpen=true;
                },
                increaseQty(index){const i=this.cart[index];if(i.quantity<Math.min(i.stock,99))i.quantity++;else this.showToast('Límite de existencias.');},
                decreaseQty(index){if(this.cart[index].quantity>1)this.cart[index].quantity--;else this.removeFromCart(index);},
                removeFromCart(index){this.cart.splice(index,1);},
                applyDiscount(){if(this.discountCode.trim().toUpperCase()!=='HF10'){this.showToast('Código inválido.');return;}this.discountApplied=true;this.discountCode='';this.showToast('Descuento HF10 aplicado.');},
                handleCheckout(){if(!this.isLoggedIn){this.cartOpen=false;this.navigateTo('login');this.showToast('Inicia sesión para registrar tu pedido.');return;}if(!this.cart.length)return;this.checkoutKey=HF.uuid();this.cartOpen=false;this.checkoutOpen=true;this.checkoutStep=1;},
                async processFinalPayment(){
                    if(this.isCheckingOut)return;this.isCheckingOut=true;
                    try{
                        const order=await HF.request('/tienda/pedidos','POST',{items:this.cart.map(i=>({id:i.id,quantity:i.quantity})),...this.checkoutForm,discount_code:this.discountApplied?'HF10':null,checkout_key:this.checkoutKey});
                        this.orderNumber=order.id;this.lastPurchaseSummary=order.items;this.cart=[];this.discountApplied=false;this.checkoutOpen=false;this.orderSuccessOpen=true;
                        await this.refreshPublic();await this.refreshPrivate();
                    }catch(e){this.showToast(e.message);}finally{this.isCheckingOut=false;}
                },
                openInvoice(order) { this.selectedOrder = order; this.invoiceModalOpen = true; },
                
                downloadInvoice(order=this.selectedOrder) {
                    if(!order)return;
                    try {
                        const {jsPDF}=window.jspdf;const doc=new jsPDF();
                        doc.setFont('courier','bold');doc.setFontSize(24);doc.text('HFSTUDIOS',105,20,{align:'center'});
                        doc.setFont('courier','normal');doc.setFontSize(10);doc.text('Comprobante de pedido · Sin valor fiscal',105,28,{align:'center'});
                        doc.setFontSize(11);doc.text('Cliente: '+(order.user||this.user.name),20,42);
                        doc.setFontSize(9);doc.text('Folio: '+order.id,20,51);doc.text('Fecha: '+order.date,20,59);doc.text('Estado: '+order.status,20,67);
                        doc.autoTable({startY:76,head:[['CANT.','PRODUCTO','PRECIO MXN','TOTAL MXN']],body:order.items.map(i=>[i.quantity,i.name,Number(i.price).toFixed(2),(i.price*i.quantity).toFixed(2)]),theme:'striped',styles:{font:'courier'},headStyles:{fillColor:[20,20,20]},margin:{left:20,right:20}});
                        let y=doc.lastAutoTable.finalY+16;if(y>260){doc.addPage();y=25;}
                        doc.setFontSize(10);if(order.discount)doc.text('Descuento: $'+Number(order.discount).toFixed(2)+' MXN',20,y-7);
                        doc.setFont('courier','bold');doc.setFontSize(14);doc.text('Total del pedido:',20,y);doc.text('$'+Number(order.total).toFixed(2)+' MXN',190,y,{align:'right'});
                        doc.setFont('courier','normal');doc.setFontSize(9);doc.text('Este comprobante no acredita un cobro ni sustituye una factura fiscal.',20,y+12);
                        doc.save('HFSTUDIOS_'+order.id+'.pdf');this.showToast('Comprobante descargado.');
                    } catch(e){this.showToast('No se pudo generar el comprobante: '+e.message);}
                },

                async handlePublishSubmit(){if(this.isSaving)return;this.isSaving=true;try{await HF.request('/tienda/publicaciones','POST',this.publishForm);this.publishForm={name:'',price:'',description:'',image:''};await this.refreshPrivate();this.showToast('Publicación guardada.');this.navigateTo('mis-publicaciones');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async deleteUserProduct(index){if(!confirm('¿Eliminar publicación?'))return;try{await HF.request('/tienda/publicaciones/'+this.userProducts[index].id,'DELETE');await this.refreshPrivate();this.showToast('Publicación eliminada.');}catch(e){this.showToast(e.message);}},
                openEditModal(product,index){this.editingIndex=index;this.editForm={...product};this.editModalOpen=true;},
                async saveEdit(){if(this.isSaving)return;this.isSaving=true;try{await HF.request('/tienda/publicaciones/'+this.editForm.id,'PUT',this.editForm);await this.refreshPrivate();this.editModalOpen=false;this.showToast('Publicación actualizada.');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async handleLogin(){
                    if(this.isLoggingIn)return;this.isLoggingIn=true;
                    try{const result=await HF.request('/login','POST',this.loginForm);this.setSession(result.user);this.loginForm={email:'',password:''};await this.refreshPublic();await this.refreshPrivate();this.showToast('Sesión iniciada.');if(this.isStaff)location.href=this.user.home||'/erp';else this.navigateTo('perfil');}
                    catch(e){this.showToast(e.message);}finally{this.isLoggingIn=false;}
                },
                async handleLogout(){try{await HF.request('/logout','POST',{});this.setSession(null);this.userOrders=[];this.userProducts=[];this.adminOrdersList=[];this.adminInvoicesList=[];this.adminStats={sales:'$0',orders:0,users:0};this.navigateTo('inicio');await this.refreshPublic();this.showToast('Sesión finalizada.');}catch(e){this.showToast(e.message);}},
                async handleRegistro(){if(this.isSaving)return;this.isSaving=true;try{const result=await HF.request('/registro','POST',this.registroForm);this.setSession(result.user);this.registroForm={name:'',email:'',password:''};await this.refreshPrivate();this.navigateTo('perfil');this.showToast('Cuenta creada.');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async handleContactSubmit(){try{await HF.request('/tienda/contacto','POST',this.contactForm);this.contactForm={name:'',email:'',message:''};this.showToast('Mensaje guardado para atención del equipo.');}catch(e){this.showToast(e.message);}},
                async addComment(){try{this.comments=await HF.request('/tienda/comentarios','POST',{text:this.newComment});this.newComment='';this.showToast('Reseña guardada.');}catch(e){this.showToast(e.message);}},
                async handleOfferSubmit(){try{const result=await HF.request('/tienda/subastas/'+this.auction.id+'/ofertas','POST',{amount:this.auction.newOffer});this.auction={...result,newOffer:''};this.showToast('Oferta registrada.');}catch(e){this.showToast(e.message);await this.refreshPublic().catch(()=>{});}},
                async changeOrderStatus(order,status){try{await HF.request('/tienda/pedidos/'+order.database_id+'/estado','PUT',{status});await this.refreshPrivate();await this.refreshPublic();this.showToast('Estado actualizado.');}catch(e){this.showToast(e.message);}},
                openProductEditor(product=null){this.productEditingId=product?.id||null;this.productForm=product?{...product}:{name:'',color:'',category:'Hoodie',price:'',originalPrice:'',stock:0,image:'',badge:'',description:'',active:true};this.productEditorOpen=true;},
                async saveProduct(){if(this.isSaving)return;this.isSaving=true;try{const body={...this.productForm,stock:Number(this.productForm.stock),originalPrice:this.productForm.originalPrice||null};await HF.request('/tienda/productos'+(this.productEditingId?'/'+this.productEditingId:''),this.productEditingId?'PUT':'POST',body);await this.refreshPublic();this.productEditorOpen=false;this.showToast('Producto guardado.');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async toggleProduct(product){try{await HF.request('/tienda/productos/'+product.id+'/visibilidad','PATCH',{});await this.refreshPublic();this.showToast('Visibilidad actualizada.');}catch(e){this.showToast(e.message);}}
            }));
        });
```

## resources/views/crm/index.blade.php

```php
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>HFSTUDIOS | CRM</title>
    <link rel="stylesheet" href="/css/crm.css">
    <script src="/js/http.js?v={{ filemtime(public_path('js/http.js')) }}" defer></script><script src="/js/crm.js?v={{ filemtime(public_path('js/crm.js')) }}" defer></script>
</head>
<body>
<header><a href="/" class="brand">HFSTUDIOS <small>CRM</small></a><div>@include('partials.module-links') · {{ auth()->user()->name }} · {{ auth()->user()->role_label }} <button id="logout" type="button">Cerrar sesión</button></div></header>
<div class="shell">
    <aside><p>RELACIONES CON CLIENTES</p><nav>
        <button data-tab="dashboard" class="selected">Dashboard</button><button data-tab="clients">Clientes</button>
        <button data-tab="activity">Mi actividad</button><button data-tab="contacts">Mensajes de contacto</button>
        @if(auth()->user()->role === 'admin')<button data-tab="users">Equipo y permisos</button>@endif
        <a href="/">Volver a la tienda</a>
    </nav><small>Seguimiento centralizado.<br>Alertas tras 30 días sin contacto.</small></aside>
    <main>
        <div id="message" role="status" hidden></div>
        <section id="dashboard" class="tab"><div class="heading"><div><span class="eyebrow">VISTA GENERAL</span><h1>La relación, en números.</h1></div><button id="refresh-metrics">Actualizar</button></div>
            <div id="counters" class="cards"></div><div class="two-col"><article><h2>Etapas CRM</h2><div id="chart" role="img" aria-label="Clientes por etapa CRM"></div></article>
            <article><h2>Clientes en riesgo</h2><p class="muted">Clientes activos sin interacciones o con último contacto hace más de 30 días.</p><div id="risks"></div></article></div>
            <article><h2>Interacciones por cliente</h2><div id="interaction-counts"></div></article>
        </section>
        <section id="clients" class="tab" hidden><div class="heading"><div><span class="eyebrow">DIRECTORIO</span><h1>Clientes</h1></div><button id="new-client" class="primary">+ Nuevo cliente</button></div>
            <form id="filters" class="filters"><label>Buscar<input name="q" placeholder="Nombre, correo o empresa" maxlength="120"></label>
                <label>Estado<select name="estado"><option value="">Todos</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></label>
                <label>Etapa<select name="etapa"><option value="">Todas</option><option>Prospecto</option><option>Activo</option><option>Frecuente</option><option>Inactivo</option></select></label><button>Filtrar</button></form>
            <article><div class="scroll"><table><thead><tr><th>Cliente</th><th>Contacto</th><th>Empresa</th><th>Estado / etapa</th><th>Interacciones</th><th>Acciones</th></tr></thead><tbody id="client-list"></tbody></table></div><div id="client-pages" class="pagination"></div></article>
        </section>
        <section id="activity" class="tab" hidden><div class="heading"><div><span class="eyebrow">TU SEGUIMIENTO</span><h1>Mi actividad</h1></div></div><article><div id="my-activity" class="timeline"></div><div id="activity-pages" class="pagination"></div></article></section>
        <section id="contacts" class="tab" hidden><div class="heading"><div><span class="eyebrow">BANDEJA DE ENTRADA</span><h1>Mensajes de contacto</h1></div></div><article><div id="contact-list"></div><div id="contact-pages" class="pagination"></div></article></section>
        @if(auth()->user()->role === 'admin')
        <section id="users" class="tab" hidden><div class="heading"><div><span class="eyebrow">ADMINISTRACIÓN</span><h1>Equipo y permisos</h1></div></div><div class="two-col"><article><h2>Crear cuenta del equipo</h2><form id="user-form">
            <label>Nombre<input name="name" required minlength="2" maxlength="120"></label><label>Correo<input name="email" type="email" required></label>
            <label>Contraseña<input name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password"></label>
            <label>Rol<select name="role"><option value="ventas">Ventas: CRM y órdenes ERP</option><option value="logistica">Logística: SCM y entregas</option><option value="usuario">Operador: CRM y operación</option><option value="admin">Administrador: todos los módulos</option></select></label><button type="submit" class="primary">Crear usuario</button></form><a href="/erp#users">Cambiar roles desde ERP</a></article>
            <article><h2>Cuentas registradas</h2><div id="user-list"></div></article></div></section>
        @endif
        <section id="detail" class="tab" hidden><button id="back-clients">← Volver a clientes</button><div class="heading"><div><span class="eyebrow">HISTORIAL CENTRALIZADO</span><h1 id="detail-title"></h1></div></div><article id="detail-info"></article>
            <div class="two-col"><article><h2>Registrar interacción</h2><form id="interaction-form"><label>Tipo<select name="tipo"><option value="llamada">Llamada</option><option value="correo">Correo</option><option value="reunión">Reunión</option></select></label>
                <label>Fecha y hora<input name="fecha" type="datetime-local" required></label><label>Descripción<textarea name="descripcion" required minlength="3" maxlength="3000" rows="4"></textarea></label><p class="muted">El responsable se registra automáticamente con tu cuenta.</p><button class="primary">Guardar interacción</button></form></article>
            <article><h2>Evaluar relación</h2><form id="evaluation-form"><label>Puntuación<select name="puntuacion"><option value="5">5 · Excelente</option><option value="4">4 · Buena</option><option value="3">3 · Regular</option><option value="2">2 · Débil</option><option value="1">1 · Crítica</option></select></label>
                <label>Observaciones<textarea name="observaciones" rows="3" maxlength="2000"></textarea></label><button class="primary">Guardar evaluación</button></form><div id="evaluation-list"></div></article></div>
            <article><h2>Línea de tiempo</h2><div id="history" class="timeline"></div></article>
        </section>
    </main>
</div>
<dialog id="client-dialog"><form id="client-form"><div class="heading"><h2 id="client-form-title">Nuevo cliente</h2><button type="button" id="close-client">Cerrar</button></div>
    <label>Nombre<input name="nombre" required minlength="2" maxlength="120"></label><label>Correo<input name="correo" type="email" required maxlength="255"></label>
    <label>Teléfono<input name="telefono" maxlength="25" placeholder="+52 449 123 4567"></label><label>Empresa<input name="empresa" maxlength="150"></label>
    <div class="two-col"><label>Estado<select name="estado"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></label><label>Etapa CRM<select name="etapa_crm"><option>Prospecto</option><option>Activo</option><option>Frecuente</option><option>Inactivo</option></select></label></div>
    <p id="client-error" role="alert"></p><button class="primary">Guardar cliente</button>
</form></dialog>
</body></html>
```

## resources/views/erp/index.blade.php

```php
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>HFSTUDIOS | ERP</title>
    <link rel="stylesheet" href="/css/erp.css?v={{ filemtime(public_path('css/erp.css')) }}">
    <script src="/js/http.js?v={{ filemtime(public_path('js/http.js')) }}" defer></script>
    <script src="/js/erp.js?v={{ filemtime(public_path('js/erp.js')) }}" defer></script>
</head>
@php($u = auth()->user())
<body data-permissions='@json($u->permissions)' data-role="{{ $u->role }}" data-user-id="{{ $u->id }}">
<aside class="sidebar">
    <a class="brand" href="/erp">HF<span>STUDIOS</span><small>BUSINESS PLATFORM</small></a>
    <div class="workspace">EMPRESA / ETAPA 03</div>
    <nav aria-label="Navegación empresarial">
        <button type="button" data-panel="dashboard" aria-current="page"><span>01</span> Inicio ERP</button>
        @if($u->hasPermission('crm'))<a href="/crm"><span>02</span> Clientes · CRM</a>@endif
        @if($u->hasPermission('scm.read'))<a href="/scm"><span>03</span> Inventario · SCM</a>@endif
        <button type="button" data-panel="orders"><span>04</span> Órdenes de clientes</button>
        @if($u->hasPermission('erp.write'))<button type="button" data-panel="new"><span>＋</span> Nueva orden</button>@endif
        <button type="button" data-panel="reports"><span>05</span> Reportes</button>
        <button type="button" data-panel="resources"><span>06</span> Recursos internos</button>
        <button type="button" data-panel="maturity"><span>07</span> Nivel ERP</button>
        @if($u->hasPermission('users'))<button type="button" data-panel="users"><span>08</span> Usuarios y roles</button>@endif
        <a href="/"><span>↗</span> Ir a la tienda</a>
    </nav>
    <div class="sidebar-note"><span class="live-dot"></span> CRM + SCM + ERP<p>Una operación. Información compartida.</p></div>
</aside>
<div class="workspace-content">
    <header class="topbar"><div><span class="eyebrow">CENTRO DE OPERACIONES</span><strong>Planificación empresarial</strong></div><div class="account"><span>{{ $u->name }}<small>{{ $u->role_label }}</small></span><button id="logout" type="button">Cerrar sesión</button></div></header>
    <main>
        <noscript>Activa JavaScript para utilizar los formularios del sistema.</noscript>
        <div id="notice" class="notice" role="status" aria-live="polite" hidden></div>
        <section id="dashboard" class="panel">
            <div class="page-heading"><div><p class="eyebrow">VISIÓN GENERAL</p><h1>Tu negocio, conectado.</h1><p>Clientes, operaciones e inventario en un mismo lugar.</p></div><button type="button" id="refresh">Actualizar datos</button></div>
            <div id="kpis" class="kpis" aria-label="Indicadores empresariales"><p>Cargando indicadores…</p></div>
            <div class="grid-main"><article><div class="article-heading"><div><p class="eyebrow">EVOLUCIÓN COMERCIAL</p><h2>Ventas de los últimos 6 meses</h2></div><span class="pill">MXN</span></div><div id="sales-chart" class="sales-chart" role="img" aria-label="Ventas por mes"></div><p class="hint">Ventas ERP procesadas y pedidos de tienda enviados o entregados.</p></article><article><p class="eyebrow">CATÁLOGO</p><h2>Productos más vendidos</h2><div id="top-chart"></div></article></div>
            <div class="grid-main"><article><div class="article-heading"><div><p class="eyebrow">ATENCIÓN OPERATIVA</p><h2>Inventario crítico</h2></div><a href="/scm">Abrir SCM ↗</a></div><div id="critical-stock"></div></article><article><p class="eyebrow">FLUJO EMPRESARIAL</p><h2>Integración entre módulos</h2><div id="integration"></div></article></div>
        </section>
        <section id="orders" class="panel" hidden>
            <div class="page-heading"><div><p class="eyebrow">OPERACIONES / ERP</p><h1>Órdenes de clientes</h1><p>Consulta la venta, sus movimientos de inventario y su historial.</p></div>@if($u->hasPermission('erp.write'))<button type="button" class="primary" data-panel="new">＋ Nueva orden</button>@endif</div>
            <form id="order-filter" class="filters"><label>Buscar<input name="q" maxlength="120" placeholder="Folio, nombre o correo del cliente"></label><label>Estado<select name="estado"><option value="">Todos</option><option value="pendiente">Pendiente</option><option value="procesada">Procesada</option><option value="completada">Completada</option><option value="cancelada">Cancelada</option></select></label><button type="submit">Buscar</button><button type="button" id="clear-order-filter">Limpiar</button></form>
            <article class="table-article"><div class="table-wrap"><table><thead><tr><th>Folio / fecha</th><th>Cliente</th><th>Responsable</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="order-rows"></tbody></table></div><div id="order-pages" class="pagination"></div></article>
            <p class="hint">Pendiente conserva la cotización y todavía no reserva ni descuenta stock. Procesada descuenta inventario; Completada confirma la entrega.</p>
        </section>
        @if($u->hasPermission('erp.write'))
        <section id="new" class="panel" hidden>
            <div class="page-heading"><div><p class="eyebrow">OPERACIONES / NUEVA ORDEN</p><h1>De cliente a operación.</h1><p>Elige un cliente del CRM y productos del catálogo SCM.</p></div><button type="button" id="cancel-wizard">Cancelar orden</button></div>
            <ol class="steps" aria-label="Pasos de la orden"><li data-step-indicator="1" aria-current="step"><b>1</b> Cliente</li><li data-step-indicator="2"><b>2</b> Productos</li><li data-step-indicator="3"><b>3</b> Confirmar</li></ol>
            <div data-step="1"><article><div class="article-heading"><h2>Selecciona un cliente activo</h2><button type="button" id="new-client">＋ Nuevo cliente CRM</button></div><form id="client-search" class="filters"><label>Buscar cliente<input name="q" maxlength="120" placeholder="Nombre, empresa o correo"></label><button type="submit">Buscar</button></form><div id="client-results" class="selection-list"></div><div id="client-pages" class="pagination"></div></article><article id="selected-client" hidden></article><div class="wizard-actions"><button type="button" class="primary" id="client-next">Siguiente: productos →</button></div></div>
            <div data-step="2" hidden><article><div class="article-heading"><h2>Agrega productos y cantidades</h2><span class="pill">Stock compartido con SCM</span></div><form id="product-search" class="filters"><label>Buscar producto<input name="q" maxlength="120" placeholder="Nombre o descripción"></label><button type="submit">Buscar</button></form><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Disponible</th><th>Precio MXN</th><th>Cantidad</th><th>Agregar</th></tr></thead><tbody id="catalog-rows"></tbody></table></div><div id="catalog-pages" class="pagination"></div></article><article><h2>Productos de esta orden</h2><div id="cart-preview"></div></article><div class="wizard-actions"><button type="button" id="product-back">← Cliente</button><button type="button" class="primary" id="product-next">Siguiente: confirmar →</button></div></div>
            <div data-step="3" hidden><article><h2>Revisa y confirma</h2><div id="review-client"></div><div id="review-items"></div><label>Observaciones<textarea id="order-notes" maxlength="500" rows="3" placeholder="Indicaciones de entrega o notas internas"></textarea></label><div class="total-box"><span>Total MXN</span><strong id="order-total"></strong></div><p class="hint">Al confirmar se valida el stock, se descuenta el inventario y se registra el historial de la operación.</p></article><div class="wizard-actions"><button type="button" id="review-back">← Productos</button><button type="button" id="save-draft">Guardar pendiente</button><button type="button" class="primary" id="confirm-order">Confirmar y procesar orden</button></div><p id="process-status" role="status" aria-live="polite"></p></div>
        </section>
        <section id="success" class="panel" hidden><article class="success-card"><div class="success-icon">✓</div><p class="eyebrow">OPERACIÓN REGISTRADA</p><h1 id="success-title">Orden registrada con éxito.</h1><div id="success-summary"></div><div id="process-steps" class="process-steps"></div><div class="wizard-actions"><button type="button" class="primary" id="view-created">Ver orden</button><button type="button" data-panel="orders">Ir a órdenes</button><button type="button" id="another-order">Crear otra orden</button></div></article></section>
        @endif
        <section id="reports" class="panel" hidden><div class="page-heading"><div><p class="eyebrow">ANÁLISIS EMPRESARIAL</p><h1>Reportes que conservan el contexto.</h1><p>Consulta indicadores actuales y guarda un corte de los resultados.</p></div><div class="actions"><a class="button" href="/erp/reportes/csv">Exportar CSV actual</a><button type="button" class="primary" id="generate-report">Guardar corte empresarial</button></div></div><div id="report-kpis" class="kpis"></div><article><h2>Cortes guardados</h2><div class="table-wrap"><table><thead><tr><th>Corte</th><th>Fecha</th><th>Responsable</th><th>Ventas</th><th>Órdenes ERP</th><th>Acciones</th></tr></thead><tbody id="report-rows"></tbody></table></div><div id="report-pages" class="pagination"></div></article><article><h2>Qué integra este reporte</h2><p>Clientes activos del CRM, catálogo e inventario del SCM, órdenes ERP y ventas de tienda surtidas. Las reposiciones a proveedores conservan su proceso en SCM.</p><p>Las órdenes canceladas y los pedidos de tienda pendientes se excluyen de las ventas. Cada corte conserva los valores del momento en que fue generado.</p></article></section>
        <section id="resources" class="panel" hidden><div class="page-heading"><div><p class="eyebrow">PLANIFICACIÓN</p><h1>Recursos internos</h1><p>Organiza equipo, instalaciones y herramientas de la empresa.</p></div>@if($u->hasPermission('erp.resources'))<button type="button" class="primary" id="new-resource">＋ Recurso</button>@endif</div><article><div class="table-wrap"><table><thead><tr><th>Recurso</th><th>Tipo</th><th>Responsable</th><th>Disponibilidad</th><th>Acciones</th></tr></thead><tbody id="resource-rows"></tbody></table></div></article></section>
        <section id="maturity" class="panel" hidden><div class="page-heading"><div><p class="eyebrow">ADOPCIÓN DEL SISTEMA</p><h1>Nivel de implementación ERP</h1><p>Relaciona las operaciones registradas con la madurez empresarial.</p></div></div><article><div id="erp-levels" class="levels"></div><h2 id="level-name"></h2><p id="level-description"></p><progress id="erp-progress" max="100" value="0"></progress><p id="progress-text"></p><form id="maturity-form"><div id="evidence-list" class="evidence-list"></div>@if($u->hasPermission('erp.admin'))<label>Nivel<select name="nivel"><option>Básico</option><option>Integrado</option><option>Automatizado</option><option>Optimizado</option></select></label><button class="primary" type="submit">Guardar nivel y evidencias</button>@endif</form><p class="hint">Integrado exige 3 evidencias; Automatizado, 4 e inventario automatizado; Optimizado, las 6. Cada evidencia se comprueba contra los registros del sistema.</p></article></section>
        @if($u->hasPermission('users'))
        <section id="users" class="panel" hidden><div class="page-heading"><div><p class="eyebrow">ADMINISTRACIÓN</p><h1>Equipo y permisos</h1><p>Cada rol tiene acceso a sus procesos y módulos.</p></div><button type="button" class="primary" id="new-user">＋ Usuario</button></div><article><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Rol actual</th><th>Nuevo rol</th><th>Acción</th></tr></thead><tbody id="user-rows"></tbody></table></div></article><article><h2>Permisos por rol</h2><p><strong>Administrador:</strong> todos los módulos, usuarios y configuración. <strong>Ventas:</strong> clientes, nuevas órdenes y reportes. <strong>Logística:</strong> catálogo, proveedores, inventario, recursos y entregas. <strong>Operador:</strong> conserva el acceso operativo de la Etapa 2.</p></article></section>
        @endif
        <footer>HFSTUDIOS · CRM + SCM + ERP <span>Información compartida. Operaciones trazables.</span></footer>
    </main>
</div>
<dialog id="order-dialog" aria-labelledby="order-title"><div class="article-heading"><h2 id="order-title">Detalle de orden</h2><button type="button" data-close="order-dialog">Cerrar</button></div><div id="order-detail"></div><div class="actions"><button type="button" id="print-order">Imprimir / PDF</button></div></dialog>
<dialog id="snapshot-dialog" aria-labelledby="snapshot-title"><div class="article-heading"><h2 id="snapshot-title">Corte empresarial</h2><button type="button" data-close="snapshot-dialog">Cerrar</button></div><div id="snapshot-detail"></div></dialog>
@if($u->hasPermission('erp.write'))
<dialog id="client-dialog" aria-labelledby="client-title"><div class="article-heading"><h2 id="client-title">Nuevo cliente CRM</h2><button type="button" data-close="client-dialog">Cerrar</button></div><form id="client-form"><label>Nombre<input name="nombre" required minlength="2" maxlength="120" autocomplete="name"></label><label>Correo<input name="correo" required type="email" maxlength="255" autocomplete="email"></label><label>Teléfono<input name="telefono" maxlength="25" autocomplete="tel"></label><label>Empresa<input name="empresa" maxlength="150"></label><input type="hidden" name="estado" value="activo"><input type="hidden" name="etapa_crm" value="Prospecto"><button type="submit" class="primary">Guardar y seleccionar cliente</button></form></dialog>
@endif
@if($u->hasPermission('erp.resources'))
<dialog id="resource-dialog" aria-labelledby="resource-title"><div class="article-heading"><h2 id="resource-title">Recurso interno</h2><button type="button" data-close="resource-dialog">Cerrar</button></div><form id="resource-form"><input type="hidden" name="id"><label>Nombre<input name="nombre" required minlength="2" maxlength="120"></label><label>Tipo<select name="tipo"><option value="humano">Humano</option><option value="fisico">Físico</option><option value="tecnologico">Tecnológico</option><option value="financiero">Financiero</option></select></label><label>Descripción<textarea name="descripcion" maxlength="500" rows="3"></textarea></label><label>Responsable<select name="responsable_id"><option value="">Sin asignar</option></select></label><label class="check"><input type="checkbox" name="disponible" checked>Disponible para la operación</label><button type="submit" class="primary">Guardar recurso</button></form></dialog>
@endif
@if($u->hasPermission('users'))
<dialog id="user-dialog" aria-labelledby="user-title"><div class="article-heading"><h2 id="user-title">Cuenta del equipo</h2><button type="button" data-close="user-dialog">Cerrar</button></div><form id="user-form"><label>Nombre<input name="name" required minlength="2" maxlength="120"></label><label>Correo<input name="email" type="email" required maxlength="255"></label><label>Contraseña<input name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password"></label><label>Rol<select name="role"><option value="ventas">Ventas</option><option value="logistica">Logística</option><option value="usuario">Operador</option><option value="admin">Administrador</option></select></label><button type="submit" class="primary">Crear usuario</button></form></dialog>
@endif
</body>
</html>
```

## resources/views/partials/module-links.blade.php

```php
@if(auth()->user()->hasPermission('erp.read'))<a href="/erp">ERP</a>@endif
@if(auth()->user()->hasPermission('crm'))<a href="/crm">CRM</a>@endif
@if(auth()->user()->hasPermission('scm.read'))<a href="/scm">SCM</a>@endif
```

## resources/views/scm/index.blade.php

```php
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>HFSTUDIOS | SCM</title>
    <link rel="stylesheet" href="/css/scm.css">
    <script src="/js/http.js?v={{ filemtime(public_path('js/http.js')) }}" defer></script><script src="/js/scm.js?v={{ filemtime(public_path('js/scm.js')) }}" defer></script>
</head>
<body data-role="{{ auth()->user()->role }}">
<header><a class="brand" href="/">HFSTUDIOS <small>SCM</small></a><div>@include('partials.module-links')<span>{{ auth()->user()->name }} · {{ auth()->user()->role_label }}</span><button id="logout" type="button">Salir</button></div></header>
<nav aria-label="Módulos SCM"><button data-tab="dashboard" aria-current="page" type="button">Resumen</button><button data-tab="catalog" type="button">Catálogo e inventario</button><button data-tab="suppliers" type="button">Proveedores</button><button data-tab="orders" type="button">Pedidos SCM</button><button data-tab="maturity" type="button">Madurez</button></nav>
<main>
    <div class="page-title"><div><p class="eyebrow">ETAPA 2 · CADENA DE SUMINISTRO</p><h1>Inventario con trazabilidad.</h1><p>Consulta existencias, recibe pedidos y compara las estrategias Push y Pull.</p></div><button id="refresh" type="button">Actualizar datos</button></div>
    <div id="notice" role="status" aria-live="polite" hidden></div>
    <section id="dashboard" class="panel">
        <div id="counters" class="cards" aria-label="Indicadores de inventario"></div>
        <div class="section-heading"><h2>Reportes</h2><a class="button" href="/scm/reportes/csv">Descargar CSV</a></div>
        <div class="two-col"><article><h3>Productos más vendidos</h3><p>Unidades históricas: ventas manuales surtidas y pedidos de tienda enviados o entregados.</p><div id="top-chart"></div></article><article><h3>Comparación logística</h3><p>Existencias disponibles por estrategia y cantidad de productos en nivel crítico.</p><div id="strategy-chart"></div></article></div>
        <div class="two-col"><article><h3>Inventario crítico</h3><p>Stock disponible menor o igual al mínimo configurado.</p><div id="critical"></div></article><article><h3>Rotación lenta</h3><p>Productos con stock y 0–2 unidades vendidas en los últimos 30 días.</p><div id="slow"></div></article></div>
    </section>
    <section id="catalog" class="panel" hidden>
        <div class="section-heading"><h2>Catálogo e inventario</h2>@if(auth()->user()->hasPermission('scm.manage'))<button id="new-product" class="primary" type="button">+ Producto</button>@endif</div>
        <form id="product-filter" class="filters"><label>Buscar<input name="q" maxlength="120" placeholder="Nombre o descripción"></label><label>Estrategia<select name="estrategia"><option value="">Todas</option><option>PUSH</option><option>PULL</option></select></label><label>Estado<select name="critico"><option value="">Todos</option><option value="1">Stock crítico</option></select></label><label>Archivo<select name="archivados"><option value="0">Vigentes</option><option value="1">Archivados</option></select></label><button type="submit">Aplicar filtros</button></form>
        <div class="table-wrap"><table><thead><tr><th>Producto / proveedor</th><th>Existencias</th><th>Mínimo / objetivo</th><th>Costo / precio</th><th>Estrategia</th><th>Acciones</th></tr></thead><tbody id="product-rows"></tbody></table></div><div id="product-pages" class="pagination"></div>
    </section>
    <section id="suppliers" class="panel" hidden>
        <div class="section-heading"><h2>Proveedores</h2>@if(auth()->user()->hasPermission('scm.manage'))<button id="new-supplier" class="primary" type="button">+ Proveedor</button>@endif</div>
        <div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Contacto</th><th>Correo</th><th>Teléfono</th><th>Acciones</th></tr></thead><tbody id="supplier-rows"></tbody></table></div>
    </section>
    <section id="orders" class="panel" hidden>
        <div class="section-heading"><h2>Pedidos SCM</h2>@if(auth()->user()->hasPermission('scm.operate'))<button id="new-order" class="primary" type="button">Generar pedido</button>@endif</div>
        <p>Los pedidos SCM son de un producto. Los pedidos del carrito se gestionan en el panel de la tienda. Surtir una reposición agrega stock; surtir una venta lo descuenta.</p>
        <form id="order-filter" class="filters"><label>Estado<select name="estado"><option value="">Todos</option><option value="pendiente">Pendiente</option><option value="surtido">Surtido</option></select></label><label>Tipo<select name="tipo"><option value="">Todos</option><option value="reposicion">Reposición</option><option value="venta">Venta</option></select></label><button type="submit">Aplicar filtros</button></form>
        <div class="table-wrap"><table><thead><tr><th>Pedido / producto</th><th>Cantidad</th><th>Tipo / origen</th><th>Estado</th><th>Registro / recepción</th><th>Acciones</th></tr></thead><tbody id="order-rows"></tbody></table></div><div id="order-pages" class="pagination"></div>
    </section>
    <section id="maturity" class="panel" hidden>
        <article><h2>Nivel de madurez SCM</h2><p id="level-indicator" class="level"></p><p id="level-description"></p><div class="two-col"><div><h3>Evidencias de la operación</h3><p>El administrador confirma evidencias reales. Inicial no exige mínimos; En desarrollo exige al menos 3; Optimizado exige las 6.</p><form id="maturity-form"><label>Nivel<select name="nivel_scm"><option>Inicial</option><option>En desarrollo</option><option>Optimizado</option></select></label>
            @foreach(['catalogo'=>'Catálogo con mínimos, costos y ubicación revisados','proveedores'=>'Proveedores y contactos validados','inventario'=>'Entradas, salidas y saldos reconciliados','estrategias'=>'Pruebas Push y Pull completadas','pedidos'=>'Pedidos recibidos sin duplicar existencias','reportes'=>'Reportes revisados y acciones documentadas'] as $key => $label)
            <label class="check"><input type="checkbox" name="{{ $key }}">{{ $label }}</label>
            @endforeach
            @if(auth()->user()->role === 'admin')<button class="primary" type="submit">Guardar nivel y evidencias</button>@else<p>Solo el administrador puede cambiar este nivel.</p>@endif
        </form></div><div><h3>Cómo funciona cada estrategia</h3><p><strong>Push:</strong> cuando stock ≤ mínimo, genera una reposición hasta el objetivo, descontando cantidades ya pedidas. La recepción física se confirma en Pedidos SCM.</p><p><strong>Pull:</strong> no genera reposiciones por el mínimo. El equipo solicita un pedido manual cuando existe demanda.</p><p>Los pedidos pendientes se conservan cuando cambia la estrategia. El sistema no envía solicitudes a proveedores externos.</p></div></div></article>
    </section>
</main>
@if(auth()->user()->hasPermission('scm.manage'))
<dialog id="product-dialog">
    <div class="dialog-heading">
        <h2 id="product-title">Producto</h2>
        <button data-close="product-dialog" type="button" aria-label="Cerrar">×</button>
    </div>
    <form id="product-form">
        <input type="hidden" name="id">
        <label>Nombre<input name="nombre" required minlength="2" maxlength="120"></label>
        <label>Descripción<textarea name="descripcion" required minlength="3" maxlength="3000"></textarea></label>
        <div class="two-col"><label>Categoría<select name="categoria"><option>Hoodie</option><option>T-Shirt</option><option>Pants</option><option>Accessory</option></select></label><label>Proveedor<select name="proveedor_id"><option value="">Sin proveedor</option></select></label></div>
        <div class="three-col"><label>Stock inicial<input name="stock_actual" type="number" min="0" max="1000000" step="1" required></label><label>Stock mínimo<input name="stock_minimo" type="number" min="0" max="999999" step="1" required></label><label>Stock objetivo<input name="stock_objetivo" type="number" min="1" max="1000000" step="1" required></label></div>
        <p class="hint">Después del alta, cambia las existencias registrando movimientos desde el historial.</p>
        <div class="two-col"><label>Costo unitario (MXN)<input name="costo_unitario" type="number" min="0" max="1000000" step="0.01" required></label><label>Precio de venta (MXN)<input name="precio_venta" type="number" min="0.01" max="1000000" step="0.01" required></label></div>
        <div class="two-col"><label>Estrategia<select name="estrategia_logistica"><option>PULL</option><option>PUSH</option></select></label><label>Ubicación<input name="ubicacion" required maxlength="120"></label></div>
        <label>URL de imagen<input name="imagen" type="url" required maxlength="2048"></label><label>Color<input name="color" maxlength="100"></label><label class="check"><input name="activo" type="checkbox">Visible en tienda</label>
        <button class="primary" type="submit">Guardar producto</button>
    </form>
</dialog>

<dialog id="supplier-dialog">
    <div class="dialog-heading">
        <h2>Proveedor</h2>
        <button data-close="supplier-dialog" type="button" aria-label="Cerrar">×</button>
    </div>
    <form id="supplier-form">
        <input name="id" type="hidden">
        <label>Nombre<input name="nombre" required minlength="2" maxlength="120"></label>
        <label>Persona de contacto<input name="contacto" required minlength="2" maxlength="120"></label>
        <label>Correo<input name="correo" type="email" required maxlength="255"></label>
        <label>Teléfono<input name="telefono" type="tel" required maxlength="25"></label>
        <button class="primary" type="submit">Guardar proveedor</button>
    </form>
</dialog>
@endif

<dialog id="history-dialog">
    <div class="dialog-heading">
        <h2 id="history-title">Movimientos</h2>
        <button data-close="history-dialog" type="button" aria-label="Cerrar">×</button>
    </div>
    <p id="history-stock"></p>
    <form id="movement-form" class="filters">
        <input type="hidden" name="producto_id">
        <label>Tipo<select name="tipo"><option value="entrada">Entrada</option><option value="salida">Salida</option></select></label>
        <label>Motivo<select name="motivo"><option value="ajuste">Ajuste</option><option value="venta">Venta</option><option value="reposición">Reposición recibida</option></select></label>
        <label>Cantidad<input name="cantidad" type="number" min="1" max="1000000" step="1" required></label>
        <label>Referencia<input name="referencia" maxlength="255" placeholder="Conteo físico / documento"></label>
        <button class="primary" type="submit">Registrar movimiento</button>
    </form>
    <p id="movement-hint" class="hint">Para recibir un pedido ya pendiente, usa Pedidos SCM. Una reposición directa sin pedido pendiente crea y surte un pedido manual.</p>
    <div class="table-wrap"><table><thead><tr><th>Fecha / responsable</th><th>Tipo / motivo</th><th>Cantidad</th><th>Saldo anterior → final</th><th>Referencia</th></tr></thead><tbody id="history-rows"></tbody></table></div>
    <div id="history-pages" class="pagination"></div>
</dialog>

<dialog id="order-dialog">
    <div class="dialog-heading">
        <h2>Generar pedido manual</h2>
        <button data-close="order-dialog" type="button" aria-label="Cerrar">×</button>
    </div>
    <form id="order-form">
        <label>Buscar producto<input id="order-search" maxlength="120" placeholder="Escribe nombre y pulsa Buscar"></label>
        <button id="order-search-button" type="button">Buscar</button>
        <label>Producto<select name="producto_id" required></select></label>
        <label>Tipo<select name="tipo"><option value="reposicion">Reposición</option><option value="venta">Venta</option></select></label>
        <label>Cantidad<input name="cantidad" type="number" min="1" max="1000000" step="1" required></label>
        <p>La reposición requiere proveedor. El stock cambia al marcar el pedido surtido.</p>
        <button class="primary" type="submit">Generar pedido</button>
    </form>
</dialog>

<footer>HFSTUDIOS · CRM y SCM · Etapas 1 y 2</footer>
</body></html>
```

## resources/views/welcome.blade.php

```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HFSTUDIOS - Acceso Exclusivo</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">



    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', sans-serif; }
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }
        @keyframes marquee { 0% { transform: translateX(0%); } 100% { transform: translateX(-50%); } }
        .marquee-content { animation: marquee 20s linear infinite; }
        .product-card:hover .hover-overlay { opacity: 1; transform: translateY(0); }
        .hover-overlay { opacity: 0; transform: translateY(20px); transition: all 0.3s ease; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { animation: spin 1s linear infinite; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
        .grayscale-map { filter: grayscale(100%); }
    </style>
</head>
<body class="bg-white antialiased" x-data="app">

    <div x-show="toast.show" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-full" class="fixed top-6 right-6 z-[90] bg-black text-white px-6 py-4 shadow-2xl max-w-sm" x-cloak>
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="font-semibold text-sm" x-text="toast.message"></p>
        </div>
    </div>

    <div x-show="productDetailOpen" x-cloak class="fixed inset-0 z-[85] overflow-y-auto">
        <div @click="productDetailOpen = false" x-show="productDetailOpen" x-transition.opacity class="fixed inset-0 bg-black/80"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="productDetailOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-6xl w-full grid md:grid-cols-2 gap-0 shadow-2xl">
                <button @click="productDetailOpen = false" class="absolute top-4 right-4 z-10 w-10 h-10 flex items-center justify-center bg-white/90 hover:bg-white rounded-full text-black font-bold text-2xl shadow-lg">×</button>
                <div class="bg-[#F4F4F4] flex items-center justify-center p-8 min-h-[500px]">
                    <img :src="selectedProduct.image" :alt="selectedProduct.name" class="w-full h-auto object-cover max-h-[600px]">
                </div>
                <div class="p-8 md:p-12 flex flex-col justify-center">
                    <span x-show="selectedProduct.badge" :class="selectedProduct.badge && selectedProduct.badge.includes('PROMO') ? 'bg-black text-white' : 'bg-white text-black'" class="inline-block text-[9px] font-bold uppercase tracking-wider px-2 py-1 mb-4 w-fit"><span x-text="selectedProduct.badge"></span></span>
                    <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tight mb-3" x-text="selectedProduct.name"></h2>
                    <p class="text-sm text-gray-500 uppercase tracking-wider mb-4" x-text="selectedProduct.color"></p>
                    <div class="flex items-end gap-3 mb-6">
                        <p class="text-4xl font-black text-red-600" x-text="'$' + selectedProduct.price + ' MXN'"></p>
                        <template x-if="selectedProduct.originalPrice">
                            <p class="text-lg font-bold text-gray-400 line-through mb-1" x-text="'$' + selectedProduct.originalPrice + ' MXN'"></p>
                        </template>
                    </div>
                    <p class="text-sm text-gray-700 leading-relaxed mb-8" x-text="selectedProduct.description"></p>
                    <div class="space-y-4">
                        <button @click="addToCart(selectedProduct); productDetailOpen = false" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Agregar al Carrito</button>
                        <button @click="productDetailOpen = false" class="w-full border-2 border-black text-black py-4 text-sm font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">Regresar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="orderSuccessOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div @click="orderSuccessOpen = false; navigateTo('perfil')" x-show="orderSuccessOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="orderSuccessOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-md w-full p-12 text-center shadow-2xl">
            <div class="w-24 h-24 mx-auto mb-6 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-3xl font-black uppercase tracking-tight mb-3">Pedido registrado</h2>
            <p class="text-gray-600 mb-2">Tu pedido está pendiente de pago y revisión por el equipo.</p>
            <p class="text-sm font-mono bg-gray-100 inline-block px-4 py-2 mb-8" x-text="orderNumber"></p>
            <button @click="orderSuccessOpen = false; navigateTo('perfil')" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Ver en Mi Perfil</button>
        </div>
    </div>

    <div x-show="invoiceModalOpen" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4">
        <div @click="invoiceModalOpen = false" x-show="invoiceModalOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="invoiceModalOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-md w-full p-12 shadow-2xl">
            <button @click="invoiceModalOpen = false" class="absolute top-4 right-4 text-2xl font-bold hover:text-gray-500 transition">×</button>
            <div class="text-center mb-8 border-b-2 border-black pb-4">
                <h2 class="text-3xl font-black tracking-tighter">HFSTUDIOS</h2>
                <p class="text-[10px] uppercase tracking-widest text-gray-500 mt-1">Comprobante de pedido · Sin valor fiscal</p>
            </div>
            <template x-if="selectedOrder">
                <div>
                    <div class="flex justify-between mb-6 text-xs bg-gray-50 p-4 border border-gray-200">
                        <div>
                            <p class="font-bold uppercase text-gray-400 mb-1">Cliente:</p>
                            <p class="font-semibold uppercase" x-text="selectedOrder?.user || user.name || 'Invitado'"></p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold uppercase text-gray-400 mb-1">Detalles:</p>
                            <p class="font-mono">Folio: <span x-text="selectedOrder.id"></span></p>
                            <p x-text="selectedOrder.date"></p>
                        </div>
                    </div>
                    <div class="mb-6 max-h-48 overflow-y-auto custom-scroll pr-2">
                        <p class="text-[10px] font-bold uppercase text-gray-400 mb-2 border-b border-gray-200 pb-1">Productos</p>
                        <template x-for="item in selectedOrder.items" :key="item.name">
                            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                                <span class="uppercase font-medium text-gray-800 text-xs" x-text="(item.quantity || 1) + 'x ' + item.name"></span>
                                <span class="font-mono font-bold text-xs" x-text="'$' + ((item.price * (item.quantity || 1)).toLocaleString())"></span>
                            </div>
                        </template>
                    </div>
                    <div class="border-t-2 border-black pt-4 mb-8 flex justify-between items-center text-xl font-black uppercase">
                        <span>Total del pedido</span>
                        <span class="text-red-600 font-mono" x-text="'$' + (selectedOrder.total || 0).toLocaleString() + ' MXN'"></span>
                    </div>
                    <button @click="downloadInvoice()" class="w-full border-2 border-black text-black py-4 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">
                        Descargar PDF
                    </button>
                </div>
            </template>
        </div>
    </div>

    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div @click="editModalOpen = false" x-show="editModalOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="editModalOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-lg w-full p-10 shadow-2xl">
            <button @click="editModalOpen = false" class="absolute top-4 right-4 text-2xl font-bold hover:text-gray-500 transition">×</button>
            <h2 class="text-3xl font-black uppercase tracking-tight mb-8 text-center">Editar Publicación</h2>
            <form @submit.prevent="saveEdit()" novalidate class="space-y-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre del artículo</label>
                    <input type="text" x-model="editForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Precio (MXN)</label>
                    <input type="number" x-model="editForm.price" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">URL de la Foto</label>
                    <input type="text" x-model="editForm.image" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Descripción</label>
                    <textarea x-model="editForm.description" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none"></textarea>
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" @click="editModalOpen = false" class="w-1/3 border-2 border-black text-black py-4 text-sm font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">Cancelar</button>
                    <button type="submit" class="w-2/3 bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="currentRoute !== 'inicio' && currentRoute !== 'admin'" class="bg-[#F0EBE0] overflow-hidden relative h-6">
        <div class="flex whitespace-nowrap marquee-content">
            <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] py-1.5 px-8">VENTA SS26: 20% DE DESCUENTO EN TODA LA TIENDA | © HF STUDIOS, 2026</span>
            <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] py-1.5 px-8">VENTA SS26: 20% DE DESCUENTO EN TODA LA TIENDA | © HF STUDIOS, 2026</span>
            <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] py-1.5 px-8">VENTA SS26: 20% DE DESCUENTO EN TODA LA TIENDA | © HF STUDIOS, 2026</span>
        </div>
    </div>

    <nav x-show="currentRoute !== 'inicio' && currentRoute !== 'admin'" class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm">
        <div class="max-w-screen-2xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">

                <div class="hidden lg:flex items-center gap-6">
                    <button @click="navigateTo('catalogo')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Catálogo</button>
                    <button @click="navigateTo('campana')" class="text-xs font-bold uppercase tracking-wider text-blue-600 hover:opacity-60 transition">Campaña</button>
                    <button @click="navigateTo('promociones-front')" class="text-xs font-bold uppercase tracking-wider text-red-600 hover:opacity-60 transition">Promos</button>
                    <button @click="navigateTo('comunidad')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Comunidad</button>
                </div>

                <div class="absolute left-1/2 transform -translate-x-1/2">
                    <button @click="navigateTo('catalogo')" class="text-2xl md:text-3xl font-black tracking-tighter">HFSTUDIOS</button>
                </div>

                <div class="hidden lg:flex items-center gap-5">
                    <button @click="navigateTo('nosotros')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Nosotros</button>
                    <button x-show="!isLoggedIn" @click="navigateTo('login')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Ingresar</button>

                    <template x-if="isLoggedIn && !isAdmin">
                        <div class="flex items-center gap-5">
                            <button @click="navigateTo('subasta')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition"><i class="fas fa-gavel mr-1"></i> Subastas</button>
                            <button @click="navigateTo('mis-publicaciones')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Publicar</button>
                            <button @click="navigateTo('perfil')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Perfil</button>
                        </div>
                    </template>

                    <button x-show="isLoggedIn && can('crm')" @click="navigateTo('crm')" class="text-xs font-bold">CRM</button>
                    <a x-show="isLoggedIn && can('scm.read')" href="/scm" class="font-bold">SCM</a>
                    <a x-show="isLoggedIn && can('erp.read')" href="/erp" class="font-bold">ERP</a>
                    <button x-show="isLoggedIn && isAdmin" @click="navigateTo('admin')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition bg-black text-white px-2 py-1">Admin</button>

                    <button @click="cartOpen = true" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition relative">
                        <i class="fas fa-shopping-cart mr-2"></i>Cart (<span x-text="cartTotalItems"></span>)
                        <span x-show="cartTotalItems > 0" class="absolute -top-1 -right-1 w-2 h-2 bg-black rounded-full animate-pulse"></span>
                    </button>
                </div>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden text-sm font-bold"><span x-show="!mobileMenuOpen">MENÚ</span><span x-show="mobileMenuOpen">CERRAR</span></button>
            </div>
        </div>
    </nav>

    <div x-show="mobileMenuOpen" x-transition.opacity class="fixed inset-0 bg-black z-40 lg:hidden overflow-y-auto" x-cloak>
        <div class="flex flex-col items-center justify-center min-h-full py-12 space-y-6 text-white">
            <button @click="navigateTo('catalogo'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Catálogo</button>
            <button @click="navigateTo('campana'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider text-blue-400">Campaña</button>
            <button @click="navigateTo('promociones-front'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider text-red-500">Promos</button>
            <button @click="navigateTo('comunidad'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Comunidad</button>
            <button @click="navigateTo('nosotros'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Nosotros</button>

            <button x-show="!isLoggedIn" @click="navigateTo('login'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider border-t border-gray-800 pt-6 mt-2">Iniciar sesión</button>

            <template x-if="isLoggedIn && !isAdmin">
                <div class="flex flex-col items-center space-y-6 border-t border-gray-800 pt-6 w-full">
                    <button @click="navigateTo('subasta'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Subastas</button>
                    <button @click="navigateTo('mis-publicaciones'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Mis Publicaciones</button>
                    <button @click="navigateTo('perfil'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Perfil</button>
                </div>
            </template>

            <button x-show="isLoggedIn && can('crm')" @click="navigateTo('crm')" class="text-xl font-bold">CRM</button>
                    <a x-show="isLoggedIn && can('scm.read')" href="/scm" class="font-bold">SCM</a>
                    <a x-show="isLoggedIn && can('erp.read')" href="/erp" class="font-bold">ERP</a>
            <button x-show="isLoggedIn && isAdmin" @click="navigateTo('admin'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider border-t border-gray-800 pt-6">Admin Panel</button>
            <button @click="cartOpen = true; mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider border-t border-gray-800 pt-6">Carrito (<span x-text="cartTotalItems"></span>)</button>
        </div>
    </div>

    <div x-show="cartOpen" class="fixed inset-0 z-[60]" x-cloak>
        <div @click="cartOpen = false" x-show="cartOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="cartOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 class="text-2xl font-black uppercase tracking-tight">Tu Carrito</h2>
                <button @click="cartOpen = false" class="text-3xl font-bold hover:opacity-60">×</button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 custom-scroll">
                <template x-if="cart.length === 0">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-24 w-24 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        <p class="text-gray-500 text-sm uppercase tracking-wider mb-4 font-semibold">Tu carrito está vacío</p>
                        <button @click="cartOpen = false" class="text-xs font-bold uppercase tracking-wider underline hover:no-underline">Continuar Comprando</button>
                    </div>
                </template>
                <div class="space-y-6">
                    <template x-for="(item, index) in cart" :key="item.cartItemId || item.id">
                        <div class="flex gap-4 pb-6 border-b border-gray-200">
                            <img :src="item.image" class="w-24 h-32 object-cover bg-gray-100 flex-shrink-0">
                            <div class="flex-1 min-w-0 flex flex-col">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-sm font-bold uppercase tracking-wide mb-1 truncate" x-text="item.name"></h3>
                                        <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-2" x-text="item.color"></p>
                                    </div>
                                    <button @click="removeFromCart(index)" class="text-xs text-gray-400 hover:text-red-600 font-semibold transition self-start"><i class="fas fa-trash"></i></button>
                                </div>
                                <div class="mt-auto flex items-center justify-between">
                                    <div class="flex items-center border border-gray-200 rounded-sm">
                                        <button @click="decreaseQty(index)" class="px-3 py-1 text-gray-500 hover:bg-gray-100 transition">-</button>
                                        <span class="px-2 py-1 text-xs font-bold font-mono text-center min-w-[2rem]" x-text="item.quantity || 1"></span>
                                        <button @click="increaseQty(index)" class="px-3 py-1 text-gray-500 hover:bg-gray-100 transition">+</button>
                                    </div>
                                    <p class="text-base font-bold text-red-600" x-text="'$' + ((item.price || 0) * (item.quantity || 1)).toLocaleString() + ' MXN'"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="border-t border-gray-200 p-6 space-y-4 bg-gray-50" x-show="cart.length > 0">
                <div class="flex gap-2 mb-4">
                    <input type="text" x-model="discountCode" placeholder="Código de descuento" class="flex-1 px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-black uppercase bg-white">
                    <button @click="applyDiscount()" class="bg-gray-800 text-white px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-black transition">Aplicar</button>
                </div>

                <div class="flex items-center justify-between text-lg font-bold">
                    <span class="uppercase tracking-wider">Subtotal</span>
                    <div class="text-right">
                        <template x-if="discountApplied">
                            <span class="text-xs text-gray-400 line-through block" x-text="'$' + cartTotalSinDescuento.toLocaleString() + ' MXN'"></span>
                        </template>
                        <span class="text-red-600" x-text="'$' + cartTotal.toLocaleString() + ' MXN'"></span>
                    </div>
                </div>
                <template x-if="discountApplied">
                    <p class="text-[10px] uppercase font-bold text-green-600 tracking-wider text-right">Descuento aplicado con éxito</p>
                </template>

                <button @click="handleCheckout()" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Finalizar Compra</button>
            </div>
        </div>
    </div>

    <div x-show="checkoutOpen" x-cloak class="fixed inset-0 z-[80] overflow-y-auto">
        <div @click="checkoutOpen = false" x-show="checkoutOpen" x-transition.opacity class="absolute inset-0 bg-black/80"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="checkoutOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-2xl w-full p-8 md:p-12 shadow-2xl overflow-hidden">
                <button @click="checkoutOpen = false" class="absolute top-4 right-4 text-2xl font-bold text-gray-400 hover:text-black transition">×</button>

                <div class="flex justify-between mb-12 relative px-4 md:px-12">
                    <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-200 -translate-y-1/2 z-0"></div>
                    <div class="absolute top-1/2 left-0 h-0.5 bg-black -translate-y-1/2 z-0 transition-all duration-500" :style="'width: ' + ((checkoutStep - 1) * 50) + '%'"></div>

                    <template x-for="step in [1, 2, 3]">
                        <div class="relative z-10 flex flex-col items-center bg-white px-2">
                            <div :class="checkoutStep >= step ? 'bg-black text-white border-black' : 'bg-white text-gray-300 border-gray-200'" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-colors">
                                <span x-show="checkoutStep <= step" x-text="step"></span>
                                <i x-show="checkoutStep > step" class="fas fa-check text-[10px]"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase mt-2 tracking-widest absolute -bottom-6 w-max text-center" :class="checkoutStep >= step ? 'text-black' : 'text-gray-400'" x-text="step === 1 ? 'Dirección' : step === 2 ? 'Pago' : 'Confirma'"></span>
                        </div>
                    </template>
                </div>

                <div x-show="checkoutStep === 1">
                    <h3 class="text-xl font-black uppercase mb-6 tracking-tight">Datos de Envío</h3>
                    <form @submit.prevent="checkoutStep = 2" class="space-y-4">
                        <input type="text" required x-model="checkoutForm.address" placeholder="Calle y número" class="w-full border-2 border-gray-200 px-4 py-3 text-sm focus:border-black outline-none bg-gray-50 focus:bg-white transition-colors">
                        <div class="grid grid-cols-2 gap-4">
                            <input type="text" required x-model="checkoutForm.city" placeholder="Ciudad" class="border-2 border-gray-200 px-4 py-3 text-sm focus:border-black outline-none bg-gray-50 focus:bg-white transition-colors">
                            <input type="text" required pattern="[0-9]{5}" maxlength="5" x-model="checkoutForm.zip" placeholder="C.P." class="border-2 border-gray-200 px-4 py-3 text-sm focus:border-black outline-none bg-gray-50 focus:bg-white transition-colors font-mono">
                        </div>
                        <button type="submit" class="w-full bg-black text-white py-4 mt-8 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">Siguiente</button>
                    </form>
                </div>

                <div x-show="checkoutStep === 2" style="display:none;">
                    <h3 class="text-xl font-black uppercase mb-6 tracking-tight">Registro del pedido</h3>
                    <div class="bg-gray-50 border border-gray-200 p-6"><h4 class="font-bold mb-3">Pedido pendiente de pago</h4><p class="text-sm">Al confirmar, se guardará el pedido y se reservarán las existencias. El equipo coordinará el pago y envío. No se cobra ninguna tarjeta desde esta pantalla.</p></div>

                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <button @click="checkoutStep = 1" class="border-2 border-black text-black py-4 text-xs font-bold uppercase tracking-widest hover:bg-gray-50 transition">Atrás</button>
                        <button @click="checkoutStep = 3" class="bg-black text-white py-4 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">Revisar</button>
                    </div>
                </div>

                <div x-show="checkoutStep === 3" style="display:none;">
                    <h3 class="text-xl font-black uppercase mb-6 tracking-tight">Resumen Final</h3>
                    <div class="space-y-4 mb-8">
                        <div class="border border-gray-200 bg-gray-50 p-4">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Enviar a:</p>
                                <button @click="checkoutStep = 1" class="text-xs text-blue-500 hover:underline font-bold">Editar</button>
                            </div>
                            <p class="text-sm font-bold uppercase" x-text="checkoutForm.address || 'PENDIENTE'"></p>
                            <p class="text-xs text-gray-500 uppercase mt-1" x-text="(checkoutForm.city || 'Ciudad') + ', CP: ' + (checkoutForm.zip || '00000')"></p>
                        </div>
                        <div class="border border-gray-200 bg-gray-50 p-4">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Pago:</p>
                                <button @click="checkoutStep = 2" class="text-xs text-blue-500 hover:underline font-bold">Editar</button>
                            </div>
                            <p class="text-sm font-bold uppercase" x-text="'Pendiente de pago'"></p>
                        </div>
                        <div class="border border-gray-200 p-4">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Artículos (<span x-text="cartTotalItems"></span>)</p>
                            <div class="max-h-32 overflow-y-auto custom-scroll pr-2 space-y-2 mb-4">
                                <template x-for="item in cart" :key="item.cartItemId || item.id">
                                    <div class="flex justify-between text-xs">
                                        <span class="truncate pr-2 font-medium uppercase" x-text="(item.quantity || 1) + 'x ' + item.name"></span>
                                        <span class="font-mono font-bold" x-text="'$' + ((item.price || 0) * (item.quantity || 1)).toLocaleString()"></span>
                                    </div>
                                </template>
                            </div>
                            <div class="flex justify-between items-center text-lg font-black uppercase border-t border-black pt-3">
                                <span>Total a pagar</span>
                                <span class="text-red-600" x-text="'$' + cartTotal.toLocaleString() + ' MXN'"></span>
                            </div>
                        </div>
                    </div>
                    <button :disabled="isCheckingOut" @click="processFinalPayment()" class="w-full bg-black text-white py-5 text-sm font-black uppercase tracking-widest hover:bg-gray-800 transition flex justify-center items-center">
                        <span x-show="!isCheckingOut">Registrar pedido</span>
                        <div x-show="isCheckingOut" class="flex gap-2 items-center">
                            <svg class="w-5 h-5 spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Procesando...</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="currentRoute === 'inicio'" x-cloak>
        <section class="relative h-screen overflow-hidden">
            <div class="absolute inset-0">
                <img src="https://images.unsplash.com/photo-1550639525-c97d455acf70?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/80"></div>
            </div>
            <div class="relative z-10 flex items-center justify-between px-6 md:px-12 py-6">
                <h1 class="text-2xl md:text-3xl font-black tracking-tighter text-white">HFSTUDIOS</h1>
                <button @click="navigateTo('login')" class="bg-white hover:bg-gray-200 text-black px-6 py-2 text-sm font-bold uppercase tracking-wider transition">Iniciar sesión</button>
            </div>
            <div class="relative z-10 flex flex-col items-center justify-center h-full px-6 pb-32">
                <div class="max-w-3xl text-center">
                    <h2 class="text-4xl md:text-6xl lg:text-7xl font-black uppercase tracking-tight text-white mb-6 leading-tight">ACCESO EXCLUSIVO<br>AL DROP SS26.</h2>
                    <p class="text-lg md:text-xl text-gray-300 mb-12 font-medium">Todo el catálogo con 20% de descuento por tiempo limitado.</p>
                    <form @submit.prevent="handleLandingSubmit()" class="flex flex-col sm:flex-row gap-4 max-w-2xl mx-auto">
                        <input type="email" required x-model="landingEmail" placeholder="Tu Email" class="flex-1 px-6 py-4 text-lg bg-white/90 border-2 border-white/20 focus:border-white focus:outline-none text-black">
                        <button type="submit" class="bg-black hover:bg-gray-800 text-white border-2 border-white px-8 py-4 text-lg font-black uppercase tracking-widest transition whitespace-nowrap">Entrar a la tienda <i class="fas fa-chevron-right ml-2"></i></button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'promociones-front'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-4xl mx-auto text-center mb-16">
                <h1 class="text-5xl md:text-6xl font-black uppercase tracking-tight mb-4">Promociones</h1>
                <p class="text-lg text-gray-600 uppercase tracking-wider">Aprovecha nuestras ofertas activas de temporada.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                <div class="bg-black text-white p-10 flex flex-col items-center text-center justify-center shadow-xl">
                    <h2 class="text-6xl font-black uppercase mb-2">10% OFF</h2>
                    <p class="text-sm tracking-widest uppercase mb-8 text-gray-400">En toda tu compra</p>
                    <div class="bg-white/10 px-6 py-3 border border-white/30 mb-4">
                        <p class="font-mono text-2xl font-bold tracking-widest">HF10</p>
                    </div>
                    <p class="text-xs text-gray-400">Ingresa este código visual en el carrito.</p>
                </div>
                <div class="bg-[#F0EBE0] text-black p-10 flex flex-col items-center text-center justify-center shadow-xl">
                    <h2 class="text-6xl font-black uppercase mb-2">CAMISETAS</h2>
                    <p class="text-sm tracking-widest uppercase mb-8 text-gray-600">Explora los modelos disponibles</p>
                    <button @click="navigateTo('catalogo'); selectedCategory = 'T-Shirt'" class="bg-black text-white px-8 py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">
                        Ver Colección
                    </button>
                </div>
            </div>
            <div class="max-w-md mx-auto mt-20 bg-gray-50 p-8 border border-gray-200">
                <h3 class="text-center font-bold uppercase tracking-wider mb-4">Prueba tu código aquí</h3>
                <form @submit.prevent="applyDiscount()" class="flex gap-2">
                    <input type="text" x-model="discountCode" placeholder="Código de descuento" class="flex-1 px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none uppercase">
                    <button type="submit" class="bg-black text-white px-6 py-3 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Aplicar</button>
                </form>
            </div>
        </section>
    </div>

  <div x-show="currentRoute === 'campana'" x-cloak>
        <section class="relative h-[80vh] bg-[#111] overflow-hidden flex items-center">
            <div class="absolute inset-0 w-full h-full opacity-50">
                <img src="https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?q=80&w=2000&auto=format&fit=crop" class="w-full h-full object-cover">
            </div>
            <div class="relative z-10 max-w-screen-2xl mx-auto px-6 w-full flex flex-col md:flex-row items-center gap-12">
                <div class="text-white flex-1">
                    <p class="text-red-500 font-bold uppercase tracking-widest mb-4">Nueva Colección - Producto Destacado</p>
                    <h1 class="text-6xl md:text-8xl font-black uppercase tracking-tighter leading-none mb-6">HOODIE<br>GRÁFICO<br>HF</h1>
                    <p class="text-lg text-gray-300 mb-8 max-w-md">Descubre el hoodie gráfico de la colección HF: serigrafía de alta densidad y acabado lavado.</p>
                    <button @click="navigateTo('catalogo')" class="bg-white text-black px-10 py-4 text-sm font-black uppercase tracking-widest hover:bg-gray-200 transition">
                        Comprar Ahora
                    </button>
                </div>
                <div class="hidden md:block flex-1">
                    <img src="https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800&auto=format&fit=crop" class="w-2/3 ml-auto shadow-2xl rotate-3 hover:rotate-0 transition duration-500">
                </div>
            </div>
        </section>
        <section class="py-20 bg-gray-50">
            <div class="max-w-screen-2xl mx-auto px-6">
                <h2 class="text-3xl font-black uppercase tracking-tight text-center mb-12">Lo que dice nuestra comunidad</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white p-8 border border-gray-200 shadow-sm">
                        <div class="flex text-yellow-400 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="text-gray-700 mb-6 italic">"La calidad de los materiales es brutal. Superó por completo mis expectativas. El envío fue rápido."</p>
                        <p class="font-bold uppercase text-xs tracking-wider">- Roberto M.</p>
                    </div>
                    <div class="bg-white p-8 border border-gray-200 shadow-sm">
                        <div class="flex text-yellow-400 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="text-gray-700 mb-6 italic">"Increíble el fit de las camisetas oversized. Ya me pedí tres colores diferentes."</p>
                        <p class="font-bold uppercase text-xs tracking-wider">- Ana S.</p>
                    </div>
                    <div class="bg-white p-8 border border-gray-200 shadow-sm">
                        <div class="flex text-yellow-400 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
                        <p class="text-gray-700 mb-6 italic">"Me encanta el diseño minimalista de la marca. Una estética de lujo a precio accesible."</p>
                        <p class="font-bold uppercase text-xs tracking-wider">- Diego L.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'comunidad'" x-cloak>
        <section class="max-w-screen-xl mx-auto px-6 py-20">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight mb-4">Comunidad HF</h1>
                <p class="text-gray-600 uppercase tracking-wider text-sm">Únete a la conversación. Deja tu comentario sobre nuestros drops.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-12">
                <div class="md:col-span-1">
                    <div class="bg-gray-50 p-8 border border-gray-200 sticky top-32">
                        <h3 class="font-black uppercase tracking-tight text-xl mb-6">Deja un comentario</h3>
                        <form @submit.prevent="addComment()" class="space-y-4">
                            <textarea x-model="newComment" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none" placeholder="¿Qué opinas de la nueva colección?"></textarea>
                            <button type="submit" class="w-full bg-black text-white py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">Publicar</button>
                        </form>
                    </div>
                </div>
                <div class="md:col-span-2 space-y-6">
                    <template x-for="comment in comments" :key="comment.id || comment.name">
                        <div class="bg-white p-6 border-b border-gray-200 flex gap-4">
                            <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center shrink-0">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm mb-1" x-text="'@' + (comment.name || 'Usuario')"></p>
                                <p class="text-gray-700 text-sm leading-relaxed" x-text="comment.text || 'Sin texto'"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'catalogo'" x-cloak>
        <div class="bg-black text-white p-4 text-center"><p class="text-xs md:text-sm font-black uppercase tracking-[0.2em] animate-pulse">🔥 TODA LA TIENDA TIENE 20% DE DESCUENTO APLICADO 🔥</p></div>
        <section class="max-w-screen-2xl mx-auto px-6 py-12">
            <div class="mb-12">
                <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tight mb-2">Catálogo</h2>
                <div class="mb-8 relative">
                    <input type="text" x-model="searchQuery" @input="selectedCategory = 'All'" placeholder="Buscar productos (ej. Hoodie, Negro)..." class="w-full px-6 py-4 text-sm border-2 border-gray-200 focus:border-black focus:outline-none bg-white">
                    <i class="fas fa-search absolute right-6 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button @click="selectedCategory = 'All'; searchQuery = ''" :class="selectedCategory === 'All' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Todos</button>
                    <button @click="selectedCategory = 'Promociones'; searchQuery = ''" :class="selectedCategory === 'Promociones' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-red-600 border border-red-600'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition"><i class="fas fa-tags"></i> Promociones</button>
                    <button @click="selectedCategory = 'Hoodie'; searchQuery = ''" :class="selectedCategory === 'Hoodie' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Hoodies</button>
                    <button @click="selectedCategory = 'T-Shirt'; searchQuery = ''" :class="selectedCategory === 'T-Shirt' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">T-Shirts</button>
                    <button @click="selectedCategory = 'Pants'; searchQuery = ''" :class="selectedCategory === 'Pants' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Pants</button>
                    <button @click="selectedCategory = 'Accessory'; searchQuery = ''" :class="selectedCategory === 'Accessory' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Accesorios</button>
                </div>
            </div>

            <template x-if="filteredProducts.length === 0">
                <div class="text-center py-20">
                    <svg class="mx-auto h-24 w-24 text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <h3 class="text-2xl font-black uppercase tracking-tight mb-2">No se encontraron resultados</h3>
                    <button @click="searchQuery = ''; selectedCategory = 'All'" class="inline-block bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition mt-4">Ver todos los productos</button>
                </div>
            </template>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 gap-y-10" x-show="filteredProducts.length > 0">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div class="product-card group cursor-pointer" @click="viewProductDetail(product)">
                        <div class="relative bg-[#F4F4F4] overflow-hidden mb-4">
                            <span x-show="product.badge" :class="product.badge && product.badge.includes('PROMO') ? 'bg-red-600 text-white' : 'bg-black text-white'" class="absolute top-3 left-3 text-[9px] font-bold uppercase tracking-wider px-2 py-1 z-10 shadow-md" x-text="product.badge"></span>
                            <img :src="product.image" class="w-full aspect-[3/4] object-cover group-hover:scale-105 transition duration-500">
                        </div>
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wide mb-1 truncate" x-text="product.name"></h3>
                            <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-2" x-text="product.color"></p>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-black text-red-600" x-text="'$' + (product.price || 0).toLocaleString() + ' MXN'"></p>
                                <template x-if="product.originalPrice"><p class="text-[10px] font-bold text-gray-400 line-through" x-text="'$' + product.originalPrice.toLocaleString()"></p></template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'mis-publicaciones'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-5xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight">Mis Publicaciones</h1>
                    <button @click="navigateTo('publicar')" class="bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">+ Vender Producto</button>
                </div>
                <div class="bg-white border border-gray-200 overflow-hidden shadow-sm">
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                                <tr><th class="px-6 py-4">Producto</th><th class="px-6 py-4">Precio</th><th class="px-6 py-4 text-right">Acciones</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-if="userProducts.length === 0">
                                    <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500 font-medium">No has publicado ningún producto aún.</td></tr>
                                </template>
                                <template x-for="(prod, index) in userProducts" :key="prod.id || index">
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="w-16 h-16 bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0"><img :src="prod.image" class="w-full h-full object-cover"></div>
                                                <div><p class="text-sm font-bold uppercase" x-text="prod.name || 'Sin Título'"></p><p class="text-xs text-gray-500 truncate max-w-[200px]" x-text="prod.description || '...'"></p></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><p class="text-sm font-bold" x-text="'$' + (prod.price || 0).toLocaleString()"></p></td>
                                        <td class="px-6 py-4 text-right space-x-3">
                                            <button @click="openEditModal(prod, index)" class="text-xs font-bold text-blue-600 hover:underline uppercase tracking-wider">Editar</button>
                                            <button @click="deleteUserProduct(index)" class="text-xs font-bold text-red-600 hover:underline uppercase tracking-wider">Eliminar</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="md:hidden divide-y divide-gray-100">
                        <template x-if="userProducts.length === 0">
                            <div class="py-10 text-center text-gray-500 text-sm">No hay publicaciones</div>
                        </template>
                        <template x-for="(prod, index) in userProducts" :key="'mob-'+(prod.id || index)">
                            <div class="p-4 flex gap-4">
                                <div class="w-20 h-24 bg-gray-100 border border-gray-200 flex-shrink-0"><img :src="prod.image" class="w-full h-full object-cover"></div>
                                <div class="flex-1 flex flex-col">
                                    <p class="text-sm font-bold uppercase tracking-tight" x-text="prod.name || 'Prod'"></p>
                                    <p class="font-mono font-bold text-xs mt-1" x-text="'$' + (prod.price || 0).toLocaleString()"></p>
                                    <div class="mt-auto flex justify-between border-t border-gray-100 pt-2">
                                        <button @click="openEditModal(prod, index)" class="text-[10px] font-bold text-blue-600 uppercase">Editar</button>
                                        <button @click="deleteUserProduct(index)" class="text-[10px] font-bold text-red-500 uppercase">Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'publicar'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-2xl mx-auto bg-white p-10 shadow-2xl border border-gray-100 relative">
                <button @click="navigateTo('mis-publicaciones')" class="absolute top-6 left-6 text-gray-400 hover:text-black transition"><i class="fas fa-arrow-left text-xl"></i></button>
                <div class="text-center mb-8 border-b border-gray-100 pb-4 mt-2">
                    <h1 class="text-3xl font-black uppercase tracking-tight">Publica tu producto</h1>
                </div>
                <form @submit.prevent="handlePublishSubmit()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre del artículo</label>
                        <input type="text" required x-model="publishForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm" placeholder="Ej. Jordan 1 Retro High">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Precio (MXN)</label>
                        <input type="number" required x-model="publishForm.price" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm" placeholder="2500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">URL de la Foto</label>
                        <input type="text" required x-model="publishForm.image" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm" placeholder="https://ejemplo.com/foto.jpg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Descripción</label>
                        <textarea x-model="publishForm.description" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none" placeholder="Condición, talla, detalles..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-black text-white py-4 mt-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Publicar</button>
                </form>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'subasta'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-5xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight mb-2">Subasta en Vivo</h1>
                <p class="text-sm text-gray-600 uppercase tracking-wider mb-8">Comunidad HF</p>
                <div class="grid md:grid-cols-2 gap-10">
                    <div class="bg-[#F4F4F4] p-8 border border-gray-200 relative">
                        <span class="absolute top-4 left-4 bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest px-3 py-1 animate-pulse">En vivo</span>
                        <img :src="auction.product.image" class="w-full h-auto object-cover max-h-[400px]">
                    </div>
                    <div class="flex flex-col justify-between">
                        <div>
                            <h2 class="text-2xl font-black uppercase tracking-tight mb-2" x-text="auction.product.name"></h2>
                            <div class="flex items-center gap-3 mb-6 bg-black text-white w-fit px-4 py-2"><i class="far fa-clock"></i><span class="font-mono text-lg font-bold tracking-widest" x-text="formattedTimer"></span></div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Oferta Actual</p>
                            <p class="text-4xl font-black text-green-600 mb-8" x-text="'$' + (auction.product.currentBid || 0).toLocaleString()"></p>
                            <form @submit.prevent="handleOfferSubmit()" class="mb-8 bg-gray-50 p-6 border border-gray-200">
                                <label class="block text-xs font-bold uppercase tracking-wider mb-3">Hacer Oferta</label>
                                <div class="flex gap-2">
                                    <span class="bg-gray-200 flex items-center px-4 font-bold text-gray-600">$</span>
                                    <input type="number" required min="0.01" step="0.01" x-model="auction.newOffer" class="flex-1 px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none font-bold" placeholder="Monto">
                                    <button type="submit" class="bg-black text-white px-6 font-bold uppercase tracking-widest hover:bg-gray-800 transition">Ofertar</button>
                                </div>
                                <p class="text-[10px] text-gray-500 mt-2">* La oferta debe superar el importe actual y la subasta debe estar abierta.</p>
                            </form>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider mb-3 border-b pb-2">Historial de Ofertas</h3>
                            <ul class="space-y-2 max-h-40 overflow-y-auto custom-scroll">
                                <template x-for="offer in auction.offers" :key="offer.amount || offer.user">
                                    <li class="flex justify-between items-center text-sm p-2 bg-gray-50"><span class="font-semibold text-gray-700" x-text="offer.user || 'Anónimo'"></span><span class="font-black" x-text="'$' + (offer.amount || 0).toLocaleString()"></span></li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'admin' && isAdmin" x-cloak class="min-h-screen bg-gray-50 flex flex-col md:flex-row">
        <aside class="w-full md:w-64 bg-white border-r border-gray-200 flex flex-col shrink-0">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-black uppercase tracking-tighter">HF. ADMIN</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-1" x-text="'HOLA, ' + (user.name || 'ADMIN')"></p>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <button @click="adminTab = 'pedidos'" :class="adminTab === 'pedidos' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-box w-5"></i> Pedidos</button>
                <button @click="adminTab = 'productos'" :class="adminTab === 'productos' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-tags w-5"></i> Productos</button>
                <a href="/scm" class="block px-4 py-3 text-xs font-bold uppercase tracking-widest">Inventario SCM</a>
                <button @click="adminTab = 'clientes'" :class="adminTab === 'clientes' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-users w-5"></i> Clientes</button>
                <button @click="adminTab = 'facturas'" :class="adminTab === 'facturas' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-file-invoice w-5"></i> Comprobantes</button>
            </nav>
            <div class="p-4 border-t border-gray-200 space-y-2">
                <button @click="navigateTo('catalogo')" class="w-full bg-white border border-gray-300 text-black px-4 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-100 transition text-center">Ver Tienda</button>
                <button @click="handleLogout()" class="w-full bg-red-600 text-white px-4 py-3 text-xs font-bold uppercase tracking-widest hover:bg-red-700 transition text-center">Cerrar Sesión</button>
            </div>
        </aside>

        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <div class="mb-8 flex justify-between items-end border-b border-gray-200 pb-4">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight" x-text="adminTab === 'facturas' ? 'Comprobantes' : adminTab"></h1>
                <button x-show="adminTab === 'productos'" @click="openProductEditor()" class="bg-black text-white px-6 py-3 text-xs font-bold">+ Nuevo producto</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" x-show="adminTab === 'pedidos' || adminTab === 'clientes' || adminTab === 'facturas'">
                <div class="bg-white p-6 border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Valor de pedidos</p>
                    <p class="text-3xl font-black text-black" x-text="adminStats.sales"></p>
                </div>
                <div class="bg-white p-6 border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Pedidos Activos</p>
                    <p class="text-3xl font-black text-black" x-text="adminStats.orders"></p>
                </div>
                <div class="bg-white p-6 border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Clientes CRM</p>
                    <p class="text-3xl font-black text-black" x-text="adminStats.users"></p>
                </div>
            </div>

            <div x-show="adminTab === 'pedidos'" class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">ID Pedido</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Fecha</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="pedido in adminOrdersList" :key="pedido.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-mono text-sm font-bold" x-text="pedido.id"></td>
                                    <td class="px-6 py-4 text-sm uppercase font-semibold" x-text="pedido.user"></td>
                                    <td class="px-6 py-4 text-xs text-gray-500 font-mono" x-text="pedido.date"></td>
                                    <td class="px-6 py-4">
                                        <select x-show="pedido.status === 'Pendiente' || pedido.status === 'Enviado'" @change="changeOrderStatus(pedido, $event.target.value); $event.target.value = ''" class="text-xs border p-2"><option value="">Cambiar estado</option><option x-show="pedido.status === 'Pendiente'" value="Enviado">Enviado</option><option x-show="pedido.status === 'Enviado'" value="Entregado">Entregado</option><option x-show="pedido.status === 'Pendiente'" value="Cancelado">Cancelado</option></select>
                                        <span :class="pedido.status === 'Entregado' ? 'bg-green-100 text-green-700' : (pedido.status === 'Enviado' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')" class="px-2 py-1 text-[9px] font-bold uppercase tracking-widest rounded-sm" x-text="pedido.status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-sm" x-text="'$' + pedido.total.toLocaleString()"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="adminTab === 'productos'" class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Producto</th>
                                <th class="px-6 py-4">Categoría</th>
                                <th class="px-6 py-4">Precio</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="prod in allProducts" :key="prod.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <img :src="prod.image" class="w-12 h-16 object-cover bg-gray-100 border border-gray-200">
                                            <span class="text-sm font-bold uppercase" x-text="prod.name"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 uppercase tracking-widest font-bold" x-text="prod.category"></td>
                                    <td class="px-6 py-4 font-mono font-bold text-sm text-red-600" x-text="'$' + prod.price.toLocaleString()"></td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="openProductEditor(prod)" class="text-[10px] font-bold text-blue-600 uppercase tracking-wider hover:underline">Editar</button>
                                        <button @click="toggleProduct(prod)" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider" x-text="prod.active ? 'Ocultar' : 'Mostrar'"></button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="adminTab === 'clientes'" class="bg-white p-8 border border-gray-200"><h2 class="text-2xl font-black mb-4">Gestión CRM</h2><p class="mb-6">Clientes, interacciones, etapas, evaluaciones y métricas centralizadas.</p><a href="/crm" class="bg-black text-white px-6 py-3 inline-block">Abrir CRM</a></div>

            <div x-show="adminTab === 'facturas'" class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Folio del pedido</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Pedido Relacionado</th>
                                <th class="px-6 py-4">Fecha de registro</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-right">Monto</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="factura in adminInvoicesList" :key="factura.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-mono text-sm font-bold" x-text="factura.id"></td>
                                    <td class="px-6 py-4 text-sm uppercase font-semibold" x-text="factura.user"></td>
                                    <td class="px-6 py-4 text-xs text-gray-500 font-mono" x-text="factura.orderId"></td>
                                    <td class="px-6 py-4 text-xs text-gray-500 font-mono" x-text="factura.date"></td>
                                    <td class="px-6 py-4">
                                        <span :class="factura.status === 'Pagada' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="px-2 py-1 text-[9px] font-bold uppercase tracking-widest rounded-sm" x-text="factura.status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-sm" x-text="'$' + factura.total.toLocaleString()"></td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="downloadInvoice(factura)" class="text-[10px] font-bold text-blue-600 uppercase tracking-wider hover:underline"><i class="fas fa-file-pdf mr-1"></i> Descargar PDF</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <div x-show="currentRoute === 'perfil'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-5xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight mb-8 border-b border-gray-100 pb-4">Mi Perfil</h1>
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="bg-gray-50 p-8 border border-gray-100 lg:col-span-1 h-fit">
                        <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center text-2xl font-black mb-6">
                            <span x-text="(user.name || 'U').charAt(0).toUpperCase()"></span>
                        </div>
                        <h2 class="text-xl font-black uppercase tracking-tight mb-6">Mis Datos</h2>
                        <div class="space-y-4">
                            <div><p class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Nombre</p><p class="text-base font-semibold uppercase" x-text="user.name || 'Invitado'"></p></div>
                            <div><p class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Email</p><p class="text-base font-semibold" x-text="user.email || 'N/A'"></p></div>
                        </div>
                        <button @click="handleLogout()" class="mt-8 w-full border-2 border-black text-black py-3 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">Cerrar Sesión</button>
                    </div>

                    <div class="bg-white p-8 border border-gray-100 lg:col-span-2">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                            <h2 class="text-xl font-black uppercase tracking-tight">Mis Compras</h2>
                            <span class="text-[10px] font-bold bg-gray-100 text-gray-600 px-2 py-1 uppercase tracking-widest" x-text="userOrders.length + ' Órdenes'"></span>
                        </div>
                        <div class="space-y-4 max-h-[500px] overflow-y-auto custom-scroll pr-2">
                            <template x-for="order in userOrders" :key="order.id">
                                <div class="bg-gray-50 p-5 border border-gray-200 flex flex-col md:flex-row md:justify-between md:items-center hover:border-black transition gap-4">
                                    <div>
                                        <p class="font-bold font-mono text-sm mb-1" x-text="order.id"></p>
                                        <div class="flex items-center gap-2 text-[10px] text-gray-500 uppercase tracking-widest font-bold">
                                            <span x-text="order.date"></span><span>•</span>
                                            <span :class="order.status === 'Entregado' ? 'text-green-600' : 'text-blue-600'" x-text="order.status || 'Procesando'"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between md:justify-end gap-6 w-full md:w-auto mt-2 md:mt-0 border-t md:border-t-0 border-gray-200 pt-3 md:pt-0">
                                        <p class="font-black font-mono text-lg" x-text="'$' + (order.total || 0).toLocaleString()"></p>
                                        <button @click="openInvoice(order)" class="text-[10px] font-bold uppercase tracking-widest text-black border border-black px-4 py-2 hover:bg-black hover:text-white transition whitespace-nowrap">
                                            Ver Ticket
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="userOrders.length === 0">
                                <div class="text-center py-16 bg-gray-50 border border-gray-100">
                                    <i class="fas fa-shopping-bag text-4xl text-gray-200 mb-4"></i>
                                    <p class="text-gray-500 text-xs uppercase tracking-widest font-bold">Aún no tienes pedidos.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'registro'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-md mx-auto bg-white p-10 shadow-2xl border border-gray-100">
                <h1 class="text-3xl font-black uppercase tracking-tight mb-8 text-center">Crear Cuenta</h1>
                <form @submit.prevent="handleRegistro()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre</label>
                        <input type="text" required x-model="registroForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Email</label>
                        <input type="email" required x-model="registroForm.email" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Contraseña</label>
                        <input type="password" required minlength="12" maxlength="128" x-model="registroForm.password" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                    </div>
                    <button type="submit" class="w-full bg-black text-white py-4 mt-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Registrarme</button>
                </form>
                <div class="mt-6 text-center border-t border-gray-100 pt-6">
                    <p class="text-[10px] uppercase font-bold text-gray-500 tracking-widest">¿Ya tienes cuenta? <button @click="navigateTo('login')" class="text-black font-black hover:underline ml-1">Inicia sesión</button></p>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'login'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-md mx-auto bg-white p-10 shadow-2xl border border-gray-100">
                <h1 class="text-3xl font-black uppercase tracking-tight mb-8 text-center">Iniciar Sesión</h1>
                <p class="text-xs text-gray-500 mb-6">Accede con tu cuenta. Los permisos se asignan por el equipo.</p>
                <form @submit.prevent="handleLogin()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Email</label>
                        <input type="email" required x-model="loginForm.email" :disabled="isLoggingIn" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm disabled:opacity-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Contraseña</label>
                        <input type="password" required x-model="loginForm.password" :disabled="isLoggingIn" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm disabled:opacity-50">
                    </div>
                    <button type="submit" :disabled="isLoggingIn" class="w-full bg-black text-white py-4 mt-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition disabled:opacity-70 flex items-center justify-center gap-2">
                        <span x-show="!isLoggingIn">Entrar</span>
                        <div x-show="isLoggingIn" class="flex gap-2 items-center"><svg class="w-4 h-4 spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Cargando...</span></div>
                    </button>
                </form>
                <div class="mt-6 text-center border-t border-gray-100 pt-6">
                    <p class="text-[10px] uppercase font-bold text-gray-500 tracking-widest">¿No tienes cuenta? <button @click="navigateTo('registro')" class="text-black font-black hover:underline ml-1">Regístrate</button></p>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'contacto'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-5xl md:text-6xl font-black uppercase tracking-tight mb-8">Contacto</h1>
                <form @submit.prevent="handleContactSubmit()" class="space-y-6 mb-12">
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre</label><input type="text" required x-model="contactForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Email</label><input type="email" required x-model="contactForm.email" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Mensaje</label><textarea x-model="contactForm.message" rows="5" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none"></textarea></div>
                    <button type="submit" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Enviar Mensaje</button>
                </form>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'nosotros'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-24">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-5xl md:text-7xl font-black uppercase tracking-tighter mb-10">Nosotros</h1>
                <div class="space-y-6 text-gray-700 leading-relaxed mb-12 font-medium">
                    <p class="text-xl font-bold border-l-4 border-black pl-4 py-2 text-black">Fundada en 2026, HFSTUDIOS representa la intersección del lujo y la cultura urbana.</p>
                    <h2 class="text-2xl font-black uppercase tracking-tighter mt-12 mb-4 border-b border-gray-100 pb-2 text-black">Misión</h2>
                    <p>Ofrecemos streetwear de alta calidad diseñado para individuos que se niegan a mezclarse con la multitud. Cada pieza es una declaración construida con atención obsesiva a los detalles.</p>
                    <h2 class="text-2xl font-black uppercase tracking-tighter mt-12 mb-4 border-b border-gray-100 pb-2 text-black">Horarios</h2>
                    <div class="bg-gray-50 border border-gray-200 p-6 shadow-sm w-fit">
                        <p class="text-xs font-bold uppercase tracking-widest text-black">Lunes a Sábado <span class="text-gray-500 font-medium ml-4">10:00 - 20:00</span></p>
                    </div>
                </div>
                <div class="mt-16">
                    <h2 class="text-2xl font-black uppercase tracking-tighter mb-6 text-black">Ubicación</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-4">Aguascalientes, México</p>
                    <div class="aspect-video bg-gray-100 border border-gray-200 flex items-center justify-center shadow-sm opacity-60">
                        <div class="text-center"><i class="fas fa-map-marker-alt text-4xl mb-3 text-black"></i><p class="text-[10px] font-bold uppercase tracking-widest">Mapa Interactivo</p></div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer x-show="currentRoute !== 'inicio' && currentRoute !== 'admin'" class="bg-black text-white py-16 mt-auto">
        <div class="max-w-screen-2xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div>
                    <h3 class="text-3xl font-black mb-4 tracking-tighter">HFSTUDIOS</h3>
                    <p class="text-xs text-gray-400 leading-relaxed font-medium">Streetwear premium para quienes se atreven a ser diferentes. Aguascalientes, MX.</p>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-6 text-gray-500">Tienda</h4>
                    <ul class="space-y-3 text-xs font-bold uppercase tracking-wider text-gray-300">
                        <li><button @click="navigateTo('catalogo')" class="hover:text-white transition">Catálogo</button></li>
                        <li><button @click="navigateTo('campana')" class="hover:text-white transition">Campaña</button></li>
                        <li><button @click="navigateTo('promociones-front')" class="hover:text-white transition">Promociones</button></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-6 text-gray-500">Comunidad</h4>
                    <ul class="space-y-3 text-xs font-bold uppercase tracking-wider text-gray-300">
                        <li><button @click="navigateTo('comunidad')" class="hover:text-white transition">Reseñas</button></li>
                        <li><button @click="navigateTo('nosotros')" class="hover:text-white transition">Nosotros</button></li>
                        <li><button @click="navigateTo('subasta')" class="hover:text-white transition text-red-500">Subastas (C2C)</button></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-6 text-gray-500">Newsletter</h4>
                    <form @submit.prevent="subscribeNewsletter()" class="flex shadow-md border border-white/20">
                        <input type="email" required x-model="newsletterEmail" placeholder="TU EMAIL" class="flex-1 bg-white/5 px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-white focus:outline-none focus:bg-white/10 transition">
                        <button type="submit" class="bg-white text-black px-6 py-3 text-sm font-black hover:bg-gray-200 transition">→</button>
                    </form>
                </div>
            </div>
            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">© 2026 HF Studios. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>


    <div x-show="productEditorOpen" x-cloak class="fixed inset-0 z-[130] overflow-y-auto bg-black/70 p-4">
        <form @submit.prevent="saveProduct()" class="bg-white max-w-xl mx-auto my-8 p-8 space-y-4">
            <div class="flex justify-between"><h2 class="font-black text-xl">Producto de catálogo</h2><button type="button" @click="productEditorOpen=false">Cerrar</button></div>
            <label class="block">Nombre<input required x-model="productForm.name" maxlength="120" class="w-full border p-2"></label>
            <label class="block">Color<input x-model="productForm.color" class="w-full border p-2"></label>
            <label class="block">Categoría<select x-model="productForm.category" class="w-full border p-2"><option>Hoodie</option><option>T-Shirt</option><option>Pants</option><option>Accessory</option></select></label>
            <div class="grid grid-cols-2 gap-4"><label>Precio MXN<input required type="number" min="0.01" step="0.01" x-model="productForm.price" class="w-full border p-2"></label><label>Precio original<input type="number" min="0.01" step="0.01" x-model="productForm.originalPrice" class="w-full border p-2"></label></div>
            <label class="block">Existencias<input required type="number" min="0" step="1" x-model="productForm.stock" class="w-full border p-2"></label>
            <label class="block">URL de imagen<input required type="url" x-model="productForm.image" class="w-full border p-2"></label>
            <label class="block">Etiqueta<input x-model="productForm.badge" class="w-full border p-2"></label>
            <label class="block">Descripción<textarea required x-model="productForm.description" class="w-full border p-2"></textarea></label>
            <label><input type="checkbox" x-model="productForm.active"> Visible en catálogo</label>
            <button :disabled="isSaving" class="w-full bg-black text-white p-3">Guardar producto</button>
        </form>
    </div>

    <script src="/js/http.js?v={{ filemtime(public_path('js/http.js')) }}"></script>
    <script src="/js/storefront.js?v={{ filemtime(public_path('js/storefront.js')) }}"></script>
</body>
</html>
```

## routes/web.php

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\ErpController;
use App\Http\Controllers\ScmController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/login', fn () => redirect('/?vista=login'))->name('login');
Route::get('/sesion', [AuthController::class, 'session']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/tienda/productos', [StoreController::class, 'products']);
Route::get('/tienda/comentarios', [StoreController::class, 'comments']);
Route::get('/tienda/subasta', [StoreController::class, 'auction']);
Route::post('/tienda/contacto', [StoreController::class, 'contact'])->middleware('throttle:5,1');
Route::post('/tienda/suscripciones', [StoreController::class, 'subscribe'])->middleware('throttle:5,1');
Route::middleware('auth')->group(function () {
    Route::get('/tienda/pedidos', [StoreController::class, 'orders']);
    Route::post('/tienda/pedidos', [StoreController::class, 'checkout'])->middleware('throttle:20,1');
    Route::get('/tienda/publicaciones', [StoreController::class, 'publications']);
    Route::post('/tienda/publicaciones', [StoreController::class, 'savePublication']);
    Route::put('/tienda/publicaciones/{publication}', [StoreController::class, 'savePublication']);
    Route::delete('/tienda/publicaciones/{publication}', [StoreController::class, 'deletePublication']);
    Route::post('/tienda/comentarios', [StoreController::class, 'comment'])->middleware('throttle:10,1');
    Route::post('/tienda/subastas/{auction}/ofertas', [StoreController::class, 'bid'])->middleware('throttle:20,1');
});
Route::middleware(['auth', 'permission:crm'])->group(function () {
    Route::view('/crm', 'crm.index');
    Route::apiResource('clientes', CrmController::class)->parameters(['clientes' => 'cliente'])->except('destroy');
    Route::put('/clientes/{cliente}/etapa', [CrmController::class, 'etapa']);
    Route::post('/interacciones', [CrmController::class, 'interaction']);
    Route::get('/clientes/{cliente}/interacciones', [CrmController::class, 'history']);
    Route::post('/clientes/{cliente}/evaluaciones', [CrmController::class, 'evaluate']);
    Route::get('/metricas', [CrmController::class, 'metrics']);
    Route::get('/mi-actividad', [CrmController::class, 'activity']);
    Route::get('/contactos', [CrmController::class, 'contacts']);
    Route::post('/contactos/{id}/registrar', [CrmController::class, 'handleContact']);
});
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::delete('/clientes/{cliente}', [CrmController::class, 'destroy']);
    Route::get('/usuarios', [CrmController::class, 'users']);
    Route::post('/usuarios', [CrmController::class, 'createUser']);
    Route::get('/tienda/estadisticas', [StoreController::class, 'stats']);
    Route::post('/tienda/productos', [StoreController::class, 'saveProduct']);
    Route::put('/tienda/productos/{product}', [StoreController::class, 'saveProduct']);
    Route::patch('/tienda/productos/{product}/visibilidad', [StoreController::class, 'toggleProduct']);
    Route::put('/tienda/pedidos/{order}/estado', [StoreController::class, 'orderStatus']);
});

Route::middleware(['auth', 'permission:scm.read'])->group(function () {
    Route::view('/scm', 'scm.index');
    Route::get('/productos', [ScmController::class, 'products']);
    Route::get('/productos/{producto}', [ScmController::class, 'product'])->withTrashed();
    Route::get('/productos/{producto}/movimientos', [ScmController::class, 'history'])->withTrashed();
    Route::get('/proveedores', [ScmController::class, 'suppliers']);
    Route::get('/pedidos', [ScmController::class, 'orders']);
    Route::get('/scm/estado', [ScmController::class, 'state']);
    Route::get('/scm/reportes', [ScmController::class, 'reports']);
    Route::get('/scm/reportes/csv', [ScmController::class, 'export']);
});
Route::middleware(['auth', 'permission:scm.operate'])->group(function () {
    Route::post('/inventario/movimiento', [ScmController::class, 'movement']);
    Route::post('/pedidos', [ScmController::class, 'createOrder']);
    Route::put('/pedidos/{pedido}/estado', [ScmController::class, 'orderStatus']);
});
Route::middleware(['auth', 'permission:scm.manage'])->group(function () {
    Route::post('/productos', [ScmController::class, 'saveProduct']);
    Route::put('/productos/{producto}', [ScmController::class, 'saveProduct']);
    Route::delete('/productos/{producto}', [ScmController::class, 'deleteProduct']);
    Route::put('/productos/{producto}/restaurar', [ScmController::class, 'restoreProduct'])->withTrashed();
    Route::put('/productos/{producto}/estrategia', [ScmController::class, 'strategy']);
    Route::post('/proveedores', [ScmController::class, 'saveSupplier']);
    Route::put('/proveedores/{proveedor}', [ScmController::class, 'saveSupplier']);
    Route::delete('/proveedores/{proveedor}', [ScmController::class, 'deleteSupplier']);
});
Route::put('/scm/nivel', [ScmController::class, 'maturity'])->middleware(['auth', 'role:admin']);

Route::middleware(['auth', 'permission:erp.read'])->group(function () {
    Route::view('/erp', 'erp.index');
    Route::get('/ordenes', [ErpController::class, 'index']);
    Route::get('/ordenes/{orden}', [ErpController::class, 'show'])->whereNumber('orden');
    Route::put('/ordenes/{orden}/estado', [ErpController::class, 'state'])->whereNumber('orden');
    Route::get('/erp/metricas', [ErpController::class, 'metrics']);
    Route::get('/erp/estado', [ErpController::class, 'implementation']);
    Route::get('/erp/recursos', [ErpController::class, 'resources']);
    Route::get('/erp/reportes', [ErpController::class, 'reports']);
    Route::get('/erp/reportes/csv', [ErpController::class, 'export']);
    Route::get('/erp/reportes/{reporte}/csv', [ErpController::class, 'exportReport'])->whereNumber('reporte');
    Route::post('/erp/reportes', [ErpController::class, 'generateReport']);
});
Route::middleware(['auth', 'permission:erp.write'])->group(function () {
    Route::post('/ordenes', [ErpController::class, 'store']);
    Route::post('/ordenes/procesar', [ErpController::class, 'process']);
});
Route::middleware(['auth', 'permission:erp.resources'])->group(function () {
    Route::post('/erp/recursos', [ErpController::class, 'saveResource']);
    Route::put('/erp/recursos/{recurso}', [ErpController::class, 'saveResource']);
    Route::get('/erp/responsables', [ErpController::class, 'resourceOwners']);
});
Route::middleware(['auth', 'permission:erp.admin'])->group(function () {
    Route::put('/erp/estado', [ErpController::class, 'updateImplementation']);
});
Route::middleware(['auth', 'permission:users'])->group(function () {
    Route::put('/usuarios/rol', [UserRoleController::class, 'update']);
    Route::post('/usuarios/rol', [UserRoleController::class, 'update']);
});
```

## tests/Feature/ErpTest.php

```php
<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Product;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ErpTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function client(array $extra = []): Cliente
    {
        return Cliente::create(['nombre' => 'Cliente ERP', 'correo' => Str::uuid().'@example.test', 'estado' => 'activo', 'etapa_crm' => 'Prospecto', ...$extra]);
    }

    private function product(array $extra = []): Product
    {
        return Product::create(['name' => 'Prenda ERP', 'category' => 'Hoodie', 'description' => 'Prenda para integración ERP', 'image' => 'https://example.test/p.jpg',
            'price_cents' => 10090, 'stock' => 10, 'active' => true, 'stock_minimo' => 2, 'stock_objetivo' => 12, 'costo_unitario_cents' => 4000, 'estrategia_logistica' => 'PULL', ...$extra]);
    }

    private function payload(Cliente $c, Product $p, int $quantity = 2): array
    {
        return ['cliente_id' => $c->id, 'request_key' => (string) Str::uuid(),
            'productos' => [['producto_id' => $p->id, 'cantidad' => $quantity, 'precio_unitario_cents' => $p->price_cents]]];
    }

    public function test_draft_uses_catalog_prices_without_reducing_or_reserving_stock(): void
    {
        $this->actingAs($this->staff('ventas'));
        $c = $this->client();
        $p = $this->product();
        $d = [...$this->payload($c, $p), 'total' => .01, 'usuario_id' => 999, 'estado' => 'completada'];
        $r = $this->postJson('/ordenes', $d)->assertCreated()->assertJsonPath('estado', 'pendiente')->assertJsonPath('total', 201.8);
        $r->assertJsonPath('cliente.id', $c->id)->assertJsonCount(1, 'productos')->assertJsonCount(1, 'historial')->assertJsonMissingPath('payload_hash');
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertDatabaseCount('procesos_internos', 0);
    }

    public function test_process_integrates_crm_scm_erp_and_replay_does_not_duplicate_anything(): void
    {
        $user = $this->staff('ventas');
        $this->actingAs($user);
        $c = $this->client();
        $p = $this->product();
        $d = $this->payload($c, $p);
        $id = $this->postJson('/ordenes/procesar', $d)->assertCreated()->assertJsonPath('estado', 'procesada')->assertJsonPath('total', 201.8)->assertJsonCount(1, 'movimientos')->assertJsonCount(1, 'procesos')->json('id');
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertSame('Activo', $c->fresh()->etapa_crm);
        $this->assertDatabaseHas('movimientos_inventario', ['orden_empresarial_id' => $id, 'cantidad' => 2, 'tipo' => 'salida', 'usuario_id' => $user->id]);
        $this->postJson('/ordenes/procesar', $d)->assertCreated()->assertJsonPath('id', $id);
        $this->postJson('/ordenes/procesar', ['orden_id' => $id])->assertOk()->assertJsonPath('id', $id);
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'procesada'])->assertOk();
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('procesos_internos', 1);
        $this->assertDatabaseCount('orden_historial', 2);
        $this->assertDatabaseCount('movimientos_inventario', 2);
    }

    public function test_failed_multi_product_order_rolls_back_all_tables_and_stock(): void
    {
        $this->actingAs($this->staff());
        $c = $this->client();
        $p = $this->product();
        $empty = $this->product(['stock' => 0]);
        $d = $this->payload($c, $p);
        $d['productos'][] = ['producto_id' => $empty->id, 'cantidad' => 1];
        $this->postJson('/ordenes/procesar', $d)->assertUnprocessable();
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('orden_productos', 0);
        $this->assertDatabaseCount('orden_historial', 0);
        $this->assertDatabaseCount('procesos_internos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertSame('Prospecto', $c->fresh()->etapa_crm);
    }

    public function test_pending_order_rechecks_live_stock_and_keeps_price_snapshot(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product();
        $d = $this->payload($this->client(), $p, 10);
        $id = $this->postJson('/ordenes', $d)->assertCreated()->json('id');
        $p->update(['stock' => 9, 'price_cents' => 20000]);
        $this->postJson('/ordenes/procesar', ['orden_id' => $id])->assertUnprocessable();
        $this->assertDatabaseHas('ordenes_empresariales', ['id' => $id, 'estado' => 'pendiente', 'total_cents' => 100900]);
        $p->update(['stock' => 10]);
        $this->postJson('/ordenes/procesar', ['orden_id' => $id])->assertOk()->assertJsonPath('total', 1009);
        $this->assertSame(0, $p->fresh()->stock);
    }

    public function test_logistics_confirms_delivery_without_a_second_stock_reduction(): void
    {
        $this->actingAs($this->staff('ventas'));
        $p = $this->product();
        $id = $this->postJson('/ordenes/procesar', $this->payload($this->client(), $p))->json('id');
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'completada'])->assertForbidden();
        $this->actingAs($this->staff('logistica'));
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'completada'])->assertOk()->assertJsonPath('estado', 'completada');
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'completada'])->assertOk();
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertDatabaseCount('orden_historial', 3);
    }

    public function test_cancellation_restores_stock_once_and_removes_sale_from_both_dashboards(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product();
        $id = $this->postJson('/ordenes/procesar', $this->payload($this->client(), $p))->json('id');
        $this->getJson('/erp/metricas')->assertJsonPath('ventas_totales', 201.8)->assertJsonPath('productos_vendidos', 2);
        $this->getJson('/scm/reportes')->assertJsonPath('productos_mas_vendidos.0.unidades', 2);
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada', 'motivo' => 'Prueba de devolución'])->assertOk()->assertJsonPath('estado', 'cancelada');
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertOk();
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 3);
        $this->getJson('/erp/metricas')->assertJsonPath('ventas_totales', 0)->assertJsonPath('productos_vendidos', 0)->assertJsonPath('ordenes_canceladas', 1);
        $this->getJson('/scm/reportes')->assertJsonCount(0, 'productos_mas_vendidos');
        $this->postJson('/ordenes/procesar', ['orden_id' => $id])->assertConflict();
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'completada'])->assertConflict();
    }

    public function test_sales_can_cancel_pending_but_not_processed_orders(): void
    {
        $this->actingAs($this->staff('ventas'));
        $p = $this->product();
        $c = $this->client();
        $draft = $this->postJson('/ordenes', $this->payload($c, $p))->json('id');
        $this->putJson('/ordenes/'.$draft.'/estado', ['estado' => 'cancelada'])->assertOk();
        $done = $this->postJson('/ordenes/procesar', $this->payload($c, $p))->json('id');
        $this->putJson('/ordenes/'.$done.'/estado', ['estado' => 'cancelada'])->assertForbidden();
        $this->assertSame(8, $p->fresh()->stock);
    }

    public function test_erp_sale_triggers_existing_push_rule_and_pull_does_not(): void
    {
        $this->actingAs($this->staff());
        $supplier = Proveedor::create(['nombre' => 'Proveedor', 'contacto' => 'Contacto', 'correo' => 'scm@example.test', 'telefono' => '4491234567']);
        $push = $this->product(['stock' => 3, 'estrategia_logistica' => 'PUSH', 'proveedor_id' => $supplier->id]);
        $pull = $this->product(['stock' => 3]);
        $d = $this->payload($this->client(), $push);
        $d['productos'][] = ['producto_id' => $pull->id, 'cantidad' => 2];
        $this->postJson('/ordenes/procesar', $d)->assertCreated();
        $this->assertDatabaseHas('pedidos_scm', ['producto_id' => $push->id, 'tipo' => 'reposicion', 'cantidad' => 11, 'automatico' => true]);
        $this->assertDatabaseMissing('pedidos_scm', ['producto_id' => $pull->id]);
        $this->postJson('/ordenes/procesar', $d)->assertCreated();
        $this->assertDatabaseCount('pedidos_scm', 1);
    }

    public function test_validation_rejects_tampered_price_duplicate_products_and_inactive_customer(): void
    {
        $this->actingAs($this->staff());
        $c = $this->client();
        $p = $this->product();
        $d = $this->payload($c, $p);
        $d['productos'][0]['precio_unitario_cents'] = 1;
        $this->postJson('/ordenes/procesar', $d)->assertUnprocessable();
        $d = $this->payload($c, $p);
        $d['productos'][] = $d['productos'][0];
        $this->postJson('/ordenes/procesar', $d)->assertUnprocessable();
        $c->update(['estado' => 'inactivo']);
        $this->postJson('/ordenes/procesar', $this->payload($c, $p))->assertUnprocessable();
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertSame(10, $p->fresh()->stock);
    }

    public function test_reused_key_with_different_order_is_rejected(): void
    {
        $this->actingAs($this->staff());
        $d = $this->payload($this->client(), $this->product());
        $this->postJson('/ordenes/procesar', $d)->assertCreated();
        $d['productos'][0]['cantidad'] = 3;
        $this->postJson('/ordenes/procesar', $d)->assertConflict();
        $this->assertDatabaseCount('ordenes_empresariales', 1);
    }

    public function test_existing_customer_cannot_be_deleted_and_pending_product_cannot_be_archived(): void
    {
        $this->actingAs($this->staff());
        $c = $this->client();
        $p = $this->product(['stock' => 0]);
        $id = $this->postJson('/ordenes', $this->payload($c, $p))->assertCreated()->json('id');
        $this->deleteJson('/clientes/'.$c->id)->assertConflict();
        $this->deleteJson('/productos/'.$p->id)->assertConflict();
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertOk();
        $this->deleteJson('/productos/'.$p->id)->assertNoContent();
        $this->getJson('/ordenes/'.$id)->assertOk()->assertJsonPath('productos.0.nombre', 'Prenda ERP');
    }

    public function test_search_pagination_and_detail_expose_history(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product();
        $c = $this->client(['nombre' => 'Cliente buscable']);
        for ($n = 0; $n < 16; $n++) {
            $this->postJson('/ordenes', $this->payload($c, $p))->assertCreated();
        }
        $this->getJson('/ordenes?q=buscable&page=2&estado=pendiente')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('total', 16);
        $this->getJson('/ordenes?q=inexistente')->assertJsonCount(0, 'data');
        $this->getJson('/ordenes/99999')->assertNotFound();
    }

    public function test_archived_product_can_be_restored_before_cancelling_a_fulfilled_sale(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product();
        $id = $this->postJson('/ordenes/procesar', $this->payload($this->client(), $p, 10))->assertCreated()->json('id');
        $this->deleteJson('/productos/'.$p->id)->assertNoContent();
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertConflict();
        $this->putJson('/productos/'.$p->id.'/restaurar')->assertOk();
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertOk();
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertNull($p->fresh()->deleted_at);
    }

    public function test_maturity_is_persistent_and_requires_real_operational_evidence(): void
    {
        $this->actingAs($this->staff());
        $all = array_fill_keys(['clientes', 'inventario', 'ordenes', 'movimientos', 'reportes', 'procesos'], true);
        $this->putJson('/erp/estado', ['nivel' => 'Optimizado', 'checklist' => $all])->assertUnprocessable();
        $this->postJson('/ordenes/procesar', $this->payload($this->client(), $this->product()))->assertCreated();
        $this->postJson('/erp/reportes')->assertCreated();
        $this->putJson('/erp/estado', ['nivel' => 'Optimizado', 'checklist' => $all])->assertOk()->assertJsonPath('progreso', 100);
        $this->getJson('/erp/estado')->assertJsonPath('nivel', 'Optimizado')->assertJsonPath('checklist.movimientos', true);
        $this->assertDatabaseHas('estado_erp', ['id' => 1, 'nivel' => 'Optimizado']);
    }

    public function test_report_snapshot_keeps_original_figures_after_cancellation_and_csv_is_safe(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product(['name' => '=HYPERLINK("test")']);
        $id = $this->postJson('/ordenes/procesar', $this->payload($this->client(), $p))->json('id');
        $report = $this->postJson('/erp/reportes')->assertCreated()->assertJsonPath('resumen.ventas_totales', 201.8)->json('id');
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertOk();
        $this->getJson('/erp/reportes')->assertJsonPath('data.0.resumen.ventas_totales', 201.8);
        $csv = $this->get('/erp/reportes/'.$report.'/csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('201.8', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->get('/erp/reportes/csv')->assertOk();
    }

    public function test_resources_are_managed_by_logistics_and_readable_by_sales(): void
    {
        $owner = $this->staff('logistica');
        $this->actingAs($owner);
        $d = ['nombre' => 'Estación de empaque', 'tipo' => 'fisico', 'descripcion' => 'Mesa y herramientas', 'disponible' => true, 'responsable_id' => $owner->id];
        $id = $this->postJson('/erp/recursos', $d)->assertCreated()->json('id');
        $this->putJson('/erp/recursos/'.$id, [...$d, 'disponible' => false])->assertOk()->assertJsonPath('disponible', false);
        $this->getJson('/erp/responsables')->assertOk();
        $this->actingAs($this->staff('ventas'));
        $this->getJson('/erp/recursos')->assertOk()->assertJsonPath('0.nombre', $d['nombre']);
        $this->postJson('/erp/recursos', $d)->assertForbidden();
    }

    public function test_role_matrix_protects_pages_endpoints_and_dynamic_navigation(): void
    {
        $this->getJson('/erp')->assertUnauthorized();
        $this->actingAs($this->staff('cliente'))->getJson('/erp/metricas')->assertForbidden();
        $this->actingAs($this->staff('ventas'));
        $this->get('/erp')->assertOk()->assertSee('Nueva orden')->assertDontSee('id="new-resource"', false)->assertDontSee('id="users"', false);
        $this->get('/crm')->assertOk();
        $this->postJson('/inventario/movimiento', [])->assertForbidden();
        $this->postJson('/productos', [])->assertForbidden();
        $this->putJson('/erp/estado', [])->assertForbidden();
        $this->actingAs($this->staff('logistica'));
        $this->get('/erp')->assertOk()->assertDontSee('id="confirm-order"', false)->assertSee('Recursos internos');
        $this->getJson('/crm')->assertForbidden();
        $this->postJson('/ordenes/procesar', [])->assertForbidden();
        $this->postJson('/proveedores', ['nombre' => 'Proveedor logística', 'contacto' => 'Contacto', 'correo' => 'log@example.test', 'telefono' => '4491234567'])->assertCreated();
        $this->putJson('/usuarios/rol', [])->assertForbidden();
    }

    public function test_admin_assigns_roles_and_cannot_remove_own_admin_access(): void
    {
        $admin = $this->staff();
        $other = $this->staff('usuario');
        $this->actingAs($admin);
        $this->putJson('/usuarios/rol', ['usuario_id' => $other->id, 'role' => 'ventas'])->assertOk()->assertJsonPath('role', 'ventas');
        $this->assertSame('ventas', $other->fresh()->role);
        $this->putJson('/usuarios/rol', ['usuario_id' => $admin->id, 'role' => 'ventas'])->assertConflict();
        $this->putJson('/usuarios/rol', ['usuario_id' => $other->id, 'role' => 'root'])->assertUnprocessable();
        $this->postJson('/usuarios', ['name' => 'Logística nueva', 'email' => 'newlog@example.test', 'password' => 'LongPassword123!', 'role' => 'logistica'])->assertCreated();
        $this->actingAs($other->fresh());
        $this->putJson('/usuarios/rol', ['usuario_id' => $admin->id, 'role' => 'cliente'])->assertForbidden();
    }

    public function test_auth_login_alias_returns_home_and_permissions_without_privilege_escalation(): void
    {
        $u = $this->staff('ventas');
        $u->password = 'PasswordTest123!';
        $u->save();
        $this->postJson('/auth/login', ['email' => $u->email, 'password' => 'PasswordTest123!', 'role' => 'admin'])->assertOk()->assertJsonPath('user.role', 'ventas')->assertJsonPath('user.home', '/erp')->assertJsonPath('user.permissions', fn ($p) => in_array('crm', $p) && in_array('erp.write', $p));
        $this->postJson('/logout')->assertOk();
        $this->postJson('/registro', ['name' => 'Cliente público', 'email' => 'public@example.test', 'password' => 'PasswordTest123!', 'role' => 'admin'])->assertCreated()->assertJsonPath('user.role', 'cliente')->assertJsonCount(0, 'user.permissions');
    }

    public function test_demo_command_is_additive_and_idempotent(): void
    {
        $p = $this->product();
        $this->artisan('hf:demo-u3')->expectsQuestion('Contraseña para las nuevas cuentas demo (mínimo 12 caracteres)', 'DemoPassword123!')->assertExitCode(0);
        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('products', 3);
        $this->assertSame(10, $p->fresh()->stock);
        $this->artisan('hf:demo-u3')->assertExitCode(0);
        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('products', 3);
        $this->artisan('hf:diagnostico')->assertExitCode(0);
    }
}
```

## tests/ui/fixtures.php

```php
<?php

// Solo se ejecuta desde run.cjs con una base SQLite temporal y aislada.
if (getenv('HF_UI_TEST') !== '1') {
    exit(1);
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$accounts = [];
foreach (['admin', 'ventas', 'logistica'] as $role) {
    $u = new App\Models\User(['name' => 'UI '.ucfirst($role), 'email' => $role.'@ui.example.test', 'password' => 'UiPassword123456!']);
    $u->role = $role;
    $u->save();
    $accounts[$role] = $u->id;
}
$client = App\Models\Cliente::create(['nombre' => 'Cliente UI existente', 'correo' => 'cliente@ui.example.test', 'estado' => 'activo', 'etapa_crm' => 'Prospecto']);
$supplier = App\Models\Proveedor::create(['nombre' => 'Proveedor UI existente', 'contacto' => 'Contacto UI', 'correo' => 'proveedor@ui.example.test', 'telefono' => '4491234567']);
$ids = [];
foreach (['PUSH', 'PULL'] as $strategy) {
    $p = App\Models\Product::create(['name' => 'UI '.$strategy, 'description' => 'Producto para pruebas de interfaz', 'category' => 'Hoodie',
        'price_cents' => $strategy === 'PUSH' ? 29900 : 19900, 'stock' => $strategy === 'PUSH' ? 4 : 10,
        'stock_minimo' => 3, 'stock_objetivo' => 12, 'proveedor_id' => $supplier->id, 'costo_unitario_cents' => 10000,
        'image' => 'https://example.test/p.jpg', 'estrategia_logistica' => $strategy, 'active' => true]);
    $ids[$strategy] = $p->id;
}
echo json_encode(['users' => $accounts, 'client' => $client->id, 'products' => $ids], JSON_THROW_ON_ERROR);
```

## tests/ui/run.cjs

```javascript
/* Prueba de eventos DOM -> fetch HTTP real -> Laravel -> SQLite temporal.
   JSDOM no verifica la presentación visual ni sustituye Chrome. */
const fs=require('node:fs'),path=require('node:path'),os=require('node:os'),cp=require('node:child_process'),assert=require('node:assert/strict'),crypto=require('node:crypto');
const {JSDOM,VirtualConsole,CookieJar}=require('jsdom');
const net=require('node:net');
const root=path.resolve(__dirname,'../..'),tmp=fs.mkdtempSync(path.join(os.tmpdir(),'hf-u3-ui-')),db=path.join(tmp,'ui.sqlite');
fs.writeFileSync(db,'');
const env={...process.env,APP_ENV:'local',APP_DEBUG:'false',APP_KEY:'base64:'+crypto.randomBytes(32).toString('base64'),DB_CONNECTION:'sqlite',DB_DATABASE:db,DB_URL:'',SESSION_DRIVER:'database',CACHE_STORE:'array',QUEUE_CONNECTION:'sync',
    APP_CONFIG_CACHE:path.join(tmp,'config.php'),APP_ROUTES_CACHE:path.join(tmp,'routes.php'),HF_UI_TEST:'1'};
const php=process.env.HF_PHP||'php';
function command(args){const r=cp.spawnSync(php,args,{cwd:root,env,encoding:'utf8'});if(r.status!==0)throw Error(r.stdout+'\n'+r.stderr);return r.stdout;}
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const checks=[],errors=[],sessions=[];
let server,base,fixtures;
function passed(label){checks.push(label);console.log('OK '+label);}
async function until(fn,label){for(let n=0;n<250;n++){if(fn())return;await sleep(20);}throw Error('Tiempo agotado: '+label);}
async function session(role){
    const jar=new CookieJar(),calls=[];let pending=0;
    async function fetcher(url,options={}){
        url=new URL(url,base).href;
        const headers=new Headers(options.headers||{}),cookies=jar.getCookieStringSync(url);if(cookies)headers.set('Cookie',cookies);
        pending++;let r;try{r=await fetch(url,{...options,headers,redirect:'manual'});}finally{pending--;}
        for(const cookie of r.headers.getSetCookie())jar.setCookieSync(cookie,url);
        calls.push({path:new URL(url).pathname,method:options.method||'GET',status:r.status});
        return r;
    }
    let token=(await(await fetcher('/sesion')).json()).csrf_token;
    const login=await fetcher('/auth/login',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify({email:role+'@ui.example.test',password:'UiPassword123456!'})});
    assert.equal(login.status,200);token=(await login.json()).csrf_token;
    async function api(url,method='GET',body,csrf=true){
        const r=await fetcher(url,{method,headers:{Accept:'application/json','Content-Type':'application/json',...(csrf?{'X-CSRF-TOKEN':token}:{})},...(body===undefined?{}:{body:JSON.stringify(body)})});
        const text=await r.text();let json;try{json=JSON.parse(text);}catch{}
        return {status:r.status,json,text};
    }
    const s={role,api,calls,async page(url,script){
        const r=await fetcher(url);assert.equal(r.status,200,url);
        const vc=new VirtualConsole();vc.on('jsdomError',e=>{if(!/Not implemented: (navigation|window.scrollTo)/.test(e.message))errors.push(e.message);});
        const dom=new JSDOM(await r.text(),{url:new URL(url,base).href,runScripts:'outside-only',pretendToBeVisual:true,cookieJar:jar,virtualConsole:vc});
        const w=dom.window;w.fetch=fetcher;w.confirm=()=>true;w.alert=()=>{};w.scrollTo=()=>{};w.print=()=>s.printed=true;
        w.HTMLDialogElement.prototype.showModal=function(){this.open=true;};w.HTMLDialogElement.prototype.close=function(){this.open=false;};
        w.eval(fs.readFileSync(path.join(root,'public/js/http.js'),'utf8'));
        w.eval(fs.readFileSync(path.join(root,'public/js/'+script+'.js'),'utf8'));
        s.dom=dom;s.w=w;s.d=w.document;
        return s;
    },async close(){await until(()=>pending===0,'solicitudes terminadas');await sleep(50);s.dom?.window.close();}};
    sessions.push(s);return s;
}
async function click(s,selector){await until(()=>s.d.querySelector(selector)&&!s.d.querySelector(selector).disabled,'botón habilitado '+selector);s.d.querySelector(selector).click();}
function fill(s,selector,value){const e=s.d.querySelector(selector);assert(e,'Campo '+selector);e.value=value;return e;}
async function submit(s,selector){await until(()=>!s.d.querySelector(selector+' button[type="submit"],'+selector+' button.primary').disabled,'botón habilitado '+selector);await click(s,selector+' button[type="submit"],'+selector+' button.primary');}
async function erp(admin){
    await admin.page('/erp','erp');
    await until(()=>admin.d.querySelectorAll('#kpis .kpi').length===5,'dashboard ERP');
    await click(admin,'#refresh');await until(()=>admin.d.querySelector('#notice').textContent.includes('actualizados'),'actualizar ERP');
    await click(admin,'[data-panel="new"]');
    await until(()=>admin.d.querySelector('[data-client]'),'clientes ERP');
    await click(admin,'#new-client');
    fill(admin,'#client-form [name="nombre"]','UI Cliente nuevo');fill(admin,'#client-form [name="correo"]','nuevo@ui.example.test');fill(admin,'#client-form [name="telefono"]','4491234567');await submit(admin,'#client-form');
    await until(()=>!admin.d.querySelector('#client-dialog').open,'alta cliente CRM');
    await click(admin,'#new-client');
    fill(admin,'#client-form [name="nombre"]','UI Duplicado');fill(admin,'#client-form [name="correo"]','nuevo@ui.example.test');await submit(admin,'#client-form');
    await until(()=>admin.d.querySelector('#client-dialog #notice')?.textContent.includes('422'),'error visible en cliente');
    assert(admin.d.querySelector('#client-dialog').open);await click(admin,'[data-close="client-dialog"]');
    await click(admin,'#client-next');
    fill(admin,'#product-search [name="q"]','UI ');await submit(admin,'#product-search');
    await until(()=>admin.d.querySelector('[data-add="'+fixtures.products.PUSH+'"]'),'catálogo ERP');
    fill(admin,'[data-quantity="'+fixtures.products.PUSH+'"]','2');await click(admin,'[data-add="'+fixtures.products.PUSH+'"]');
    fill(admin,'[data-quantity="'+fixtures.products.PULL+'"]','2');await click(admin,'[data-add="'+fixtures.products.PULL+'"]');
    await click(admin,'[data-remove="'+fixtures.products.PULL+'"]');await click(admin,'[data-add="'+fixtures.products.PULL+'"]');
    await click(admin,'#product-next');assert(admin.d.querySelector('#order-total').textContent.includes('996'));
    await click(admin,'#review-back');await click(admin,'#product-back');await click(admin,'#client-next');await click(admin,'#product-next');
    const b=admin.d.querySelector('#confirm-order');b.click();b.click();
    await until(()=>!admin.d.querySelector('#success').hidden,'procesar orden');
    assert.equal(admin.calls.filter(c=>c.path==='/ordenes/procesar'&&c.method==='POST').length,1);
    const r=await admin.api('/ordenes');const first=r.json.data[0];assert.equal(first.total,996);
    assert.equal((await admin.api('/productos/'+fixtures.products.PUSH)).json.stock_actual,2);
    assert.equal((await admin.api('/productos/'+fixtures.products.PULL)).json.stock_actual,8);
    const scm=await admin.api('/pedidos');assert(scm.json.data.some(p=>p.producto_id===fixtures.products.PUSH&&p.automatico));
    await click(admin,'#view-created');await until(()=>admin.d.querySelector('#order-dialog').open,'ver orden');
    assert(admin.d.querySelector('#order-detail').textContent.includes('Movimientos de inventario'));
    await click(admin,'#print-order');assert(admin.printed);await click(admin,'[data-close="order-dialog"]');
    passed('ERP: cliente CRM, rechazo duplicado visible, selección, cantidades, quitar, atrás/siguiente, confirmar una vez, stock compartido, Push, detalle y botón de impresión');
    await click(admin,'#another-order');await until(()=>admin.d.querySelector('[data-client]'),'otra orden');
    await click(admin,'[data-client="'+fixtures.client+'"]');await click(admin,'#client-next');
    fill(admin,'#product-search [name="q"]','UI PULL');await submit(admin,'#product-search');await until(()=>admin.d.querySelector('[data-add="'+fixtures.products.PULL+'"]'),'producto Pull');
    fill(admin,'[data-quantity="'+fixtures.products.PULL+'"]','1');await click(admin,'[data-add="'+fixtures.products.PULL+'"]');await click(admin,'#product-next');await click(admin,'#save-draft');
    await until(()=>admin.d.querySelector('#success-title').textContent.includes('pendiente'),'guardar pendiente');
    assert.equal((await admin.api('/productos/'+fixtures.products.PULL)).json.stock_actual,8);
    await click(admin,'[data-panel="orders"]');await until(()=>admin.d.querySelector('[data-process]'),'listado pendientes');
    const second=Number(admin.d.querySelector('[data-process]').dataset.process);
    await click(admin,'[data-process="'+second+'"]');await until(()=>admin.d.querySelector('[data-state="completada"][data-order="'+second+'"]'),'procesar pendiente');
    await click(admin,'[data-state="completada"][data-order="'+second+'"]');await until(()=>admin.d.querySelector('#order-rows').textContent.includes('completada'),'entregar');
    await click(admin,'[data-state="cancelada"][data-order="'+first.id+'"]');await until(()=>admin.d.querySelector('#order-rows').textContent.includes('cancelada'),'cancelar');
    assert.equal((await admin.api('/productos/'+fixtures.products.PUSH)).json.stock_actual,4);
    assert.equal((await admin.api('/productos/'+fixtures.products.PULL)).json.stock_actual,9);
    fill(admin,'#order-filter [name="q"]','inexistente');await submit(admin,'#order-filter');await until(()=>admin.d.querySelector('#order-rows').textContent.includes('No hay'),'buscar vacío');
    await click(admin,'#clear-order-filter');await until(()=>admin.d.querySelectorAll('#order-rows tr').length===2,'limpiar filtro');
    passed('ERP: guardar pendiente, procesar, completar, cancelar con devolución, búsqueda y limpiar filtros');
    await click(admin,'[data-panel="reports"]');await until(()=>admin.d.querySelector('#report-kpis .kpi'),'reportes');
    await click(admin,'#generate-report');await until(()=>admin.d.querySelector('[data-snapshot]'),'corte guardado');
    await click(admin,'[data-snapshot]');await until(()=>admin.d.querySelector('#snapshot-dialog').open,'ver corte');
    const csv=admin.d.querySelector('#snapshot-detail a').getAttribute('href');assert.equal((await admin.api(csv)).status,200);await click(admin,'[data-close="snapshot-dialog"]');
    await click(admin,'[data-panel="maturity"]');await until(()=>admin.d.querySelector('#evidence-list input'),'evidencias');
    for(const box of admin.d.querySelectorAll('#evidence-list input')){assert(!box.disabled);box.checked=true;}
    fill(admin,'#maturity-form [name="nivel"]','Optimizado');await submit(admin,'#maturity-form');
    await until(()=>admin.d.querySelector('#level-name').textContent.includes('Optimizado'),'nivel ERP');
    await click(admin,'[data-panel="resources"]');await until(()=>admin.d.querySelector('#resource-rows tr'),'recursos');
    await click(admin,'#new-resource');await until(()=>admin.d.querySelector('#resource-dialog').open,'nuevo recurso');
    fill(admin,'#resource-form [name="nombre"]','UI Empaque');fill(admin,'#resource-form [name="responsable_id"]',String(fixtures.users.logistica));await submit(admin,'#resource-form');
    await until(()=>admin.d.querySelector('[data-resource]'),'recurso guardado');await click(admin,'[data-resource]');
    await until(()=>admin.d.querySelector('#resource-dialog').open,'editar recurso');admin.d.querySelector('#resource-form [name="disponible"]').checked=false;await submit(admin,'#resource-form');
    await until(()=>admin.d.querySelector('#resource-rows').textContent.includes('No disponible'),'recurso editado');
    await click(admin,'[data-panel="users"]');await until(()=>admin.d.querySelector('[data-role-user]'),'usuarios');
    assert(admin.d.querySelector('[data-role-user="'+fixtures.users.admin+'"]').disabled);
    await click(admin,'#new-user');fill(admin,'#user-form [name="name"]','UI Nueva cuenta');fill(admin,'#user-form [name="email"]','nueva.cuenta@ui.example.test');fill(admin,'#user-form [name="password"]','UiPassword123456!');await submit(admin,'#user-form');
    await until(()=>admin.d.querySelector('#user-rows').textContent.includes('nueva.cuenta@'),'cuenta creada');
    const created=(await admin.api('/usuarios')).json.find(u=>u.email==='nueva.cuenta@ui.example.test');
    fill(admin,'[data-role-for="'+created.id+'"]','logistica');await click(admin,'[data-role-user="'+created.id+'"]');
    await until(()=>admin.d.querySelector('#notice').textContent.includes('Rol actualizado'),'cambio de rol');
    assert.equal((await admin.api('/usuarios')).json.find(u=>u.id===created.id).role,'logistica');
    passed('ERP: reportes y CSV persistentes, madurez con evidencia, recursos alta/edición, usuarios y cambio de rol');
}
async function crm(admin){
    await admin.close();await admin.page('/crm','crm');await until(()=>admin.d.querySelector('#counters .card'),'CRM');
    await click(admin,'[data-tab="clients"]');await until(()=>admin.d.querySelector('#client-list tr'),'listado CRM');await click(admin,'#new-client');
    fill(admin,'#client-form [name="nombre"]','UI CRM temporal');fill(admin,'#client-form [name="correo"]','crm.temporal@ui.example.test');await submit(admin,'#client-form');
    await until(()=>!admin.d.querySelector('#client-dialog').open,'guardar cliente CRM');
    const c=(await admin.api('/clientes?q=crm.temporal')).json.data[0];
    await click(admin,'[data-edit="'+c.id+'"]');await until(()=>admin.d.querySelector('#client-dialog').open,'editar cliente');
    fill(admin,'#client-form [name="empresa"]','Empresa UI');await submit(admin,'#client-form');await until(()=>!admin.d.querySelector('#client-dialog').open,'editar guardado');
    await click(admin,'[data-history="'+c.id+'"]');await until(()=>admin.d.querySelector('#detail-stage'),'historial CRM');
    fill(admin,'#interaction-form [name="descripcion"]','Interacción desde eventos de la interfaz');await submit(admin,'#interaction-form');
    await until(()=>admin.d.querySelector('#history .list-item'),'interacción CRM');
    fill(admin,'#evaluation-form [name="observaciones"]','Evaluación UI');await submit(admin,'#evaluation-form');await until(()=>admin.d.querySelector('#evaluation-list .list-item'),'evaluación CRM');
    const stage=fill(admin,'#detail-stage','Frecuente');stage.dispatchEvent(new admin.w.Event('change',{bubbles:true}));
    await until(()=>admin.d.querySelector('#message').textContent.includes('Etapa actualizada'),'etapa CRM');
    await click(admin,'[data-tab="activity"]');await until(()=>admin.d.querySelector('#my-activity .list-item'),'mi actividad');
    await click(admin,'#back-clients');await until(()=>admin.d.querySelector('[data-delete="'+c.id+'"]'),'regresar clientes');
    await click(admin,'[data-delete="'+c.id+'"]');await until(()=>!admin.d.querySelector('[data-delete="'+c.id+'"]'),'eliminar cliente');
    await click(admin,'[data-tab="contacts"]');await until(()=>admin.d.querySelector('#contact-list').textContent.includes('Sin mensajes'),'contactos vacíos');
    await click(admin,'[data-tab="users"]');await until(()=>admin.d.querySelector('#user-list .list-item'),'equipo CRM');
    await click(admin,'[data-tab="dashboard"]');await click(admin,'#refresh-metrics');
    const rolesLink=admin.d.querySelector('a[href="/erp#users"]');assert(rolesLink);await admin.close();await admin.page(rolesLink.getAttribute('href'),'erp');await until(()=>admin.d.querySelector('#user-rows tr'),'enlace CRM a roles ERP');
    passed('CRM: alta, edición, historial, interacción, evaluación, etapa, Mi actividad, eliminación, contactos, equipo y actualizar');
}
async function scm(admin){
    await admin.close();await admin.page('/scm','scm');await until(()=>admin.d.querySelector('#counters .card'),'SCM');
    await click(admin,'[data-tab="suppliers"]');await click(admin,'#new-supplier');
    fill(admin,'#supplier-form [name="nombre"]','UI Proveedor nuevo');fill(admin,'#supplier-form [name="contacto"]','Contacto UI');fill(admin,'#supplier-form [name="correo"]','nuevo.proveedor@ui.example.test');fill(admin,'#supplier-form [name="telefono"]','4491234567');await submit(admin,'#supplier-form');
    await until(()=>!admin.d.querySelector('#supplier-dialog').open,'proveedor guardado');
    const supplier=(await admin.api('/proveedores')).json.find(p=>p.correo==='nuevo.proveedor@ui.example.test');
    await click(admin,'[data-edit-supplier="'+supplier.id+'"]');fill(admin,'#supplier-form [name="contacto"]','Contacto editado UI');await submit(admin,'#supplier-form');await until(()=>!admin.d.querySelector('#supplier-dialog').open,'proveedor editado');
    await click(admin,'#new-supplier');fill(admin,'#supplier-form [name="nombre"]','UI Duplicado');fill(admin,'#supplier-form [name="contacto"]','Contacto UI');fill(admin,'#supplier-form [name="correo"]','nuevo.proveedor@ui.example.test');fill(admin,'#supplier-form [name="telefono"]','4491234567');await submit(admin,'#supplier-form');
    await until(()=>admin.d.querySelector('#supplier-dialog .dialog-notice')?.textContent.includes('422'),'duplicado proveedor visible');await click(admin,'[data-close="supplier-dialog"]');
    await click(admin,'[data-tab="catalog"]');await click(admin,'#new-product');
    fill(admin,'#product-form [name="nombre"]','UI Producto temporal');fill(admin,'#product-form [name="descripcion"]','Producto para validar todos los controles');fill(admin,'#product-form [name="imagen"]','https://example.test/ui.jpg');fill(admin,'#product-form [name="stock_actual"]','5');fill(admin,'#product-form [name="stock_minimo"]','2');fill(admin,'#product-form [name="proveedor_id"]',String(supplier.id));await submit(admin,'#product-form');
    await until(()=>!admin.d.querySelector('#product-dialog').open,'producto guardado');
    const p=(await admin.api('/productos?q=UI%20Producto%20temporal')).json.data[0];
    await click(admin,'[data-edit-product="'+p.id+'"]');await until(()=>admin.d.querySelector('#product-dialog').open,'editar producto');assert(admin.d.querySelector('#product-form [name="stock_actual"]').disabled);
    fill(admin,'#product-form [name="precio_venta"]','199.90');fill(admin,'#product-form [name="proveedor_id"]','');await submit(admin,'#product-form');await until(()=>!admin.d.querySelector('#product-dialog').open,'producto editado');
    await click(admin,'[data-strategy="'+p.id+'"]');await until(()=>admin.d.querySelector('#notice').textContent.includes('422'),'Push exige proveedor');
    await click(admin,'[data-edit-product="'+p.id+'"]');await until(()=>admin.d.querySelector('#product-dialog').open,'asociar proveedor');fill(admin,'#product-form [name="proveedor_id"]',String(supplier.id));await submit(admin,'#product-form');await until(()=>!admin.d.querySelector('#product-dialog').open,'asociación guardada');
    await click(admin,'[data-strategy="'+p.id+'"]');await until(()=>admin.d.querySelector('[data-strategy="'+p.id+'"]').dataset.value==='PULL','Push configurado');
    await click(admin,'[data-strategy="'+p.id+'"]');await until(()=>admin.d.querySelector('[data-strategy="'+p.id+'"]').dataset.value==='PUSH','Pull configurado');
    await click(admin,'[data-history="'+p.id+'"]');await until(()=>admin.d.querySelector('#history-dialog').open,'historial SCM');
    fill(admin,'#movement-form [name="cantidad"]','1');fill(admin,'#movement-form [name="referencia"]','UI conteo');await submit(admin,'#movement-form');await until(()=>admin.d.querySelector('#history-stock').textContent.includes('6'),'entrada SCM');
    fill(admin,'#movement-form [name="tipo"]','salida');fill(admin,'#movement-form [name="cantidad"]','6');await submit(admin,'#movement-form');await until(()=>admin.d.querySelector('#history-stock').textContent.includes('0'),'salida SCM');
    await click(admin,'[data-close="history-dialog"]');await click(admin,'[data-edit-product="'+p.id+'"]');await until(()=>admin.d.querySelector('#product-dialog').open,'desasociar proveedor');fill(admin,'#product-form [name="proveedor_id"]','');await submit(admin,'#product-form');await until(()=>!admin.d.querySelector('#product-dialog').open,'desasociación');
    await click(admin,'[data-delete-product="'+p.id+'"]');await until(()=>!admin.d.querySelector('[data-delete-product="'+p.id+'"]'),'archivar producto');
    fill(admin,'#product-filter [name="archivados"]','1');await submit(admin,'#product-filter');await until(()=>admin.d.querySelector('[data-restore-product="'+p.id+'"]'),'producto archivado visible');await click(admin,'[data-restore-product="'+p.id+'"]');await until(()=>!admin.d.querySelector('[data-restore-product="'+p.id+'"]'),'restaurar producto');fill(admin,'#product-filter [name="archivados"]','0');await submit(admin,'#product-filter');await until(()=>admin.d.querySelector('[data-delete-product="'+p.id+'"]'),'producto restaurado visible');await click(admin,'[data-delete-product="'+p.id+'"]');await until(()=>!admin.d.querySelector('[data-delete-product="'+p.id+'"]'),'volver a archivar');
    await click(admin,'[data-tab="suppliers"]');await click(admin,'[data-delete-supplier="'+supplier.id+'"]');await until(()=>!admin.d.querySelector('[data-delete-supplier="'+supplier.id+'"]'),'eliminar proveedor');
    await click(admin,'[data-tab="orders"]');await click(admin,'#new-order');await until(()=>admin.d.querySelector('#order-dialog').open,'pedido SCM');
    fill(admin,'#order-search','UI PULL');await click(admin,'#order-search-button');await until(()=>admin.d.querySelector('#order-form [name="producto_id"]').value===String(fixtures.products.PULL),'buscar producto pedido');
    fill(admin,'#order-form [name="cantidad"]','2');await submit(admin,'#order-form');await until(()=>!admin.d.querySelector('#order-dialog').open,'pedido creado');
    const order=(await admin.api('/pedidos')).json.data.find(p=>p.producto_id===fixtures.products.PULL&&p.estado==='pendiente');
    await click(admin,'[data-fulfill="'+order.id+'"]');await until(()=>!admin.d.querySelector('[data-fulfill="'+order.id+'"]'),'surtir SCM');
    await click(admin,'[data-tab="maturity"]');fill(admin,'#maturity-form [name="nivel_scm"]','Optimizado');for(const box of admin.d.querySelectorAll('#maturity-form input[type="checkbox"]'))box.checked=true;await submit(admin,'#maturity-form');
    await until(()=>admin.d.querySelector('#level-indicator').textContent==='Optimizado','madurez SCM');
    await click(admin,'#refresh');await until(()=>admin.d.querySelector('#notice').textContent.includes('actualizados'),'actualizar SCM');
    assert.equal((await admin.api('/scm/reportes/csv')).status,200);
    passed('SCM: proveedores alta/editar/duplicado/eliminar, productos alta/editar/archivar, Push/Pull, movimientos, búsqueda/pedido/surtir, madurez, actualizar y CSV');
}
async function storefront(admin){
    await admin.close();await admin.page('/','storefront');
    admin.w.eval(fs.readFileSync(path.join(root,'node_modules/alpinejs/dist/cdn.js'),'utf8'));
    await until(()=>admin.w.Alpine?.$data(admin.d.body).ready,'Alpine tienda');
    const app=admin.w.Alpine.$data(admin.d.body);
    const button=expression=>[...admin.d.querySelectorAll('button')].find(b=>b.getAttribute('@click')===expression||b.getAttribute('@click')?.startsWith(expression+';'));
    async function action(expression){await until(()=>button(expression)&&!button(expression).disabled,'botón Alpine '+expression);button(expression).click();await sleep(20);}
    async function form(expression){const f=[...admin.d.querySelectorAll('form')].find(f=>f.getAttribute('@submit.prevent')===expression);assert(f,expression);await sleep(25);const b=f.querySelector('button[type="submit"]')||f.querySelector('button:not([type="button"])');assert(b);b.click();}
    await action('handleLogout()');await until(()=>!app.isLoggedIn,'logout tienda');
    app.navigateTo('registro');app.registroForm={name:'UI Cliente tienda',email:'tienda@ui.example.test',password:'UiPassword123456!'};
    await form('handleRegistro()');await until(()=>app.isLoggedIn&&!app.isSaving,'registro público');
    assert.equal(app.user.role,'cliente');assert(!app.can('erp.read'));
    app.navigateTo('catalogo');await sleep(30);const product=app.allProducts.find(p=>p.stock>4);
    app.viewProductDetail(product);await action('addToCart(selectedProduct)');assert.equal(app.cartTotalItems,1);
    await action('increaseQty(index)');assert.equal(app.cartTotalItems,2);await action('decreaseQty(index)');assert.equal(app.cartTotalItems,1);
    app.discountCode='HF10';await form('applyDiscount()');assert(app.discountApplied);
    await action('handleCheckout()');assert.equal(app.checkoutStep,1);
    app.checkoutForm={address:'Calle UI 123',city:'Aguascalientes',zip:'20000'};await form('checkoutStep = 2');await until(()=>app.checkoutStep===2,'dirección checkout');
    await action('checkoutStep = 3');await action('processFinalPayment()');
    await until(()=>app.orderSuccessOpen&&!app.isCheckingOut,'checkout persistente');
    assert.equal(app.cart.length,0);assert.equal(app.userOrders.length,1);
    const order=app.userOrders[0];
    app.openInvoice(order);
    const {jsPDF}=require(path.join(root,'node_modules/jspdf'));const {applyPlugin}=require(path.join(root,'node_modules/jspdf-autotable'));applyPlugin(jsPDF);
    const originalSave=jsPDF.API.save;jsPDF.API.save=function(){fs.writeFileSync(path.join(tmp,'ticket.pdf'),Buffer.from(this.output('arraybuffer')));};
    admin.w.jspdf={jsPDF};app.downloadInvoice(order);jsPDF.API.save=originalSave;
    assert(fs.readFileSync(path.join(tmp,'ticket.pdf')).subarray(0,4).equals(Buffer.from('%PDF')));
    app.orderSuccessOpen=false;app.invoiceModalOpen=false;
    app.navigateTo('publicar');app.publishForm={name:'UI Publicación',price:'250',description:'Publicación de prueba con descripción completa',image:'https://example.test/publicacion.jpg'};
    await form('handlePublishSubmit()');await until(()=>app.userProducts.length===1&&!app.isSaving,'publicar');
    app.openEditModal(app.userProducts[0],0);app.editForm.description='Descripción editada desde la interfaz';await form('saveEdit()');
    await until(()=>!app.editModalOpen,'editar publicación');
    await action('deleteUserProduct(index)');await until(()=>app.userProducts.length===0,'eliminar publicación');
    app.navigateTo('comunidad');app.newComment='Reseña desde la interfaz de prueba';await form('addComment()');await until(()=>app.comments.some(c=>c.text==='Reseña desde la interfaz de prueba'),'reseña');
    app.navigateTo('subasta');app.auction.newOffer=Number(app.auction.product.currentBid)+100;await form('handleOfferSubmit()');await until(()=>app.auction.offers.length>0,'oferta subasta');
    app.navigateTo('contacto');app.contactForm={name:'Contacto UI',email:'contacto@ui.example.test',message:'Mensaje real para el equipo CRM'};await form('handleContactSubmit()');await until(()=>app.contactForm.message==='','mensaje contacto');
    app.newsletterEmail='newsletter@ui.example.test';await form('subscribeNewsletter()');await until(()=>app.newsletterEmail==='','suscripción');
    await action('handleLogout()');await until(()=>!app.isLoggedIn,'logout final');
    app.navigateTo('login');app.loginForm={email:'tienda@ui.example.test',password:'UiPassword123456!'};await form('handleLogin()');await until(()=>app.isLoggedIn&&!app.isLoggingIn,'login tienda');
    assert.equal(app.userOrders.length,1);
    passed('Tienda: Alpine real, registro/login/logout, carrito y cantidades, cupón, checkout, persistencia, PDF real, publicaciones alta/editar/eliminar, reseñas, subasta, contacto y suscripción');
}
(async()=>{
    try{
        command(['artisan','migrate','--seed','--force','--no-ansi']);
        fixtures=JSON.parse(command(['tests/ui/fixtures.php']));
        const port=await new Promise((resolve,reject)=>{const probe=net.createServer();probe.once('error',reject);probe.listen(Number(process.env.HF_UI_PORT||0),'127.0.0.1',()=>{const chosen=probe.address().port;probe.close(()=>resolve(chosen));});});base='http://127.0.0.1:'+port;
        server=cp.spawn(php,['-S','127.0.0.1:'+port,'-t',path.join(root,'public'),path.join(root,'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],{cwd:path.join(root,'public'),env,stdio:'ignore'});
        let ready=false;for(let n=0;n<100;n++){try{const r=await fetch(base+'/up');if(r.ok){ready=true;break;}}catch{}await sleep(30);}assert(ready,'Servidor de pruebas disponible');
        const admin=await session('admin');
        assert.equal((await admin.api('/proveedores','POST',{},false)).status,419);passed('HTTP real: sesión, cookies, login /auth/login y protección CSRF 419');
        await erp(admin);await crm(admin);await scm(admin);await storefront(admin);
        for(const role of ['ventas','logistica']){
            const s=await session(role);await s.page('/erp','erp');await until(()=>s.d.querySelector('#kpis .kpi'),'panel por rol');
            assert(!s.d.querySelector('#users'));
            if(role==='ventas'){assert(s.d.querySelector('#confirm-order'));assert(!s.d.querySelector('#resource-form'));assert.equal((await s.api('/inventario/movimiento','POST',{})).status,403);}
            else{assert(!s.d.querySelector('#confirm-order'));assert(s.d.querySelector('#resource-form'));assert.equal((await s.api('/ordenes/procesar','POST',{})).status,403);assert.equal((await s.api('/crm')).status,403);}
            passed('Rol '+role+': menú dinámico y bloqueos reales del backend');
        }
        assert.deepEqual(errors,[]);
        passed('Sin excepciones de JavaScript no controladas en los recorridos');
        console.log(JSON.stringify({success:true,groups:checks.length,checks},null,2));
    }catch(e){console.error(e.stack);process.exitCode=1;}
    finally{for(const s of sessions)await s.close();if(server)server.kill();fs.rmSync(tmp,{recursive:true,force:true});}
})();
```
