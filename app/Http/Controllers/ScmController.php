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
            abort_if($product->stock > 0, 409, 'Agota o ajusta el stock antes de archivar.');
            abort_if(Pedido::where('producto_id', $product->id)->where('estado', 'pendiente')->exists(), 409, 'El producto tiene pedidos SCM pendientes.');
            abort_if(DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->where('product_id', $product->id)->whereIn('status', ['Pendiente', 'Enviado'])->exists(), 409, 'El producto tiene pedidos de tienda abiertos.');
            $product->delete();

            return response()->noContent();
        }, 3);
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
