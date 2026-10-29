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
