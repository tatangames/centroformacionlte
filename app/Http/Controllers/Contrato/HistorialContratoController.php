<?php

namespace App\Http\Controllers\Contrato;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\ContratoDetalle;
use App\Models\Departamentos;
use App\Models\RetiroContrato;
use App\Models\RetiroContratoDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HistorialContratoController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // Helper: disponible de un ítem para una unidad de origen
    //   = asignado a la unidad - retirado por la unidad
    //   sin pasar de lo que queda del ítem en total.
    // Devuelve null si el ítem no está asignado a esa unidad.
    // $excluirLinea: id de una línea de retiro a ignorar (al editarla).
    // ─────────────────────────────────────────────────────────────
    private function calcularDisponible(ContratoDetalle $detalle, ?int $idDepto, ?int $excluirLinea = null): ?int
    {
        $query = RetiroContratoDetalle::where('id_contrato_detalle', $detalle->id);

        if ($excluirLinea) {
            $query->where('id', '!=', $excluirLinea);
        }

        $retiradoTotal = (int) (clone $query)->sum('cantidad');
        $restanteItem  = (int) $detalle->cantidad - $retiradoTotal;

        // Línea sin unidad (retiros anteriores): solo se controla el total del ítem
        if (!$idDepto) {
            return max($restanteItem, 0);
        }

        $asignado = DB::table('contrato_detalle_departamento')
            ->where('id_contrato_detalle', $detalle->id)
            ->where('id_departamento', $idDepto)
            ->value('cantidad');

        if ($asignado === null) {
            return null;
        }

        $retiradoUnidad = (int) (clone $query)->where('id_departamento', $idDepto)->sum('cantidad');

        return max(min((int) $asignado - $retiradoUnidad, $restanteItem), 0);
    }

    public function indexHistorialSalidas()
    {
        $arrayContratos     = Contrato::orderBy('nombre_proceso')->get();
        $arrayDepartamentos = Departamentos::orderBy('nombre')->get(['id', 'nombre']);

        return view('backend.admin.procesocontrato.historial.vistahistorialsalidas',
            compact('arrayContratos', 'arrayDepartamentos'));
    }

    public function tablaHistorialSalidas(Request $request)
    {
        $arrayRetiros = RetiroContrato::with([
            'contrato.proveedor',
            'departamentoDestino',
            'detalle.departamentoOrigen',
            'detalle.contratoDetalle',
        ])
            ->when($request->contrato, fn($q) =>
            $q->where('id_contrato', $request->contrato)
            )
            ->when($request->fecha_desde, fn($q) =>
            $q->whereDate('fecha', '>=', $request->fecha_desde)
            )
            ->when($request->fecha_hasta, fn($q) =>
            $q->whereDate('fecha', '<=', $request->fecha_hasta)
            )
            // Retiros que tengan al menos una línea de esa unidad de origen
            ->when($request->unidad, function ($q) use ($request) {
                $q->whereHas('detalle', function ($q2) use ($request) {
                    $q2->where('id_departamento', $request->unidad);
                });
            })
            ->when($request->material, function ($q) use ($request) {
                $busqueda = '%' . $request->material . '%';
                $q->whereHas('detalle.contratoDetalle', function ($q2) use ($busqueda) {
                    $q2->where('nombre', 'LIKE', $busqueda);
                });
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                $item->fecha_fmt = date('d/m/Y', strtotime($item->fecha));
                $item->fecha_factura_fmt = $item->fecha_factura
                    ? date('d/m/Y', strtotime($item->fecha_factura))
                    : '';

                // Unidades de origen distintas del retiro
                $item->origenes = $item->detalle
                    ->map(fn($d) => optional($d->departamentoOrigen)->nombre)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                // Líneas de retiros anteriores (sin unidad de origen)
                $item->sin_origen = $item->detalle->contains(fn($d) => !$d->id_departamento);

                $item->total_num = round(
                    $item->detalle->sum(fn($d) => $d->cantidad * (float) optional($d->contratoDetalle)->precio),
                    4
                );
                $item->total_fmt = '$' . number_format($item->total_num, 2);

                return $item;
            });

        return view('backend.admin.procesocontrato.historial.tablahistorialsalidas',
            compact('arrayRetiros'));
    }

    public function informacionSalida(Request $request)
    {
        $retiro = RetiroContrato::find($request->id);

        if (!$retiro) {
            return response()->json(['success' => 0]);
        }

        // Unidades de origen de las líneas (el destino no puede ser ninguna de ellas)
        $origenes = RetiroContratoDetalle::where('id_retiro_contrato', $retiro->id)
            ->whereNotNull('id_departamento')
            ->distinct()
            ->pluck('id_departamento');

        return response()->json([
            'success'  => 1,
            'retiro'   => [
                'id'                      => $retiro->id,
                'fecha'                   => $retiro->fecha,
                'no_factura'              => $retiro->no_factura,
                'fecha_factura'           => $retiro->fecha_factura,
                'descripcion'             => $retiro->descripcion,
                'otra_unidad'             => (int) $retiro->otra_unidad,
                'id_departamento_destino' => $retiro->id_departamento_destino,
            ],
            'origenes' => $origenes->values(),
        ]);
    }

    public function editarSalida(Request $request)
    {
        $retiro = RetiroContrato::with('contrato')->find($request->id);

        if (!$retiro) {
            return response()->json(['success' => 0]);
        }

        $contrato = $retiro->contrato;

        if ($contrato && $contrato->estado == 'finalizado') {
            return response()->json([
                'success' => 3,
                'mensaje' => 'El contrato está finalizado, no se puede editar este retiro',
            ]);
        }

        // La fecha del retiro no puede ser anterior al inicio del contrato
        if ($contrato && $request->fecha < $contrato->fecha_inicio) {
            return response()->json([
                'success'      => 2,
                'fecha_salida' => Carbon::parse($request->fecha)->format('d-m-Y'),
                'fecha_limite' => Carbon::parse($contrato->fecha_inicio)->format('d-m-Y'),
                'motivo'       => 'anterior al inicio del contrato',
            ]);
        }

        // Unidad destino (solo si es para otra unidad)
        $otraUnidad = (int) $request->otra_unidad === 1;
        $idDestino  = null;

        if ($otraUnidad) {
            $idDestino = (int) $request->id_departamento_destino;

            if (!$idDestino || !Departamentos::find($idDestino)) {
                return response()->json([
                    'success' => 4,
                    'mensaje' => 'Seleccione una Unidad Destino válida',
                ]);
            }

            // El destino no puede ser ninguna de las unidades de origen del retiro
            $esOrigen = RetiroContratoDetalle::where('id_retiro_contrato', $retiro->id)
                ->where('id_departamento', $idDestino)
                ->exists();

            if ($esOrigen) {
                return response()->json([
                    'success' => 4,
                    'mensaje' => 'La Unidad Destino no puede ser igual a una Unidad de Origen de este retiro',
                ]);
            }

            if (trim((string) $request->descripcion) === '') {
                return response()->json(['success' => 5]);
            }
        }

        $retiro->fecha                   = $request->fecha;
        $retiro->no_factura              = $request->no_factura ?: null;
        $retiro->fecha_factura           = $request->fecha_factura ?: null;
        $retiro->descripcion             = $request->descripcion ?: null;
        $retiro->otra_unidad             = $otraUnidad ? 1 : 0;
        $retiro->id_departamento_destino = $otraUnidad ? $idDestino : null;
        $retiro->save();

        return response()->json(['success' => 1]);
    }

    public function eliminarSalida(Request $request)
    {
        $retiro = RetiroContrato::with('contrato')->find($request->id);

        if (!$retiro) {
            return response()->json(['success' => 0]);
        }

        if ($retiro->contrato && $retiro->contrato->estado == 'finalizado') {
            return response()->json([
                'success' => 3,
                'mensaje' => 'El contrato está finalizado, no se puede eliminar este retiro',
            ]);
        }

        try {
            DB::beginTransaction();

            $retiro->detalle()->delete();
            $retiro->delete();

            DB::commit();
            return response()->json(['success' => 1]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminarSalida: ' . $e->getMessage());
            return response()->json(['success' => 0]);
        }
    }

    public function detalleSalida(Request $request)
    {
        $retiro = RetiroContrato::with('departamentoDestino')->find($request->id);

        if (!$retiro) {
            return response()->json(['success' => 0]);
        }

        $detalle = $retiro->detalle()
            ->with(['contratoDetalle.unidadMedida', 'departamentoOrigen'])
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                $precio   = (float) ($item->contratoDetalle->precio ?? 0);
                $subtotal = round($item->cantidad * $precio, 4);

                return [
                    'id'              => $item->id, // id del retiro_contrato_detalle
                    'codigo'          => $item->id_contrato_detalle,
                    'material'        => $item->contratoDetalle->nombre ?? '',
                    'unidad'          => $item->contratoDetalle->unidadMedida->nombre ?? '',
                    'origen'          => optional($item->departamentoOrigen)->nombre,
                    'cantidad_salida' => $item->cantidad,
                    'precio'          => number_format($precio, 4),
                    'subtotal'        => number_format($subtotal, 2),
                    'subtotal_num'    => $subtotal,
                ];
            })
            ->values();

        return response()->json([
            'success' => 1,
            'detalle' => $detalle,
            'total'   => number_format($detalle->sum('subtotal_num'), 2),
            'destino' => $retiro->otra_unidad ? optional($retiro->departamentoDestino)->nombre : null,
        ]);
    }

    public function vistaExtrasSalida($id)
    {
        $retiro = RetiroContrato::with(['contrato', 'departamentoDestino'])->find($id);

        if (!$retiro || $retiro->contrato->estado == 'finalizado') {
            return redirect()->route('admin.historial.contratos.index')
                ->with('error', 'El contrato está finalizado, no se pueden agregar extras');
        }

        // Unidades con ítems asignados en este contrato
        $idsUnidades = DB::table('contrato_detalle_departamento as cdd')
            ->join('contrato_detalle as cd', 'cd.id', '=', 'cdd.id_contrato_detalle')
            ->where('cd.id_contrato', $retiro->id_contrato)
            ->pluck('cdd.id_departamento')
            ->unique();

        // Si el retiro es para otra unidad, el destino no puede ser origen
        $arrayUnidades = Departamentos::whereIn('id', $idsUnidades)
            ->when($retiro->otra_unidad && $retiro->id_departamento_destino, function ($q) use ($retiro) {
                $q->where('id', '!=', $retiro->id_departamento_destino);
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('backend.admin.procesocontrato.historial.vistaextrassalidas',
            compact('retiro', 'arrayUnidades'));
    }

    public function guardarExtrasSalida(Request $request)
    {
        $retiro = RetiroContrato::with('contrato')->find($request->id_retiro);

        if (!$retiro) {
            return response()->json(['success' => 0]);
        }

        if ($retiro->contrato->estado == 'finalizado') {
            return response()->json(['success' => 3, 'mensaje' => 'El contrato está finalizado']);
        }

        $contenedor = json_decode($request->contenedorArray, true);

        if (empty($contenedor) || !is_array($contenedor)) {
            return response()->json(['success' => 0]);
        }

        // Un mismo ítem no puede repetirse para la misma unidad de origen
        $claves = collect($contenedor)->map(function ($i) {
            return (int) $i['infoIdContratoDetalle'] . '-' . (int) $i['infoIdDepartamento'];
        });

        if ($claves->count() !== $claves->unique()->count()) {
            return response()->json(['success' => 5]);
        }

        try {
            DB::beginTransaction();

            foreach ($contenedor as $index => $item) {
                $fila     = $index + 1;
                $idOrigen = (int) $item['infoIdDepartamento'];
                $cantidad = (int) $item['infoCantidad'];

                if ($cantidad <= 0) {
                    DB::rollBack();
                    return response()->json(['success' => 0]);
                }

                // Unidad de origen obligatoria y distinta al destino del retiro
                if (!$idOrigen
                    || ($retiro->otra_unidad && $idOrigen === (int) $retiro->id_departamento_destino)) {
                    DB::rollBack();
                    return response()->json(['success' => 4, 'fila' => $fila]);
                }

                $contratoDetalle = ContratoDetalle::where('id', $item['infoIdContratoDetalle'])
                    ->lockForUpdate()
                    ->first();

                if (!$contratoDetalle || $contratoDetalle->id_contrato != $retiro->id_contrato) {
                    DB::rollBack();
                    return response()->json(['success' => 4, 'fila' => $fila]);
                }

                $disponible = $this->calcularDisponible($contratoDetalle, $idOrigen);

                // null = el ítem no está asignado a esa unidad
                if ($disponible === null) {
                    DB::rollBack();
                    return response()->json(['success' => 4, 'fila' => $fila]);
                }

                if ($cantidad > $disponible) {
                    DB::rollBack();
                    return response()->json([
                        'success'    => 2,
                        'fila'       => $fila,
                        'disponible' => $disponible,
                    ]);
                }

                RetiroContratoDetalle::create([
                    'id_retiro_contrato'  => $retiro->id,
                    'id_contrato_detalle' => $contratoDetalle->id,
                    'id_departamento'     => $idOrigen,
                    'cantidad'            => $cantidad,
                ]);
            }

            DB::commit();
            return response()->json(['success' => 10]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardarExtrasSalida: ' . $e->getMessage());
            return response()->json(['success' => 0]);
        }
    }

    // ── Autocompletar ítems del contrato con disponibilidad en una unidad ──
    public function buscarMaterialDisponible(Request $request)
    {
        $texto      = (string) $request->input('query', '');
        $idContrato = $request->input('id_contrato');
        $idDepto    = (int) $request->input('id_departamento');

        if (!$idDepto) {
            return response()->json([]);
        }

        $items = ContratoDetalle::with([
            'unidadMedida',
            'departamentos' => fn($q) => $q->where('departamentos.id', $idDepto),
        ])
            ->where('id_contrato', $idContrato)
            ->whereHas('departamentos', fn($q) => $q->where('departamentos.id', $idDepto))
            ->where('nombre', 'like', '%' . $texto . '%')
            ->orderBy('nombre')
            ->limit(30)
            ->get();

        $ids = $items->pluck('id');

        $retiradoTotal = DB::table('retiro_contrato_detalle')
            ->whereIn('id_contrato_detalle', $ids)
            ->groupBy('id_contrato_detalle')
            ->selectRaw('id_contrato_detalle, SUM(cantidad) as total')
            ->pluck('total', 'id_contrato_detalle');

        $retiradoUnidad = DB::table('retiro_contrato_detalle')
            ->where('id_departamento', $idDepto)
            ->whereIn('id_contrato_detalle', $ids)
            ->groupBy('id_contrato_detalle')
            ->selectRaw('id_contrato_detalle, SUM(cantidad) as total')
            ->pluck('total', 'id_contrato_detalle');

        $resultados = $items->map(function ($item) use ($retiradoTotal, $retiradoUnidad) {
            $asignado        = (int) $item->departamentos->first()->pivot->cantidad;
            $retUnidad       = (int) ($retiradoUnidad[$item->id] ?? 0);
            $disponibleTotal = (int) $item->cantidad - (int) ($retiradoTotal[$item->id] ?? 0);

            return [
                'id'              => $item->id,
                'nombre'          => $item->nombre,
                'unidad'          => $item->unidadMedida->nombre ?? '',
                'asignado'        => $asignado,
                'retirado_unidad' => $retUnidad,
                'disponible'      => max(min($asignado - $retUnidad, $disponibleTotal), 0),
                'precio'          => '$' . number_format($item->precio, 4),
            ];
        })
            ->filter(fn($item) => $item['disponible'] > 0)
            ->values();

        return response()->json($resultados);
    }

    public function materialDisponibilidad(Request $request)
    {
        $item = ContratoDetalle::with('unidadMedida')->find($request->input('id'));

        if (!$item) {
            return response()->json(['success' => 0]);
        }

        $idDepto    = $request->input('id_departamento') ? (int) $request->input('id_departamento') : null;
        $disponible = $this->calcularDisponible($item, $idDepto) ?? 0;

        return response()->json([
            'success'    => 1,
            'disponible' => $disponible <= 0 ? 1 : 0,
            'cantidad'   => $disponible,
            'nombre'     => $item->nombre,
            'unidad'     => $item->unidadMedida->nombre ?? '',
        ]);
    }

    public function editarDetalleSalida(Request $request)
    {
        $cantidad = (int) $request->cantidad;

        if ($cantidad <= 0) {
            return response()->json(['success' => 0]);
        }

        try {
            DB::beginTransaction();

            $detalle = RetiroContratoDetalle::with('retiroContrato.contrato')
                ->lockForUpdate()
                ->find($request->id_detalle);

            if (!$detalle) {
                DB::rollBack();
                return response()->json(['success' => 0]);
            }

            $contrato = $detalle->retiroContrato->contrato ?? null;

            // No permitir editar si el contrato ya está finalizado
            if ($contrato && $contrato->estado == 'finalizado') {
                DB::rollBack();
                return response()->json([
                    'success' => 3,
                    'mensaje' => 'El contrato está finalizado, no se puede modificar este ítem',
                ]);
            }

            $contratoDetalle = ContratoDetalle::lockForUpdate()->find($detalle->id_contrato_detalle);

            if (!$contratoDetalle) {
                DB::rollBack();
                return response()->json(['success' => 0]);
            }

            // Disponible para la unidad de origen de esta línea,
            // sin contar la propia línea que se está editando
            $disponible = $this->calcularDisponible(
                $contratoDetalle,
                $detalle->id_departamento ? (int) $detalle->id_departamento : null,
                $detalle->id
            );

            if ($disponible === null) {
                DB::rollBack();
                return response()->json([
                    'success' => 4,
                    'mensaje' => 'El ítem ya no está asignado a la unidad de origen de esta línea',
                ]);
            }

            if ($cantidad > $disponible) {
                DB::rollBack();
                return response()->json([
                    'success'    => 2,
                    'disponible' => $disponible,
                ]);
            }

            $detalle->cantidad = $cantidad;
            $detalle->save();

            DB::commit();
            return response()->json(['success' => 1]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error editarDetalleSalida: ' . $e->getMessage());
            return response()->json(['success' => 0]);
        }
    }

    public function eliminarDetalleSalida(Request $request)
    {
        $request->validate([
            'id_detalle' => 'required|integer|exists:retiro_contrato_detalle,id',
        ]);

        $detalle = RetiroContratoDetalle::with('retiroContrato.contrato')
            ->find($request->id_detalle);

        if (!$detalle) {
            return response()->json(['success' => 0]);
        }

        $contrato = $detalle->retiroContrato->contrato ?? null;

        if ($contrato && $contrato->estado == 'finalizado') {
            return response()->json([
                'success' => 3,
                'mensaje' => 'El contrato está finalizado, no se puede eliminar este ítem',
            ]);
        }

        $detalle->delete();

        return response()->json(['success' => 1]);
    }

    public function generarPDFSalidaContrato(Request $request)
    {
        $idRetiro = $request->input('id', '');

        $retiro = RetiroContrato::with([
            'contrato.proveedor',
            'departamentoDestino',
            'detalle.departamentoOrigen',
            'detalle.contratoDetalle.unidadMedida',
        ])->find($idRetiro);

        if (!$retiro) {
            abort(404, 'Retiro de contrato no encontrado');
        }

        $fechaFmt        = $retiro->fecha ? date('d/m/Y', strtotime($retiro->fecha)) : '';
        $fechaFacturaFmt = $retiro->fecha_factura ? date('d/m/Y', strtotime($retiro->fecha_factura)) : '';
        $noFactura       = $retiro->no_factura ?? '';
        $descripcion     = $retiro->descripcion ?? '';

        $nombreProceso   = $retiro->contrato ? htmlspecialchars($retiro->contrato->nombre_proceso) : '';
        $codigoContrato  = $retiro->contrato->codigo ?? '';
        $nombreProveedor = ($retiro->contrato && $retiro->contrato->proveedor)
            ? htmlspecialchars($retiro->contrato->proveedor->nombre)
            : '';

        // Fila extra si el retiro es para otra unidad
        $filaDestino = '';
        if ($retiro->otra_unidad) {
            $filaDestino = "
    <tr>
        <td colspan='2' style='padding:4px 0;'>
            <strong>RETIRO PARA OTRA UNIDAD (DESTINO):</strong> &nbsp; " . e(optional($retiro->departamentoDestino)->nombre ?? '') . "
        </td>
    </tr>";
        }

        $logoalcaldia = 'images/logo.png';

        $html = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'>
                <tr>
                    <td style='width:30%; text-align:left;'>
                        <img src='{$logoalcaldia}' style='height:38px'>
                    </td>
                    <td style='width:70%; text-align:left; color:#104e8c; font-size:13px; font-weight:bold; line-height:1.3;'>
                        SANTA ANA NORTE<br>EL SALVADOR
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
                   padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            FORMULARIO DE RETIRO DE CONTRATO
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Código:</strong></td>
                    <td width='60%' style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'></td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Versión:</strong></td>
                    <td style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'></td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha de vigencia:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<br>

<table width='100%' style='font-family:Arial, sans-serif; font-size:12px; border-collapse:collapse;'>
    <tr>
        <td width='50%' style='padding:4px 0;'>
            <strong>FECHA DE RETIRO:</strong> &nbsp; {$fechaFmt}
        </td>
        <td width='50%' style='padding:4px 0;'>
            <strong>NO. FACTURA:</strong> &nbsp; " . e($noFactura) . "
        </td>
    </tr>
    <tr>
        <td width='50%' style='padding:4px 0;'>
            <strong>FECHA DE FACTURA:</strong> &nbsp; {$fechaFacturaFmt}
        </td>
        <td width='50%' style='padding:4px 0;'>
            <strong>CÓDIGO CONTRATO:</strong> &nbsp; " . e($codigoContrato) . "
        </td>
    </tr>
    <tr>
        <td colspan='2' style='padding:4px 0;'>
            <strong>CONTRATO / PROCESO:</strong> &nbsp; {$nombreProceso}
        </td>
    </tr>
    <tr>
        <td colspan='2' style='padding:4px 0;'>
            <strong>PROVEEDOR:</strong> &nbsp; {$nombreProveedor}
        </td>
    </tr>{$filaDestino}
    <tr>
        <td colspan='2' style='padding:4px 0;'>
            <strong>DESCRIPCIÓN:</strong> &nbsp; " . e($descripcion) . "
        </td>
    </tr>
</table>

<br>

<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; font-size:12px;'>
    <thead>
        <tr>
            <th style='width:8%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>CANTIDAD</th>
            <th style='width:34%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>DESCRIPCION</th>
            <th style='width:10%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>U/M</th>
            <th style='width:18%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>UNIDAD DE ORIGEN</th>
            <th style='width:15%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>PRECIO UNIT.</th>
            <th style='width:15%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>";

        $totalGeneral = 0;

        // Agrupado por unidad de origen y luego por nombre del material
        $lineas = $retiro->detalle->sortBy(function ($f) {
            return (optional($f->departamentoOrigen)->nombre ?? '') . '|' . (optional($f->contratoDetalle)->nombre ?? '');
        });

        foreach ($lineas as $fila) {
            $cantidad   = (float) $fila->cantidad;
            $nombreItem = '';
            $unidadMed  = '';
            $precio     = 0;
            $origen     = optional($fila->departamentoOrigen)->nombre;
            $origenTxt  = $origen ? htmlspecialchars($origen) : '-';

            if ($fila->contratoDetalle) {
                $nombreItem = htmlspecialchars($fila->contratoDetalle->nombre);
                $precio     = (float) $fila->contratoDetalle->precio;
                if ($fila->contratoDetalle->unidadMedida) {
                    $unidadMed = htmlspecialchars($fila->contratoDetalle->unidadMedida->nombre);
                }
            }

            $subtotal      = $cantidad * $precio;
            $totalGeneral += $subtotal;

            $html .= "
    <tr>
        <td style='border:0.8px solid #000; padding:5px 8px; text-align:center;'>" . number_format($cantidad, 0) . "</td>
        <td style='border:0.8px solid #000; padding:5px 8px;'>{$nombreItem}</td>
        <td style='border:0.8px solid #000; padding:5px 8px; text-align:center;'>{$unidadMed}</td>
        <td style='border:0.8px solid #000; padding:5px 8px; text-align:center;'>{$origenTxt}</td>
        <td style='border:0.8px solid #000; padding:5px 8px; text-align:right;'>$" . number_format($precio, 2) . "</td>
        <td style='border:0.8px solid #000; padding:5px 8px; text-align:right;'>$" . number_format($subtotal, 2) . "</td>
    </tr>";
        }

        $html .= "
    </tbody>
    <tfoot>
        <tr>
            <td colspan='5' style='border:0.8px solid #000; padding:6px 8px; text-align:right; font-weight:bold;'>TOTAL</td>
            <td style='border:0.8px solid #000; padding:6px 8px; text-align:right; font-weight:bold;'>$" . number_format($totalGeneral, 2) . "</td>
        </tr>
    </tfoot>
</table>

<br><br><br>";

        $mpdf = new \Mpdf\Mpdf([
            'tempDir'       => sys_get_temp_dir(),
            'format'        => 'LETTER',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
        ]);

        $mpdf->SetTitle('Formulario de Retiro de Contrato');
        $mpdf->showImageErrors = false;

        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML($html, 2);
        $mpdf->Output('retiro_contrato_' . $retiro->id . '.pdf', 'I');
    }
}
