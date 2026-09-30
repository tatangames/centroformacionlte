<?php

namespace App\Http\Controllers\Contrato;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\ContratoDetalle;
use App\Models\Departamentos;
use App\Models\RetiroContrato;
use App\Models\RetiroContratoDetalle;
use Illuminate\Http\Request;

class ReportesContratoController extends Controller
{
    public function index()
    {
        $contratos     = Contrato::orderBy('nombre_proceso')->get();
        $departamentos = Departamentos::orderBy('nombre')->get(['id', 'nombre']);

        return view('backend.admin.procesocontrato.reportes.vistareportescontrato',
            compact('contratos', 'departamentos'));
    }

    // ────────────────────────────────────────────────────────────
    // 1) SALDOS Y DISPONIBILIDAD DE UN CONTRATO (con desglose por unidad)
    // ────────────────────────────────────────────────────────────
    public function pdfSaldosContrato(Request $request)
    {
        $request->validate([
            'idcontrato' => 'required|integer|exists:contrato,id',
        ]);

        $contrato     = Contrato::with('proveedor')->findOrFail($request->idcontrato);
        $idDepto      = $this->idUnidad($request);
        $nombreUnidad = $idDepto ? Departamentos::where('id', $idDepto)->value('nombre') : null;

        $items = ContratoDetalle::with(['unidadMedida', 'departamentos', 'retiroDetalles'])
            ->where('id_contrato', $contrato->id)
            ->when($idDepto, function ($q) use ($idDepto) {
                $q->whereHas('departamentos', fn($q2) => $q2->where('departamentos.id', $idDepto));
            })
            ->orderBy('nombre')
            ->get();

        $filasHtml       = '';
        $totalContratado = 0;
        $totalEjecutado  = 0;
        $totalDisponible = 0;

        foreach ($items as $item) {
            $precio   = (float) $item->precio;
            $retTotal = (int) $item->retiroDetalles->sum('cantidad');

            $unidades = $item->departamentos->sortBy('nombre')->values()->map(function ($dep) use ($item) {
                $asignado = (int) $dep->pivot->cantidad;
                $ret      = (int) $item->retiroDetalles->where('id_departamento', $dep->id)->sum('cantidad');

                return [
                    'id'         => $dep->id,
                    'nombre'     => $dep->nombre,
                    'asignado'   => $asignado,
                    'retirado'   => $ret,
                    'disponible' => max($asignado - $ret, 0),
                ];
            });

            $um = $item->unidadMedida->nombre ?? '';

            if ($idDepto) {
                // Una unidad: los números son los de esa unidad
                $u = $unidades->firstWhere('id', $idDepto);
                if (!$u) continue;

                $contratado = $u['asignado'];
                $retirado   = $u['retirado'];
                $disponible = max(min($u['disponible'], (int) $item->cantidad - $retTotal), 0);

                $totalContratado += $contratado * $precio;
                $totalEjecutado  += $retirado * $precio;
                $totalDisponible += $disponible * $precio;

                $filasHtml .= $this->filaSaldo(e($item->nombre), e($um), e($nombreUnidad), $contratado, $retirado, $disponible, $precio, false);
                continue;
            }

            // Todas las unidades: fila del ítem + desglose por unidad
            $contratado = (int) $item->cantidad;
            $disponible = $contratado - $retTotal;

            $totalContratado += $contratado * $precio;
            $totalEjecutado  += $retTotal * $precio;
            $totalDisponible += $disponible * $precio;

            $filasHtml .= $this->filaSaldo(e($item->nombre), e($um), 'Todas', $contratado, $retTotal, $disponible, $precio, true);

            foreach ($unidades as $u) {
                $filasHtml .= $this->filaSaldo('', '', e($u['nombre']), $u['asignado'], $u['retirado'], $u['disponible'], $precio, false);
            }

            // Retiros anteriores que no tienen unidad de origen
            $sinUnidad = (int) $item->retiroDetalles->whereNull('id_departamento')->sum('cantidad');
            if ($sinUnidad > 0) {
                $filasHtml .= "
<tr>
    <td style='font-size:11px;'></td>
    <td style='font-size:11px;'></td>
    <td style='font-size:11px; color:#777;'>Sin unidad</td>
    <td style='font-size:11px; text-align:center;'>-</td>
    <td style='font-size:11px; text-align:center;'>" . number_format($sinUnidad, 0) . "</td>
    <td style='font-size:11px; text-align:center;'>-</td>
    <td style='font-size:11px; text-align:right;'></td>
    <td style='font-size:11px; text-align:right;'></td>
</tr>";
            }
        }

        if ($filasHtml === '') {
            $filasHtml = "
<tr>
    <td colspan='8' style='text-align:center; padding:14px; color:#777;'>
        No hay ítems que coincidan con los criterios seleccionados.
    </td>
</tr>";
        }

        $tabla  = $this->encabezado('REPORTE DE SALDOS Y DISPONIBILIDAD DE CONTRATO', 'Fecha de vigencia');
        $tabla .= $this->datosContrato($contrato, [
            'UNIDAD' => $nombreUnidad ?? 'Todas las unidades',
        ]);

        $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:24%; font-size:12px;'>Ítem</td>
            <td style='font-weight:bold; width:7%; font-size:12px; text-align:center;'>U/M</td>
            <td style='font-weight:bold; width:19%; font-size:12px;'>Unidad</td>
            <td style='font-weight:bold; width:10%; font-size:12px; text-align:center;'>" . ($idDepto ? 'Asignado' : 'Contratado') . "</td>
            <td style='font-weight:bold; width:9%; font-size:12px; text-align:center;'>Retirado</td>
            <td style='font-weight:bold; width:10%; font-size:12px; text-align:center;'>Disponible</td>
            <td style='font-weight:bold; width:9%; font-size:12px; text-align:right;'>Unit.</td>
            <td style='font-weight:bold; width:12%; font-size:12px; text-align:right;'>Monto Disp.</td>
        </tr>
        $filasHtml
    </tbody>
</table>";

        $tabla .= $this->bloqueTotales([
            'Contratado' => $totalContratado,
            'Ejecutado'  => $totalEjecutado,
            'Disponible' => $totalDisponible,
        ]);

        return $this->salidaPdf($tabla, 'reporte_saldos_contrato_' . $contrato->id . '.pdf');
    }

