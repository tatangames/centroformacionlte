<?php

namespace App\Http\Controllers\Contrato;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\ContratoDetalle;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\InformacionGeneral;
use App\Models\Materiales;
use App\Models\Reserva;
use App\Models\RetiroContrato;
use App\Models\RetiroContratoDetalle;
use App\Models\Salidas;
use App\Models\SalidasDetalle;
use App\Models\TipoProyecto;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportesContratoController extends Controller
{
    public function index()
    {
        $contratos = Contrato::orderBy('nombre_proceso')->get();
        return view('backend.admin.procesocontrato.reportes.vistareportescontrato', compact('contratos'));
    }

    // ────────────────────────────────────────────────────────────
    // 1) SALDOS Y DISPONIBILIDAD DE UN CONTRATO
    // ────────────────────────────────────────────────────────────
    public function pdfSaldosContrato(Request $request)
    {
        $request->validate([
            'idcontrato' => 'required|integer|exists:contrato,id',
        ]);

        $contrato = Contrato::with('proveedor')->findOrFail($request->idcontrato);

        $detalle = ContratoDetalle::with(['unidadMedida', 'retiroDetalles'])
            ->where('id_contrato', $contrato->id)
            ->get()
            ->map(function ($item) {
                $retirado = $item->retiroDetalles->sum('cantidad');
                $disponible = $item->cantidad - $retirado;

                return (object)[
                    'nombre' => $item->nombre,
                    'unidad' => $item->unidadMedida->nombre ?? '',
                    'cantidad_contrato' => $item->cantidad,
                    'retirado' => $retirado,
                    'disponible' => $disponible,
                    'precio' => $item->precio,
                    'monto_contratado' => $item->cantidad * $item->precio,
                    'monto_ejecutado' => $retirado * $item->precio,
                    'monto_disponible' => $disponible * $item->precio,
                ];
            });

        $totalContratado = $detalle->sum('monto_contratado');
        $totalEjecutado  = $detalle->sum('monto_ejecutado');
        $totalDisponible = $detalle->sum('monto_disponible');

        $filasHtml = '';
        foreach ($detalle as $fila) {
            $filasHtml .= "
<tr>
    <td style='font-size:12px;'>{$fila->nombre}</td>
    <td style='font-size:12px; text-align:center;'>{$fila->unidad}</td>
    <td style='font-size:12px; text-align:center;'>" . number_format($fila->cantidad_contrato, 0) . "</td>
    <td style='font-size:12px; text-align:center;'>" . number_format($fila->retirado, 0) . "</td>
    <td style='font-size:12px; text-align:center; font-weight:bold;'>" . number_format($fila->disponible, 0) . "</td>
    <td style='font-size:12px; text-align:right;'>$" . number_format($fila->precio, 2) . "</td>
    <td style='font-size:12px; text-align:right;'>$" . number_format($fila->monto_disponible, 2) . "</td>
</tr>";
        }

        if ($detalle->isEmpty()) {
            $filasHtml = "
<tr>
    <td colspan='7' style='text-align:center; padding:14px; color:#777;'>
        No hay ítems que coincidan con los criterios seleccionados.
    </td>
</tr>";
        }

        $titulo = 'REPORTE DE SALDOS Y DISPONIBILIDAD DE CONTRATO';
        $logoalcaldia = 'images/logo.png';

        $codigo      = $contrato->codigo ?? '—';
        $fechaInicio = date('d-m-Y', strtotime($contrato->fecha_inicio));
        $fechaFin    = date('d-m-Y', strtotime($contrato->fecha_fin));
        $estado      = ucfirst($contrato->estado);
        $proveedor   = $contrato->proveedor->nombre ?? '';

        // ── ENCABEZADO + Datos del contrato, todo construido aquí mismo ──
        $tabla = "
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
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha de vigencia:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan='3' style='padding:0;'>
            <table width='100%' style='border-collapse:collapse; font-size:12px;'>
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
                </tr>
            </table>
        </td>
    </tr>
</table><br>";

        $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:28%; font-size:13px;'>Ítem</td>
            <td style='font-weight:bold; width:10%; font-size:13px; text-align:center;'>Unidad</td>
            <td style='font-weight:bold; width:12%; font-size:13px; text-align:center;'>Contratado</td>
            <td style='font-weight:bold; width:12%; font-size:13px; text-align:center;'>Retirado</td>
            <td style='font-weight:bold; width:12%; font-size:13px; text-align:center;'>Disponible</td>
            <td style='font-weight:bold; width:12%; font-size:13px; text-align:right;'>Unit.</td>
            <td style='font-weight:bold; width:14%; font-size:13px; text-align:right;'>Total Disponible</td>
        </tr>
        $filasHtml
    </tbody>
</table>";

        // ── Totales FUERA de la tabla ──
        $tabla .= $this->bloqueTotales([
            'Contratado' => $totalContratado,
            'Ejecutado'  => $totalEjecutado,
            'Disponible' => $totalDisponible,
        ]);

        return $this->salidaPdf($tabla, 'reporte_saldos_contrato_' . $contrato->id . '.pdf');
    }


    // Encabezado propio del reporte de Saldos
    private function encabezadoSaldos(string $titulo): string
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
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha de vigencia:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>";
    }

    // ────────────────────────────────────────────────────────────
    // 2) MOVIMIENTOS (RETIROS) DE UN CONTRATO POR PERÍODO
    // ────────────────────────────────────────────────────────────
    // Encabezado + Datos del contrato + Período, todo en UNA sola tabla
    public function pdfMovimientosContrato(Request $request)
    {
        $request->validate([
            'idcontrato' => 'required|integer|exists:contrato,id',
            'desde' => 'required|date',
            'hasta' => 'required|date',
        ]);

        $contrato = Contrato::with('proveedor')->findOrFail($request->idcontrato);

        $start = date('Y-m-d 00:00:00', strtotime($request->desde));
        $end = date('Y-m-d 23:59:59', strtotime($request->hasta));
        $fechaLabel = date('d-m-Y', strtotime($request->desde)) . '  -  ' . date('d-m-Y', strtotime($request->hasta));

        $retiros = RetiroContrato::with('detalle.contratoDetalle.unidadMedida')
            ->where('id_contrato', $contrato->id)
            ->whereBetween('fecha', [$start, $end])
            ->orderBy('fecha', 'ASC')
            ->get();

        $titulo = 'REPORTE DE MOVIMIENTOS DE CONTRATO POR PERÍODO';
        $logoalcaldia = 'images/logo.png';

        $codigo      = $contrato->codigo ?? '—';
        $fechaInicio = date('d-m-Y', strtotime($contrato->fecha_inicio));
        $fechaFin    = date('d-m-Y', strtotime($contrato->fecha_fin));
        $estado      = ucfirst($contrato->estado);
        $proveedor   = $contrato->proveedor->nombre ?? '';

        // ── ENCABEZADO + Datos del contrato + Período, todo construido aquí mismo ──
        $tabla = "
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
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan='3' style='padding:0;'>
            <table width='100%' style='border-collapse:collapse; font-size:12px;'>
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
                </tr>
                <tr>
                    <td style='border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>PERIODO</td>
                    <td colspan='3' style='border:0.8px solid #ccc; padding:6px 8px;'>" . e($fechaLabel) . "</td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>";

        $granTotal = 0;

        if ($retiros->isEmpty()) {
            $tabla .= "<p style='text-align:center; color:#777; padding:14px;'>No hay retiros registrados en el período seleccionado.</p>";
        }

        foreach ($retiros as $retiro) {
            $fechaFmt = date('d-m-Y', strtotime($retiro->fecha));
            $fechaFacturaFmt = $retiro->fecha_factura ? date('d-m-Y', strtotime($retiro->fecha_factura)) : '';
            $factura = $retiro->no_factura ?? '';
            $descrip = $retiro->descripcion ?? '';

            $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse; margin-top:6px;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:15%; font-size:12px;'>Fecha Registrado</td>
            <td style='font-weight:bold; width:15%; font-size:12px;'>Fecha Factura</td>
            <td style='font-weight:bold; width:20%; font-size:12px;'>No. Factura</td>
            <td style='font-weight:bold; width:50%; font-size:12px;'>Descripción</td>
        </tr>
        <tr>
            <td style='font-size:12px;'>$fechaFmt</td>
            <td style='font-size:12px;'>$fechaFacturaFmt</td>
            <td style='font-size:12px;'>" . e($factura) . "</td>
            <td style='font-size:12px;'>" . e($descrip) . "</td>
        </tr>
    </tbody>
</table>";

            $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:35%; font-size:12px;'>Ítem</td>
            <td style='font-weight:bold; width:15%; font-size:12px; text-align:center;'>Unidad</td>
            <td style='font-weight:bold; width:15%; font-size:12px; text-align:center;'>Cantidad</td>
            <td style='font-weight:bold; width:17%; font-size:12px; text-align:right;'>Precio Unit.</td>
            <td style='font-weight:bold; width:18%; font-size:12px; text-align:right;'>Subtotal</td>
        </tr>";

            $subtotal = 0;
            foreach ($retiro->detalle as $item) {
                $cd = $item->contratoDetalle;
                $nombre = $cd->nombre ?? '';
                $unidad = $cd->unidadMedida->nombre ?? '';
                $precio = (float)($cd->precio ?? 0);
                $sub = $precio * $item->cantidad;
                $subtotal += $sub;
                $granTotal += $sub;

                $tabla .= "
        <tr>
            <td style='font-size:12px;'>" . e($nombre) . "</td>
            <td style='font-size:12px; text-align:center;'>" . e($unidad) . "</td>
            <td style='font-size:12px; text-align:center;'>" . number_format($item->cantidad, 0) . "</td>
            <td style='font-size:12px; text-align:right;'>$" . number_format($precio, 2) . "</td>
            <td style='font-size:12px; text-align:right;'>$" . number_format($sub, 2) . "</td>
        </tr>";
            }

            $tabla .= "
        <tr>
            <td colspan='4' style='font-weight:bold; font-size:12px; text-align:right; border-top:1px solid #000; padding-top:3px;'>
                Subtotal:
            </td>
            <td style='font-weight:bold; font-size:12px; text-align:right; border-top:1px solid #000; padding-top:3px;'>
                $" . number_format($subtotal, 2) . "
            </td>
        </tr>
    </tbody>
</table><br>";
        }

        // ── Totales del contrato completo (Contratado / Ejecutado), FUERA de la tabla ──
        $totalContratado = ContratoDetalle::where('id_contrato', $contrato->id)
            ->get()
            ->sum(fn($d) => $d->cantidad * $d->precio);

        $totalEjecutado = ContratoDetalle::with('retiroDetalles')
            ->where('id_contrato', $contrato->id)
            ->get()
            ->sum(fn($d) => $d->retiroDetalles->sum('cantidad') * $d->precio);

        $bloques = [
            'Contratado Total' => $totalContratado,
            'Ejecutado Total'  => $totalEjecutado,
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

        $contratos = Contrato::with(['proveedor', 'detalle.retiroDetalles'])
            ->when($estado !== 'todos', fn($q) => $q->where('estado', $estado))
            ->orderBy('nombre_proceso')
            ->get()
            ->map(function ($c) {
                $totalContratado = 0;
                $totalEjecutado = 0;

                foreach ($c->detalle as $d) {
                    $retirado = $d->retiroDetalles->sum('cantidad');
                    $totalContratado += $d->cantidad * $d->precio;
                    $totalEjecutado += $retirado * $d->precio;
                }

                $c->monto_contratado = $totalContratado;
                $c->monto_ejecutado = $totalEjecutado;
                $c->porcentaje = $totalContratado > 0
                    ? round(($totalEjecutado / $totalContratado) * 100, 1)
                    : 0;

                return $c;
            });

        // ── ENCABEZADO propio de este reporte ──
        $tabla = $this->encabezadoGeneral('REPORTE GENERAL DE ESTADO DE CONTRATOS');

        $filtroLabel = match ($estado) {
            'vigente' => 'Solo Vigentes',
            'finalizado' => 'Solo Finalizados',
            default => 'Todos los Contratos',
        };

        $tabla .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:10px; font-size:12px;'>
    <tr>
        <td style='width:20%; border:0.8px solid #ccc; padding:6px 8px; font-weight:bold; background:#f5f5f5;'>FILTRO</td>
        <td style='border:0.8px solid #ccc; padding:6px 8px;'>$filtroLabel</td>
    </tr>
</table>";

        $tabla .= "
<table width='100%' id='tablaFor' style='border-collapse:collapse;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:12%; font-size:12px;'>Código</td>
            <td style='font-weight:bold; width:26%; font-size:12px;'>Contrato</td>
            <td style='font-weight:bold; width:18%; font-size:12px;'>Proveedor</td>
            <td style='font-weight:bold; width:10%; font-size:12px; text-align:center;'>Estado</td>
            <td style='font-weight:bold; width:12%; font-size:12px; text-align:right;'>Monto Total</td>
            <td style='font-weight:bold; width:12%; font-size:12px; text-align:right;'>Ejecutado</td>
            <td style='font-weight:bold; width:10%; font-size:12px; text-align:center;'>% Avance</td>
        </tr>";

        if ($contratos->isEmpty()) {
            $tabla .= "<tr><td colspan='7' style='text-align:center; padding:14px; color:#777;'>No hay contratos que coincidan con el filtro.</td></tr>";
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
            <td style='font-size:12px; text-align:center;'>{$c->porcentaje}%</td>
        </tr>";
        }

        $tabla .= "
    </tbody>
</table>";

        return $this->salidaPdf($tabla, 'reporte_general_contratos.pdf');
    }

    // Encabezado propio del reporte General
    private function encabezadoGeneral(string $titulo): string
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
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>";
    }

    // ────────────────────────────────────────────────────────────
    // Helpers compartidos (no son "encabezado", son bloques de datos y utilidades)
    // ────────────────────────────────────────────────────────────

    // Bloque con Código, Fechas de contrato, Estado y Proveedor.
    // Se usa DEBAJO del encabezado, no dentro de él.


    // Bloque de totales fuera de la tabla principal
    private function bloqueTotales(array $totales): string
    {
        $celdas = '';
        foreach ($totales as $etiqueta => $monto) {
            $celdas .= "
        <td style='text-align:center; padding:8px 14px; border:0.8px solid #ccc; background:#f5f5f5;'>
            <div style='font-size:10px; font-weight:bold; color:#555; text-transform:uppercase;'>{$etiqueta}</div>
            <div style='font-size:15px; font-weight:bold; color:#104e8c;'>$" . number_format($monto, 2) . "</div>
        </td>";
        }

        return "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-top:14px;'>
    <tr>
        $celdas
    </tr>
</table>";
    }

    private function salidaPdf(string $html, string $nombreArchivo)
    {
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => sys_get_temp_dir(),
            'format' => 'LETTER',
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_left' => 15,
            'margin_right' => 15,
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
