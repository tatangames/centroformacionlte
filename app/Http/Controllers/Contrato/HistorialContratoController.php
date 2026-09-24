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

class HistorialContratoController extends Controller
{
    public function indexHistorialSalidas()
    {
        $arrayContratos = Contrato::orderBy('nombre_proceso')->get();

        return view('backend.admin.procesocontrato.historial.vistahistorialsalidas',
            compact('arrayContratos'));
    }

    public function tablaHistorialSalidas(Request $request)
    {
        $arrayRetiros = RetiroContrato::with('contrato.proveedor')
            ->when($request->contrato, fn($q) =>
            $q->where('id_contrato', $request->contrato)
            )
            ->when($request->fecha_desde, fn($q) =>
            $q->whereDate('fecha', '>=', $request->fecha_desde)
            )
            ->when($request->fecha_hasta, fn($q) =>
            $q->whereDate('fecha', '<=', $request->fecha_hasta)
            )
            ->when($request->material, function ($q) use ($request) {
                $busqueda = '%' . $request->material . '%';
                $q->whereHas('detalle.contratoDetalle', function ($q2) use ($busqueda) {
                    $q2->where('nombre', 'LIKE', $busqueda);
                });
            })
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($item) {
                $item->fecha_fmt = date('d/m/Y', strtotime($item->fecha));
                $item->fecha_factura_fmt = $item->fecha_factura
                    ? date('d/m/Y', strtotime($item->fecha_factura))
                    : '';
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

        return response()->json([
            'success' => 1,
            'retiro'  => [
                'id'            => $retiro->id,
                'fecha'         => $retiro->fecha,
                'no_factura'    => $retiro->no_factura,
                'fecha_factura' => $retiro->fecha_factura,
                'descripcion'   => $retiro->descripcion,
            ]
        ]);
    }

    public function editarSalida(Request $request)
    {
        $retiro = RetiroContrato::with('contrato')->find($request->id);

        if (!$retiro) {
            return response()->json(['success' => 0]);
        }

        // La fecha del retiro no puede ser anterior al inicio del contrato
        // ni posterior a su fin (ajusta/quita si no aplica a tu negocio).
        $contrato = $retiro->contrato;
        if ($contrato && $request->fecha < $contrato->fecha_inicio) {
            return response()->json([
                'success'       => 2,
                'fecha_salida'  => Carbon::parse($request->fecha)->format('d-m-Y'),
                'fecha_limite'  => Carbon::parse($contrato->fecha_inicio)->format('d-m-Y'),
                'motivo'        => 'anterior al inicio del contrato',
            ]);
        }

        $retiro->fecha         = $request->fecha;
        $retiro->no_factura    = $request->no_factura ?: null;
        $retiro->fecha_factura = $request->fecha_factura ?: null;
        $retiro->descripcion   = $request->descripcion ?: null;
        $retiro->save();

        return response()->json(['success' => 1]);
    }

    public function eliminarSalida(Request $request)
    {
        $retiro = RetiroContrato::find($request->id);

        if (!$retiro) {
            return response()->json(['success' => 0]);
        }

        $retiro->detalle()->delete();
        $retiro->delete();

        return response()->json(['success' => 1]);
    }

    public function detalleSalida(Request $request)
    {
        $retiro = RetiroContrato::find($request->id);

        if (!$retiro) {
            return response()->json(['success' => 0]);
        }

        $detalle = $retiro->detalle()
            ->with('contratoDetalle.unidadMedida')
            ->get()
            ->map(function ($item) {
                return [
                    'id'              => $item->id, // ← AGREGADO: id del retiro_contrato_detalle
                    'codigo'          => $item->id_contrato_detalle,
                    'material'        => $item->contratoDetalle->nombre ?? '',
                    'unidad'          => $item->contratoDetalle->unidadMedida->nombre ?? '',
                    'cantidad_salida' => $item->cantidad,
                    'precio'          => number_format($item->contratoDetalle->precio ?? 0, 4),
                ];
            });

        return response()->json([
            'success' => 1,
            'detalle' => $detalle,
        ]);
    }

    public function vistaExtrasSalida($id)
    {
        $retiro = RetiroContrato::with('contrato')->find($id);

        if (!$retiro || $retiro->contrato->estado == 'finalizado') {
            return redirect()->route('admin.historial.contratos.index')
                ->with('error', 'El contrato está finalizado, no se pueden agregar extras');
        }

        return view('backend.admin.procesocontrato.historial.vistaextrassalidas', compact('retiro'));
    }

    public function guardarExtrasSalida(Request $request)
    {
        $retiro = RetiroContrato::with('contrato')->find($request->id_retiro);

        if (!$retiro) {
            return response()->json(['success' => 0]);
        }

        if ($retiro->contrato->estado == 'finalizado') {
            return response()->json(['success' => 0, 'mensaje' => 'El contrato está finalizado']);
        }

        $contenedor = json_decode($request->contenedorArray, true);

        if (empty($contenedor)) {
            return response()->json(['success' => 0]);
        }

        foreach ($contenedor as $index => $item) {
            $contratoDetalle = ContratoDetalle::find($item['infoIdContratoDetalle']);

            if (!$contratoDetalle) {
                return response()->json(['success' => 2, 'fila' => $index + 1]);
            }

            $totalRetirado = RetiroContratoDetalle::where('id_contrato_detalle', $contratoDetalle->id)
                ->sum('cantidad');

            $disponible = $contratoDetalle->cantidad - $totalRetirado;

            if ($item['infoCantidad'] > $disponible) {
                return response()->json(['success' => 2, 'fila' => $index + 1]);
            }
        }

        foreach ($contenedor as $item) {
            RetiroContratoDetalle::create([
                'id_retiro_contrato'  => $retiro->id,
                'id_contrato_detalle' => $item['infoIdContratoDetalle'],
                'cantidad'            => $item['infoCantidad'],
            ]);
        }

        return response()->json(['success' => 10]);
    }

    // ── Autocompletar ítems del contrato con disponibilidad ──
    public function buscarMaterialDisponible(Request $request)
    {
        $texto      = $request->input('query', '');
        $idContrato = $request->input('id_contrato');

        $resultados = ContratoDetalle::with('unidadMedida')
            ->where('id_contrato', $idContrato)
            ->where('nombre', 'like', '%' . $texto . '%')
            ->withSum('retiroDetalles as retirado', 'cantidad')
            ->get()
            ->map(function ($item) {
                $retirado = $item->retirado ?? 0;
                return [
                    'id'         => $item->id,
                    'nombre'     => $item->nombre,
                    'unidad'     => $item->unidadMedida->nombre ?? '',
                    'disponible' => $item->cantidad - $retirado,
                    'precio'     => number_format($item->precio, 2),
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

        $totalRetirado = RetiroContratoDetalle::where('id_contrato_detalle', $item->id)->sum('cantidad');
        $disponible    = $item->cantidad - $totalRetirado;

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
        $detalle = RetiroContratoDetalle::with(['retiroContrato.contrato', 'contratoDetalle'])
            ->find($request->id_detalle);

        if (!$detalle) {
            return response()->json(['success' => 0]);
        }

        $contrato = $detalle->retiroContrato->contrato ?? null;

        // No permitir editar si el contrato ya está finalizado
        if ($contrato && $contrato->estado == 'finalizado') {
            return response()->json([
                'success'  => 3,
                'mensaje'  => 'El contrato está finalizado, no se puede modificar este ítem',
            ]);
        }

        $contratoDetalle = $detalle->contratoDetalle;
        if (!$contratoDetalle) {
            return response()->json(['success' => 0]);
        }

        $cantidad = (int) $request->cantidad;
        if ($cantidad <= 0) {
            return response()->json(['success' => 0]);
        }

        // Cuánto se ha retirado en OTROS retiros de este mismo ítem de contrato
        // (excluyendo el registro que estamos editando)
        $totalRetiradoOtros = RetiroContratoDetalle::where('id_contrato_detalle', $contratoDetalle->id)
            ->where('id', '!=', $detalle->id)
            ->sum('cantidad');

        $disponible = $contratoDetalle->cantidad - $totalRetiradoOtros;

        if ($cantidad > $disponible) {
            return response()->json([
                'success'    => 2,
                'disponible' => $disponible,
            ]);
        }

        $detalle->cantidad = $cantidad;
        $detalle->save();

        return response()->json(['success' => 1]);
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
    </tr>
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
            <th style='width:10%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>CANTIDAD</th>
            <th style='width:45%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>DESCRIPCION</th>
            <th style='width:15%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>UNIDAD</th>
            <th style='width:15%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>PRECIO UNIT.</th>
            <th style='width:15%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>";

        $totalGeneral = 0;

        foreach ($retiro->detalle as $fila) {
            $cantidad   = (float) $fila->cantidad;
            $nombreItem = '';
            $unidadMed  = '';
            $precio     = 0;

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
        <td style='border:0.8px solid #000; padding:5px 8px; text-align:right;'>$" . number_format($precio, 2) . "</td>
        <td style='border:0.8px solid #000; padding:5px 8px; text-align:right;'>$" . number_format($subtotal, 2) . "</td>
    </tr>";
        }

        $html .= "
    </tbody>
    <tfoot>
        <tr>
            <td colspan='4' style='border:0.8px solid #000; padding:6px 8px; text-align:right; font-weight:bold;'>TOTAL</td>
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
