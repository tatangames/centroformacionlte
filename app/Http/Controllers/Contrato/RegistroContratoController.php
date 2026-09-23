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

        return view('backend.admin.procesocontrato.registro.salidas.vistasalidacontrato', compact('arrayContratos'));
    }

    public function infoContrato(Request $request)
    {
        $contrato = Contrato::with('proveedor')->find($request->id_contrato);
        if (!$contrato) return response()->json(['success' => 0]);

        $detalle = ContratoDetalle::with('unidadMedida')
            ->where('id_contrato', $contrato->id)
            ->withSum('retiros as retirado', 'cantidad')
            ->get()
            ->map(function ($item) {
                $retirado = $item->retirado ?? 0;
                return [
                    'nombre'        => $item->nombre,
                    'unidad_medida' => optional($item->unidadMedida)->nombre,
                    'cantidad'      => $item->cantidad,
                    'retirado'      => $retirado,
                    'disponible'    => $item->cantidad - $retirado,
                    'precio'        => number_format($item->precio, 2),
                ];
            });

        return response()->json([
            'success'        => 1,
            'codigo'         => $contrato->codigo,
            'nombre_proceso' => $contrato->nombre_proceso,
            'proveedor'      => optional($contrato->proveedor)->nombre,
            'fecha_inicio'   => $contrato->fecha_inicio
                ? Carbon::parse($contrato->fecha_inicio)->format('d-m-Y')
                : null,
            'fecha_fin'      => $contrato->fecha_fin
                ? Carbon::parse($contrato->fecha_fin)->format('d-m-Y')
                : null,
            'descripcion'    => $contrato->descripcion,
            'detalle'        => $detalle,
        ]);
    }

    public function buscarMaterialContrato(Request $request)
    {
        $texto = $request->input('query');

        $resultados = ContratoDetalle::where('id_contrato', $request->id_contrato)
            ->where('nombre', 'like', '%' . $texto . '%')
            ->withSum('retiros as retirado', 'cantidad')
            ->get()
            ->map(function ($item) {
                $retirado = $item->retirado ?? 0;
                return [
                    'id'         => $item->id,
                    'nombre'     => $item->nombre,
                    'disponible' => $item->cantidad - $retirado,
                    'precio'     => number_format($item->precio, 2),
                ];
            })
            ->values();

        return response()->json($resultados);
    }

    public function guardarRetiro(Request $request)
    {
        $idContrato = $request->id_contrato;
        $items = json_decode($request->contenedorArray, true);

        if (empty($items)) return response()->json(['success' => 1]);

        $contrato = Contrato::find($idContrato);
        if (!$contrato) return response()->json(['success' => 5]);
        if ($contrato->estado !== 'vigente') return response()->json(['success' => 3]);

        try {
            DB::beginTransaction();

            $retiro = RetiroContrato::create([
                'id_contrato'   => $idContrato,
                'fecha'         => $request->fecha,
                'no_factura'    => $request->no_factura !== '' ? $request->no_factura : null,
                'fecha_factura' => $request->fecha_factura !== '' ? $request->fecha_factura : null,
                'descripcion'   => $request->descripcion,
            ]);

            foreach ($items as $item) {
                $detalle = ContratoDetalle::where('id', $item['id_contrato_detalle'])
                    ->lockForUpdate()
                    ->first();

                if (!$detalle || $detalle->id_contrato != $idContrato) {
                    DB::rollBack();
                    return response()->json(['success' => 6]);
                }

                $cantidad   = (int) $item['cantidad'];
                $retirado   = RetiroContratoDetalle::where('id_contrato_detalle', $detalle->id)->sum('cantidad');
                $disponible = $detalle->cantidad - $retirado;

                if ($cantidad <= 0 || $cantidad > $disponible) {
                    DB::rollBack();
                    return response()->json([
                        'success'         => 2,
                        'nombre_material' => $detalle->nombre,
                        'cantidad_pedida' => $cantidad,
                        'disponible'      => $disponible,
                    ]);
                }

                RetiroContratoDetalle::create([
                    'id_retiro_contrato'  => $retiro->id,
                    'id_contrato_detalle' => $detalle->id,
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
            return response()->json(['success' => 0, 'error' => $e->getMessage()]); // quita 'error' en producción
        }
    }

}