    // Fila de la tabla de saldos ($principal = fila del ítem completo, en negrita y sombreada)
    private function filaSaldo(string $nombre, string $um, string $unidad, int $contratado, int $retirado, int $disponible, float $precio, bool $principal): string
    {
        $estilo = $principal
            ? 'font-size:12px; font-weight:bold; background:#f5f5f5;'
            : 'font-size:11px;';

        return "
<tr>
    <td style='$estilo'>$nombre</td>
    <td style='$estilo text-align:center;'>$um</td>
    <td style='$estilo'>$unidad</td>
    <td style='$estilo text-align:center;'>" . number_format($contratado, 0) . "</td>
    <td style='$estilo text-align:center;'>" . number_format($retirado, 0) . "</td>
    <td style='$estilo text-align:center;'>" . number_format($disponible, 0) . "</td>
    <td style='$estilo text-align:right;'>$" . number_format($precio, 2) . "</td>
    <td style='$estilo text-align:right;'>$" . number_format($disponible * $precio, 2) . "</td>
</tr>";
    }

    // ────────────────────────────────────────────────────────────
    // 2) MOVIMIENTOS (RETIROS) DE UN CONTRATO POR PERÍODO
    // ────────────────────────────────────────────────────────────
    public function pdfMovimientosContrato(Request $request)
    {
        $request->validate([
            'idcontrato' => 'required|integer|exists:contrato,id',
            'desde'      => 'required|date',
            'hasta'      => 'required|date|after_or_equal:desde',
        ]);

        $contrato     = Contrato::with('proveedor')->findOrFail($request->idcontrato);
        $idDepto      = $this->idUnidad($request);
        $nombreUnidad = $idDepto ? Departamentos::where('id', $idDepto)->value('nombre') : null;

        $start      = date('Y-m-d 00:00:00', strtotime($request->desde));
        $end        = date('Y-m-d 23:59:59', strtotime($request->hasta));
        $fechaLabel = date('d-m-Y', strtotime($request->desde)) . '  -  ' . date('d-m-Y', strtotime($request->hasta));

        $retiros = RetiroContrato::with([
            'departamentoDestino',
            'detalle.departamentoOrigen',
            'detalle.contratoDetalle.unidadMedida',
        ])
            ->where('id_contrato', $contrato->id)
            ->whereBetween('fecha', [$start, $end])
            ->when($idDepto, function ($q) use ($idDepto) {
                $q->whereHas('detalle', fn($q2) => $q2->where('id_departamento', $idDepto));
            })
            ->orderBy('fecha', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        $tabla  = $this->encabezado('REPORTE DE MOVIMIENTOS DE CONTRATO POR PERÍODO', 'Fecha');
        $tabla .= $this->datosContrato($contrato, [
            'PERIODO' => $fechaLabel,
            'UNIDAD'  => $nombreUnidad ?? 'Todas las unidades',
        ]);

        $granTotal = 0;
        $resumen   = []; // nombre de unidad => [cantidad de líneas, monto]

        if ($retiros->isEmpty()) {
            $tabla .= "<p style='text-align:center; color:#777; padding:14px;'>No hay retiros registrados en el período seleccionado.</p>";
        }

        foreach ($retiros as $retiro) {
            $fechaFmt        = date('d-m-Y', strtotime($retiro->fecha));
            $fechaFacturaFmt = $retiro->fecha_factura ? date('d-m-Y', strtotime($retiro->fecha_factura)) : '';
            $destino         = $retiro->otra_unidad ? (optional($retiro->departamentoDestino)->nombre ?? '') : '-';

            $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse; margin-top:6px;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:14%; font-size:12px;'>Fecha Registrado</td>
            <td style='font-weight:bold; width:14%; font-size:12px;'>Fecha Factura</td>
            <td style='font-weight:bold; width:16%; font-size:12px;'>No. Factura</td>
            <td style='font-weight:bold; width:18%; font-size:12px;'>Destino (otra unidad)</td>
            <td style='font-weight:bold; width:38%; font-size:12px;'>Descripción</td>
        </tr>
        <tr>
            <td style='font-size:12px;'>$fechaFmt</td>
            <td style='font-size:12px;'>$fechaFacturaFmt</td>
            <td style='font-size:12px;'>" . e($retiro->no_factura ?? '') . "</td>
            <td style='font-size:12px;'>" . e($destino) . "</td>
            <td style='font-size:12px;'>" . e($retiro->descripcion ?? '') . "</td>
        </tr>
    </tbody>
</table>";

            $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:30%; font-size:12px;'>Ítem</td>
            <td style='font-weight:bold; width:9%; font-size:12px; text-align:center;'>U/M</td>
            <td style='font-weight:bold; width:21%; font-size:12px;'>Unidad de Origen</td>
            <td style='font-weight:bold; width:10%; font-size:12px; text-align:center;'>Cantidad</td>
            <td style='font-weight:bold; width:14%; font-size:12px; text-align:right;'>Precio Unit.</td>
            <td style='font-weight:bold; width:16%; font-size:12px; text-align:right;'>Subtotal</td>
        </tr>";

            // Con filtro de unidad solo se muestran las líneas de esa unidad
            $lineas = $retiro->detalle
                ->when($idDepto, fn($c) => $c->where('id_departamento', $idDepto))
                ->sortBy(fn($l) => (optional($l->departamentoOrigen)->nombre ?? 'zzz') . '|' . (optional($l->contratoDetalle)->nombre ?? ''));

            $subtotal = 0;

            foreach ($lineas as $item) {
                $cd     = $item->contratoDetalle;
                $precio = (float) ($cd->precio ?? 0);
                $sub    = $precio * $item->cantidad;
                $origen = optional($item->departamentoOrigen)->nombre ?? 'Sin unidad';

                $subtotal  += $sub;
                $granTotal += $sub;

                $resumen[$origen] = $resumen[$origen] ?? [0, 0];
                $resumen[$origen][0] += 1;
                $resumen[$origen][1] += $sub;

                $tabla .= "
        <tr>
            <td style='font-size:12px;'>" . e($cd->nombre ?? '') . "</td>
            <td style='font-size:12px; text-align:center;'>" . e($cd->unidadMedida->nombre ?? '') . "</td>
            <td style='font-size:12px;'>" . e($origen) . "</td>
            <td style='font-size:12px; text-align:center;'>" . number_format($item->cantidad, 0) . "</td>
            <td style='font-size:12px; text-align:right;'>$" . number_format($precio, 2) . "</td>
            <td style='font-size:12px; text-align:right;'>$" . number_format($sub, 2) . "</td>
        </tr>";
            }

            $tabla .= "
        <tr>
            <td colspan='5' style='font-weight:bold; font-size:12px; text-align:right; border-top:1px solid #000; padding-top:3px;'>
                Subtotal:
            </td>
            <td style='font-weight:bold; font-size:12px; text-align:right; border-top:1px solid #000; padding-top:3px;'>
                $" . number_format($subtotal, 2) . "
            </td>
        </tr>
    </tbody>
</table><br>";
        }

        // ── Resumen por unidad de origen ──
        if (!empty($resumen)) {
            ksort($resumen);

            $tabla .= "
<p style='font-family:Arial, sans-serif; font-size:13px; font-weight:bold; margin:6px 0 4px 0;'>Resumen por Unidad de Origen</p>
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:55%; font-size:12px;'>Unidad</td>
            <td style='font-weight:bold; width:20%; font-size:12px; text-align:center;'>Líneas</td>
            <td style='font-weight:bold; width:25%; font-size:12px; text-align:right;'>Monto</td>
        </tr>";

            foreach ($resumen as $nombre => $datos) {
                $tabla .= "
        <tr>
            <td style='font-size:12px;'>" . e($nombre) . "</td>
            <td style='font-size:12px; text-align:center;'>{$datos[0]}</td>
            <td style='font-size:12px; text-align:right;'>$" . number_format($datos[1], 2) . "</td>
        </tr>";
            }

            $tabla .= "
    </tbody>
</table>";
        }

        // ── Totales del contrato completo (Contratado / Ejecutado), FUERA de la tabla ──
        $detalleContrato = ContratoDetalle::with('retiroDetalles')
            ->where('id_contrato', $contrato->id)
            ->get();

        $bloques = [
            'Contratado Total' => $detalleContrato->sum(fn($d) => $d->cantidad * $d->precio),
            'Ejecutado Total'  => $detalleContrato->sum(fn($d) => $d->retiroDetalles->sum('cantidad') * $d->precio),
        ];

        if ($retiros->isNotEmpty()) {
            $bloques['Total por Período'] = $granTotal;
        }

        $tabla .= $this->bloqueTotales($bloques);

        return $this->salidaPdf($tabla, 'reporte_movimientos_contrato_' . $contrato->id . '.pdf');
    }

    // ────────────────────────────────────────────────────────────
    // 3) ESTADO GENERAL DE TODOS LOS CONTRATOS
    // ────────────────────────────────────────────────────────────
    public function pdfEstadoGeneralContratos(Request $request)
    {
        $estado = $request->estado ?: 'todos';

        $cantidadRetiros = RetiroContrato::selectRaw('id_contrato, COUNT(*) as total')
            ->groupBy('id_contrato')
            ->pluck('total', 'id_contrato');

        $contratos = Contrato::with(['proveedor', 'detalle.retiroDetalles'])
            ->when($estado !== 'todos', fn($q) => $q->where('estado', $estado))
            ->orderBy('nombre_proceso')
            ->get()
            ->map(function ($c) use ($cantidadRetiros) {
                $totalContratado = 0;
                $totalEjecutado  = 0;

                foreach ($c->detalle as $d) {
                    $retirado         = $d->retiroDetalles->sum('cantidad');
                    $totalContratado += $d->cantidad * $d->precio;
                    $totalEjecutado  += $retirado * $d->precio;
                }

                $c->monto_contratado = $totalContratado;
                $c->monto_ejecutado  = $totalEjecutado;
                $c->monto_disponible = $totalContratado - $totalEjecutado;
                $c->porcentaje       = $totalContratado > 0
                    ? round(($totalEjecutado / $totalContratado) * 100, 1)
                    : 0;
                $c->total_retiros    = (int) ($cantidadRetiros[$c->id] ?? 0);

                return $c;
            });

        $tabla = $this->encabezado('REPORTE GENERAL DE ESTADO DE CONTRATOS', 'Fecha');

        $filtroLabel = match ($estado) {
            'vigente'    => 'Solo Vigentes',
            'finalizado' => 'Solo Finalizados',
            default      => 'Todos los Contratos',
        };

        $tabla .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:10px; font-size:12px;'>
    <tr>
        <td style='width:12%; border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>FILTRO</td>
        <td style='border:0.8px solid #ccc; padding:6px 8px;'>$filtroLabel</td>
    </tr>
</table>";

        $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:8%; font-size:12px;'>Código</td>
            <td style='font-weight:bold; width:24%; font-size:12px;'>Contrato</td>
            <td style='font-weight:bold; width:16%; font-size:12px;'>Proveedor</td>
            <td style='font-weight:bold; width:8%; font-size:12px; text-align:center;'>Estado</td>
            <td style='font-weight:bold; width:11%; font-size:12px; text-align:right;'>Monto Total</td>
            <td style='font-weight:bold; width:11%; font-size:12px; text-align:right;'>Ejecutado</td>
            <td style='font-weight:bold; width:11%; font-size:12px; text-align:right;'>Disponible</td>
            <td style='font-weight:bold; width:6%; font-size:12px; text-align:center;'>% Av.</td>
            <td style='font-weight:bold; width:5%; font-size:12px; text-align:center;'>Retiros</td>
        </tr>";

        if ($contratos->isEmpty()) {
            $tabla .= "<tr><td colspan='9' style='text-align:center; padding:14px; color:#777;'>No hay contratos que coincidan con el filtro.</td></tr>";
        }

        foreach ($contratos as $c) {
            $estadoBadge = $c->estado == 'finalizado' ? 'Finalizado' : 'Vigente';
            $tabla .= "
        <tr>
            <td style='font-size:12px;'>" . e($c->codigo ?? '') . "</td>
            <td style='font-size:12px;'>" . e($c->nombre_proceso) . "</td>
            <td style='font-size:12px;'>" . e($c->proveedor->nombre ?? '') . "</td>
            <td style='font-size:12px; text-align:center;'>$estadoBadge</td>
            <td style='font-size:12px; text-align:right;'>$" . number_format($c->monto_contratado, 2) . "</td>
            <td style='font-size:12px; text-align:right;'>$" . number_format($c->monto_ejecutado, 2) . "</td>
            <td style='font-size:12px; text-align:right;'>$" . number_format($c->monto_disponible, 2) . "</td>
            <td style='font-size:12px; text-align:center;'>{$c->porcentaje}%</td>
            <td style='font-size:12px; text-align:center;'>{$c->total_retiros}</td>
        </tr>";
        }

        $tabla .= "
    </tbody>
</table>";

        if ($contratos->isNotEmpty()) {
            $tabla .= $this->bloqueTotales([
                'Contratado' => $contratos->sum('monto_contratado'),
                'Ejecutado'  => $contratos->sum('monto_ejecutado'),
                'Disponible' => $contratos->sum('monto_disponible'),
            ]);
        }

        // true = horizontal
        return $this->salidaPdf($tabla, 'reporte_general_contratos.pdf', true);
    }

    // ────────────────────────────────────────────────────────────
    // 4) SOLICITADO POR UNIDAD (de fecha a fecha)
    //    Agrupa los retiros por unidad de origen.
    //    detalle = 'resumido' (por ítem) | 'detallado' (línea por línea)
    // ────────────────────────────────────────────────────────────
    public function pdfSolicitudUnidades(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $idContrato = is_numeric($request->idcontrato) ? (int) $request->idcontrato : null;
        $idDepto    = $this->idUnidad($request);
        $detallado  = $request->detalle === 'detallado';

        $contrato       = $idContrato ? Contrato::find($idContrato) : null;
        $nombreUnidad   = $idDepto ? Departamentos::where('id', $idDepto)->value('nombre') : null;
        $nombreContrato = $contrato
            ? (($contrato->codigo ? $contrato->codigo . ' — ' : '') . $contrato->nombre_proceso)
            : 'Todos los contratos';

        $start      = date('Y-m-d 00:00:00', strtotime($request->desde));
        $end        = date('Y-m-d 23:59:59', strtotime($request->hasta));
        $fechaLabel = date('d-m-Y', strtotime($request->desde)) . '  -  ' . date('d-m-Y', strtotime($request->hasta));

        $lineas = RetiroContratoDetalle::with([
            'departamentoOrigen',
            'retiroContrato.contrato',
            'retiroContrato.departamentoDestino',
            'contratoDetalle.unidadMedida',
        ])
            ->whereHas('retiroContrato', function ($q) use ($start, $end, $idContrato) {
                $q->whereBetween('fecha', [$start, $end]);
                if ($idContrato) {
                    $q->where('id_contrato', $idContrato);
                }
            })
            ->when($idDepto, fn($q) => $q->where('id_departamento', $idDepto))
            ->get();

        // Un grupo por unidad de origen (las líneas sin unidad van al final)
        $grupos = [];
        foreach ($lineas->groupBy(fn($l) => $l->id_departamento ?: 0) as $idUnidad => $coleccion) {
            $nombre = $idUnidad ? (optional($coleccion->first()->departamentoOrigen)->nombre ?? 'Unidad') : 'Sin unidad (retiros anteriores)';

            $grupos[] = [
                'orden'   => $idUnidad ? mb_strtolower($nombre) : 'zzzz',
                'nombre'  => $nombre,
                'lineas'  => $coleccion,
                'retiros' => $coleccion->pluck('id_retiro_contrato')->unique()->count(),
                'items'   => $coleccion->pluck('id_contrato_detalle')->unique()->count(),
                'monto'   => $coleccion->sum(fn($l) => $l->cantidad * (float) optional($l->contratoDetalle)->precio),
            ];
        }
        usort($grupos, fn($a, $b) => strcmp($a['orden'], $b['orden']));

        $tabla  = $this->encabezado('REPORTE DE SOLICITUDES POR UNIDAD', 'Fecha');
        $tabla .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; font-size:12px; margin-bottom:10px;'>
    <tr>
        <td style='width:16%; border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>PERIODO</td>
        <td style='width:34%; border:0.8px solid #ccc; padding:6px 8px;'>" . e($fechaLabel) . "</td>
        <td style='width:15%; border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>UNIDAD</td>
        <td style='width:35%; border:0.8px solid #ccc; padding:6px 8px;'>" . e($nombreUnidad ?? 'Todas las unidades') . "</td>
    </tr>
    <tr>
        <td style='border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>CONTRATO</td>
        <td colspan='3' style='border:0.8px solid #ccc; padding:6px 8px;'>" . e($nombreContrato) . "</td>
    </tr>
    <tr>
        <td style='border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>DETALLE</td>
        <td colspan='3' style='border:0.8px solid #ccc; padding:6px 8px;'>" . ($detallado ? 'Detallado (línea por línea)' : 'Resumido (por ítem)') . "</td>
    </tr>
</table>";

        if (empty($grupos)) {
            $tabla .= "<p style='text-align:center; color:#777; padding:14px;'>No hay retiros registrados en el período seleccionado.</p>";
            return $this->salidaPdf($tabla, 'reporte_solicitudes_unidades.pdf');
        }

        // ── Resumen por unidad ──
        $tabla .= "
<p style='font-family:Arial, sans-serif; font-size:13px; font-weight:bold; margin:6px 0 4px 0;'>Resumen por Unidad</p>
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:46%; font-size:12px;'>Unidad</td>
            <td style='font-weight:bold; width:16%; font-size:12px; text-align:center;'>Retiros</td>
            <td style='font-weight:bold; width:16%; font-size:12px; text-align:center;'>Ítems distintos</td>
            <td style='font-weight:bold; width:22%; font-size:12px; text-align:right;'>Monto</td>
        </tr>";

        $totalMonto = 0;
        foreach ($grupos as $g) {
            $totalMonto += $g['monto'];
            $tabla .= "
        <tr>
            <td style='font-size:12px;'>" . e($g['nombre']) . "</td>
            <td style='font-size:12px; text-align:center;'>{$g['retiros']}</td>
            <td style='font-size:12px; text-align:center;'>{$g['items']}</td>
            <td style='font-size:12px; text-align:right;'>$" . number_format($g['monto'], 2) . "</td>
        </tr>";
        }

        $tabla .= "
    </tbody>
</table><br>";

        // ── Detalle por unidad ──
        foreach ($grupos as $g) {
            $tabla .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-top:8px;'>
    <tr>
        <td style='background:#104e8c; color:#fff; font-size:13px; font-weight:bold; padding:6px 8px;'>" . e($g['nombre']) . "</td>
        <td style='background:#104e8c; color:#fff; font-size:12px; padding:6px 8px; text-align:right;'>
            {$g['retiros']} retiro(s) &nbsp;|&nbsp; $" . number_format($g['monto'], 2) . "
        </td>
    </tr>
</table>";

            if ($detallado) {
                $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:10%; font-size:11px;'>Fecha</td>
            <td style='font-weight:bold; width:13%; font-size:11px;'>Contrato</td>
            <td style='font-weight:bold; width:12%; font-size:11px;'>Factura</td>
            <td style='font-weight:bold; width:23%; font-size:11px;'>Ítem</td>
            <td style='font-weight:bold; width:7%; font-size:11px; text-align:center;'>U/M</td>
            <td style='font-weight:bold; width:8%; font-size:11px; text-align:center;'>Cant.</td>
            <td style='font-weight:bold; width:9%; font-size:11px; text-align:right;'>Precio</td>
            <td style='font-weight:bold; width:10%; font-size:11px; text-align:right;'>Subtotal</td>
            <td style='font-weight:bold; width:8%; font-size:11px;'>Destino</td>
        </tr>";

                $ordenadas = $g['lineas']->sortBy(fn($l) => $l->retiroContrato->fecha . '|' . str_pad($l->id_retiro_contrato, 10, '0', STR_PAD_LEFT));

                foreach ($ordenadas as $l) {
                    $r      = $l->retiroContrato;
                    $cd     = $l->contratoDetalle;
                    $precio = (float) ($cd->precio ?? 0);
                    $destino = $r->otra_unidad ? (optional($r->departamentoDestino)->nombre ?? '') : '-';

                    $tabla .= "
        <tr>
            <td style='font-size:11px;'>" . date('d-m-Y', strtotime($r->fecha)) . "</td>
            <td style='font-size:11px;'>" . e(optional($r->contrato)->codigo ?: optional($r->contrato)->nombre_proceso) . "</td>
            <td style='font-size:11px;'>" . e($r->no_factura ?? '') . "</td>
            <td style='font-size:11px;'>" . e($cd->nombre ?? '') . "</td>
            <td style='font-size:11px; text-align:center;'>" . e($cd->unidadMedida->nombre ?? '') . "</td>
            <td style='font-size:11px; text-align:center;'>" . number_format($l->cantidad, 0) . "</td>
            <td style='font-size:11px; text-align:right;'>$" . number_format($precio, 2) . "</td>
            <td style='font-size:11px; text-align:right;'>$" . number_format($precio * $l->cantidad, 2) . "</td>
            <td style='font-size:11px;'>" . e($destino) . "</td>
        </tr>";
                }
            } else {
                $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:16%; font-size:11px;'>Contrato</td>
            <td style='font-weight:bold; width:32%; font-size:11px;'>Ítem</td>
            <td style='font-weight:bold; width:8%; font-size:11px; text-align:center;'>U/M</td>
            <td style='font-weight:bold; width:11%; font-size:11px; text-align:center;'>Cantidad</td>
            <td style='font-weight:bold; width:8%; font-size:11px; text-align:center;'>Retiros</td>
            <td style='font-weight:bold; width:12%; font-size:11px; text-align:right;'>Precio</td>
            <td style='font-weight:bold; width:13%; font-size:11px; text-align:right;'>Monto</td>
        </tr>";

                $porItem = $g['lineas']
                    ->groupBy('id_contrato_detalle')
                    ->sortBy(fn($c) => optional($c->first()->contratoDetalle)->nombre);

                foreach ($porItem as $coleccion) {
                    $cd       = $coleccion->first()->contratoDetalle;
                    $precio   = (float) ($cd->precio ?? 0);
                    $cantidad = (int) $coleccion->sum('cantidad');
                    $contr    = optional($coleccion->first()->retiroContrato->contrato);

                    $tabla .= "
        <tr>
            <td style='font-size:11px;'>" . e($contr->codigo ?: $contr->nombre_proceso) . "</td>
            <td style='font-size:11px;'>" . e($cd->nombre ?? '') . "</td>
            <td style='font-size:11px; text-align:center;'>" . e($cd->unidadMedida->nombre ?? '') . "</td>
            <td style='font-size:11px; text-align:center;'>" . number_format($cantidad, 0) . "</td>
            <td style='font-size:11px; text-align:center;'>" . $coleccion->pluck('id_retiro_contrato')->unique()->count() . "</td>
            <td style='font-size:11px; text-align:right;'>$" . number_format($precio, 2) . "</td>
            <td style='font-size:11px; text-align:right;'>$" . number_format($precio * $cantidad, 2) . "</td>
        </tr>";
                }
            }

            $tabla .= "
    </tbody>
</table><br>";
        }

        $tabla .= $this->bloqueTotales([
            'Unidades'      => null,
            'Total Período' => $totalMonto,
        ], count($grupos));

        return $this->salidaPdf($tabla, 'reporte_solicitudes_unidades.pdf');
    }

    // ────────────────────────────────────────────────────────────
    // Helpers compartidos
    // ────────────────────────────────────────────────────────────

    // null / '' / 'todos' = todas las unidades; número = una unidad
    private function idUnidad(Request $request): ?int
    {
        return is_numeric($request->id_departamento) ? (int) $request->id_departamento : null;
    }

    // Encabezado institucional (logo, título y cuadro de código/versión)
    private function encabezado(string $titulo, string $ultimaEtiqueta = 'Fecha'): string
    {
        $logoalcaldia = 'images/logo.png';

        return "
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
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000; padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            {$titulo}
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
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>{$ultimaEtiqueta}:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>";
    }

    // Datos del contrato (+ filas extra: etiqueta => valor, a todo el ancho)
    private function datosContrato(Contrato $contrato, array $extras = []): string
    {
        $codigo      = $contrato->codigo ?? '—';
        $fechaInicio = date('d-m-Y', strtotime($contrato->fecha_inicio));
        $fechaFin    = date('d-m-Y', strtotime($contrato->fecha_fin));
        $estado      = ucfirst($contrato->estado);
        $proveedor   = $contrato->proveedor->nombre ?? '';

        $html = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; font-size:12px; margin-bottom:10px;'>
    <tr>
        <td style='width:16%; border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>CÓDIGO</td>
        <td style='width:34%; border:0.8px solid #ccc; padding:6px 8px;'>" . e($codigo) . "</td>
        <td style='width:15%; border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>FECHA INICIO</td>
        <td style='width:35%; border:0.8px solid #ccc; padding:6px 8px;'>$fechaInicio</td>
    </tr>
    <tr>
        <td style='border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>CONTRATO</td>
        <td style='border:0.8px solid #ccc; padding:6px 8px;'>" . e($contrato->nombre_proceso) . "</td>
        <td style='border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>FECHA FIN</td>
        <td style='border:0.8px solid #ccc; padding:6px 8px;'>$fechaFin</td>
    </tr>
    <tr>
        <td style='border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>ESTADO</td>
        <td style='border:0.8px solid #ccc; padding:6px 8px;'>" . e($estado) . "</td>
        <td style='border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>PROVEEDOR</td>
        <td style='border:0.8px solid #ccc; padding:6px 8px;'>" . e($proveedor) . "</td>
    </tr>";

        foreach ($extras as $etiqueta => $valor) {
            $html .= "
    <tr>
        <td style='border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>" . e($etiqueta) . "</td>
        <td colspan='3' style='border:0.8px solid #ccc; padding:6px 8px;'>" . e($valor) . "</td>
    </tr>";
        }

        return $html . "
</table>";
    }

    // Bloque de totales fuera de la tabla principal.
    // Un monto null se muestra como número entero ($enteroSi) — p. ej. cantidad de unidades.
    private function bloqueTotales(array $totales, ?int $enteroSi = null): string
    {
        $celdas = '';
        foreach ($totales as $etiqueta => $monto) {
            $valor = $monto === null
                ? (string) $enteroSi
                : '$' . number_format($monto, 2);

            $celdas .= "
        <td style='text-align:center; padding:8px 14px; border:0.8px solid #ccc; background:#f5f5f5;'>
            <div style='font-size:10px; font-weight:bold; color:#555; text-transform:uppercase;'>{$etiqueta}</div>
            <div style='font-size:15px; font-weight:bold; color:#104e8c;'>{$valor}</div>
        </td>";
        }

        return "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-top:14px;'>
    <tr>
        $celdas
    </tr>
</table>";
    }

    private function salidaPdf(string $html, string $nombreArchivo, bool $horizontal = false)
    {
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'       => sys_get_temp_dir(),
            'format'        => $horizontal ? 'LETTER-L' : 'LETTER',   // LETTER-L = horizontal
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
        ]);

        $mpdf->SetTitle($nombreArchivo);
        $mpdf->showImageErrors = false;
        $mpdf->setFooter('Página: ' . '{PAGENO}' . '/' . '{nb}');

        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML($html, 2);

        return $mpdf->Output($nombreArchivo, 'I');
    }
}
