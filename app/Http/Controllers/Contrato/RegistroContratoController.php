<?php

namespace App\Http\Controllers\Contrato;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\ContratoDetalle;
use App\Models\Cuenta;
use App\Models\Departamentos;
use App\Models\InformacionGeneral;
use App\Models\Materiales;
use App\Models\ObjetoEspecifico;
use App\Models\Proveedor;
use App\Models\RetiroContrato;
use App\Models\RetiroContratoDetalle;
use App\Models\Rubro;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistroContratoController extends Controller
{

    public function indexSalidaContrato()
    {
        $arrayContratos = Contrato::where('estado', 'vigente')
            ->orderBy('nombre_proceso')
            ->selectRaw("id, CONCAT(codigo, ' - ', nombre_proceso) as texto")
            ->pluck('texto', 'id');

        // Para el select de "otra unidad"
        $arrayDepartamentos = Departamentos::orderBy('nombre', 'ASC')->get();

        return view(
            'backend.admin.procesocontrato.registro.salidas.vistasalidacontrato',
            compact('arrayContratos', 'arrayDepartamentos')
        );
    }


    public function unidadesContrato(Request $request)
    {
        $unidades = Departamentos::whereIn('id', function ($q) use ($request) {
            $q->select('cdd.id_departamento')
                ->from('contrato_detalle_departamento as cdd')
                ->join('contrato_detalle as cd', 'cd.id', '=', 'cdd.id_contrato_detalle')
                ->where('cd.id_contrato', $request->id_contrato);
        })
            ->orderBy('nombre', 'ASC')
            ->get(['id', 'nombre']);

        return response()->json($unidades);
    }



    public function infoContrato(Request $request)
    {
        $contrato = Contrato::with('proveedor')->find($request->id_contrato);
        if (!$contrato) return response()->json(['success' => 0]);

        // null = "Todos"; número = una unidad específica
        $idDepto = is_numeric($request->id_departamento) ? (int) $request->id_departamento : null;
        $nombreUnidad = $idDepto ? Departamentos::where('id', $idDepto)->value('nombre') : null;

        // Retirado por (item, unidad de origen) en todo el contrato
        $retiradoUnidad = DB::table('retiro_contrato_detalle as rd')
            ->join('retiro_contrato as r', 'r.id', '=', 'rd.id_retiro_contrato')
            ->where('r.id_contrato', $contrato->id)
            ->whereNotNull('rd.id_departamento')
            ->groupBy('rd.id_contrato_detalle', 'rd.id_departamento')
            ->selectRaw('rd.id_contrato_detalle, rd.id_departamento, SUM(rd.cantidad) as total')
            ->get()
            ->mapWithKeys(fn ($f) => [$f->id_contrato_detalle . '-' . $f->id_departamento => (int) $f->total]);

        $query = ContratoDetalle::with(['unidadMedida', 'departamentos'])
            ->where('id_contrato', $contrato->id);

        // Si eligió una unidad, solo los items asignados a ella
        if ($idDepto) {
            $query->whereHas('departamentos', fn ($q) => $q->where('departamentos.id', $idDepto));
        }

        $detalle = $query
            ->withSum('retiros as retirado', 'cantidad')
            ->orderBy('nombre')
            ->get()
            ->map(function ($item) use ($retiradoUnidad, $idDepto) {
                $retirado = (int) ($item->retirado ?? 0);

                $unidades = $item->departamentos
                    ->sortBy('nombre')
                    ->values()
                    ->map(function ($dep) use ($item, $retiradoUnidad) {
                        $asignado = (int) $dep->pivot->cantidad;
                        $ret      = (int) ($retiradoUnidad[$item->id . '-' . $dep->id] ?? 0);

                        return [
                            'id'         => $dep->id,
                            'nombre'     => $dep->nombre,
                            'asignado'   => $asignado,
                            'retirado'   => $ret,
                            'disponible' => max($asignado - $ret, 0),
                        ];
                    });

                // Unidad específica: los números son los de esa unidad
                if ($idDepto) {
                    $u = $unidades->firstWhere('id', $idDepto);

                    return [
                        'nombre'        => $item->nombre,
                        'unidad_medida' => optional($item->unidadMedida)->nombre,
                        'cantidad'      => $u['asignado'],
                        'retirado'      => $u['retirado'],
                        // No puede superar lo que queda del item en total
                        'disponible'    => max(min($u['disponible'], $item->cantidad - $retirado), 0),
                        'precio'        => '$' . number_format($item->precio, 4),
                        'unidades'      => [$u],
                    ];
                }

                // Todos: números del item completo
                return [
                    'nombre'        => $item->nombre,
                    'unidad_medida' => optional($item->unidadMedida)->nombre,
                    'cantidad'      => $item->cantidad,
                    'retirado'      => $retirado,
                    'disponible'    => $item->cantidad - $retirado,
                    'precio'        => '$' . number_format($item->precio, 4),
                    'unidades'      => $unidades,
                ];
            })
            ->values();

        return response()->json([
            'success'        => 1,
            'codigo'         => $contrato->codigo,
            'nombre_proceso' => $contrato->nombre_proceso,
            'proveedor'      => optional($contrato->proveedor)->nombre,
            'fecha_inicio'   => $contrato->fecha_inicio ? Carbon::parse($contrato->fecha_inicio)->format('d-m-Y') : null,
            'fecha_fin'      => $contrato->fecha_fin ? Carbon::parse($contrato->fecha_fin)->format('d-m-Y') : null,
            'descripcion'    => $contrato->descripcion,
            'unidad_nombre'  => $nombreUnidad,
            'detalle'        => $detalle,
        ]);
    }

    public function buscarMaterialContrato(Request $request)
    {
        $idDepto = (int) $request->id_departamento;
        $texto   = (string) $request->input('query', '');

        $items = ContratoDetalle::with([
            'unidadMedida',
            'departamentos' => fn ($q) => $q->where('departamentos.id', $idDepto),
        ])
            ->where('id_contrato', $request->id_contrato)
            ->whereHas('departamentos', fn ($q) => $q->where('departamentos.id', $idDepto))
            ->where('nombre', 'like', '%' . $texto . '%')
            ->withSum('retiros as retirado', 'cantidad')
            ->orderBy('nombre')
            ->limit(30)
            ->get();

        // Retirado por esta unidad para los items encontrados (una sola consulta)
        $retiradoUnidad = DB::table('retiro_contrato_detalle as rd')
            ->where('rd.id_departamento', $idDepto)
            ->whereIn('rd.id_contrato_detalle', $items->pluck('id'))
            ->groupBy('rd.id_contrato_detalle')
            ->selectRaw('rd.id_contrato_detalle, SUM(rd.cantidad) as total')
            ->pluck('total', 'id_contrato_detalle');

        $resultados = $items->map(function ($item) use ($retiradoUnidad) {
            $asignado        = (int) $item->departamentos->first()->pivot->cantidad;
            $retUnidad       = (int) ($retiradoUnidad[$item->id] ?? 0);
            $disponibleTotal = $item->cantidad - (int) ($item->retirado ?? 0);

            return [
                'id'              => $item->id,
                'nombre'          => $item->nombre,
                'unidad'          => $item->unidadMedida->nombre ?? '',
                'asignado'        => $asignado,
                'retirado_unidad' => $retUnidad,
                // No puede superar lo asignado a la unidad ni lo que queda del item
                'disponible'      => max(min($asignado - $retUnidad, $disponibleTotal), 0),
                'precio'          => '$' . number_format($item->precio, 4),
                'precio_num'      => (float) $item->precio,
            ];
        })->values();

        return response()->json($resultados);
    }

    public function guardarRetiro(Request $request)
    {
        $idContrato = $request->id_contrato;
        $otraUnidad = (int) $request->otra_unidad === 1;
        $items      = json_decode($request->contenedorArray, true);

        if (empty($items) || !is_array($items)) return response()->json(['success' => 1]);

        // Un mismo item no puede repetirse para la misma unidad de origen
        $claves = collect($items)->map(fn ($i) => (int) $i['id_contrato_detalle'] . '-' . (int) $i['id_departamento']);
        if ($claves->count() !== $claves->unique()->count()) {
            return response()->json(['success' => 9]);
        }

        $contrato = Contrato::find($idContrato);
        if (!$contrato) return response()->json(['success' => 5]);
        if ($contrato->estado !== 'vigente') return response()->json(['success' => 3]);

        // Unidad destino (solo si marcó "Es para otra unidad")
        $idDestino = null;

        if ($otraUnidad) {
            $idDestino = (int) $request->id_departamento_destino;
            $origenes  = collect($items)->pluck('id_departamento')->map(fn ($v) => (int) $v);

            // El destino no puede ser ninguna de las unidades de origen del retiro
            if (!$idDestino || $origenes->contains($idDestino) || !Departamentos::find($idDestino)) {
                return response()->json(['success' => 8]);
            }

            if (trim((string) $request->descripcion) === '') {
                return response()->json(['success' => 4]);
            }
        }

        try {
            DB::beginTransaction();

            $retiro = RetiroContrato::create([
                'id_contrato'             => $idContrato,
                'otra_unidad'             => $otraUnidad ? 1 : 0,
                'id_departamento_destino' => $otraUnidad ? $idDestino : null,
                'fecha'                   => $request->fecha,
                'no_factura'              => $request->no_factura !== '' ? $request->no_factura : null,
                'fecha_factura'           => $request->fecha_factura !== '' ? $request->fecha_factura : null,
                'descripcion'             => $request->descripcion,
            ]);

            foreach ($items as $item) {
                $idOrigen = (int) $item['id_departamento'];

                $detalle = ContratoDetalle::where('id', $item['id_contrato_detalle'])
                    ->lockForUpdate()
                    ->first();

                if (!$detalle || $detalle->id_contrato != $idContrato) {
                    DB::rollBack();
                    return response()->json(['success' => 6]);
                }

                // Cantidad asignada a la unidad de origen de ESTA línea
                $asignado = DB::table('contrato_detalle_departamento')
                    ->where('id_contrato_detalle', $detalle->id)
                    ->where('id_departamento', $idOrigen)
                    ->value('cantidad');

                if ($asignado === null) {
                    DB::rollBack();
                    return response()->json(['success' => 7]);
                }

                $retiradoUnidad = (int) RetiroContratoDetalle::where('id_contrato_detalle', $detalle->id)
                    ->where('id_departamento', $idOrigen)
                    ->sum('cantidad');

                // Incluye las líneas ya insertadas en esta misma transacción
                $retiradoTotal = (int) RetiroContratoDetalle::where('id_contrato_detalle', $detalle->id)->sum('cantidad');

                $cantidad   = (int) $item['cantidad'];
                $disponible = max(min((int) $asignado - $retiradoUnidad, $detalle->cantidad - $retiradoTotal), 0);

                if ($cantidad <= 0 || $cantidad > $disponible) {
                    DB::rollBack();
                    return response()->json([
                        'success'         => 2,
                        'nombre_material' => $detalle->nombre,
                        'unidad'          => Departamentos::where('id', $idOrigen)->value('nombre'),
                        'cantidad_pedida' => $cantidad,
                        'disponible'      => $disponible,
                    ]);
                }

                RetiroContratoDetalle::create([
                    'id_retiro_contrato'  => $retiro->id,
                    'id_contrato_detalle' => $detalle->id,
                    'id_departamento'     => $idOrigen,
                    'cantidad'            => $cantidad,
                ]);
            }

            DB::commit();
            return response()->json(['success' => 10]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error guardarRetiro: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => 0]);
        }
    }


}
