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
use App\Models\RetiroContratoDetalle;
use App\Models\Rubro;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConfigContratoController extends Controller
{

    //********* CONTRATO  **************************************************************


    public function indexContrato(){

        $arrayProveedor = Proveedor::orderBy('nombre', 'ASC')->get();

        return view('backend.admin.procesocontrato.configuracion.contrato.vistacontrato', compact('arrayProveedor'));
    }

    public function tablaContrato(Request $request){

        $query = Contrato::with('proveedor');

        if ($request->filled('id_proveedor')) {
            $query->where('id_proveedor', $request->id_proveedor);
        }

        if ($request->filled('estado') && in_array($request->estado, ['vigente', 'finalizado'])) {
            $query->where('estado', $request->estado);
        }

        // El rango de fechas se aplica sobre la fecha de inicio del contrato
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_inicio', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_inicio', '<=', $request->fecha_hasta);
        }

        $lista = $query->orderBy('fecha_inicio', 'DESC')->get();

        foreach ($lista as $dato) {
            $inicio = Carbon::parse($dato->fecha_inicio);
            $fin    = Carbon::parse($dato->fecha_fin);

            $dato->codigo_texto       = $dato->codigo ?: '-';
            $dato->proveedor_nombre   = $dato->proveedor->nombre ?? 'N/A';

            $dato->fecha_inicio_texto = $inicio->format('d/m/Y');
            $dato->fecha_fin_texto    = $fin->format('d/m/Y');

            // para que DataTables ordene bien por fecha (d/m/Y ordena mal como texto)
            $dato->fecha_inicio_orden = $inicio->format('Y-m-d');
            $dato->fecha_fin_orden    = $fin->format('Y-m-d');

            if ($dato->estado === 'vigente') {
                $dato->estado_texto = 'Vigente';
                $dato->estado_clase = 'badge-success';
            } else {
                $dato->estado_texto = 'Finalizado';
                $dato->estado_clase = 'badge-secondary';
            }
        }

        return view('backend.admin.procesocontrato.configuracion.contrato.tablacontrato', compact('lista'));
    }

    public function nuevaContrato(Request $request){
        $regla = array(
            'id_proveedor'    => 'required',
            'codigo'          => 'nullable|max:300',
            'nombre_proceso'  => 'required|max:300',
            'fecha_inicio'    => 'required|date',
            'fecha_fin'       => 'required|date|after_or_equal:fecha_inicio',
            'descripcion'     => 'nullable',
            'estado'          => 'required|in:vigente,finalizado',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0, 'errores' => $validar->errors()]; }

        $dato = new Contrato();
        $dato->id_proveedor    = $request->id_proveedor;
        $dato->codigo          = $request->codigo;
        $dato->nombre_proceso  = $request->nombre_proceso;
        $dato->fecha_inicio    = $request->fecha_inicio;
        $dato->fecha_fin       = $request->fecha_fin;
        $dato->descripcion     = $request->descripcion;
        $dato->estado          = $request->estado;

        if($dato->save()){
            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }

    public function informacionContrato(Request $request){
        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0]; }

        if($lista = Contrato::with('proveedor')->where('id', $request->id)->first()){
            return ['success' => 1, 'info' => $lista];
        }else{
            return ['success' => 2];
        }
    }

    public function editarContrato(Request $request){

        $regla = array(
            'id'              => 'required',
            'id_proveedor'    => 'required',
            'codigo'          => 'nullable|max:300',
            'nombre_proceso'  => 'required|max:300',
            'fecha_inicio'    => 'required|date',
            'fecha_fin'       => 'required|date|after_or_equal:fecha_inicio',
            'descripcion'     => 'nullable',
            'estado'          => 'required|in:vigente,finalizado',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0, 'errores' => $validar->errors()]; }

        if(Contrato::where('id', $request->id)->first()){

            Contrato::where('id', $request->id)->update([
                'id_proveedor'    => $request->id_proveedor,
                'codigo'          => $request->codigo,
                'nombre_proceso'  => $request->nombre_proceso,
                'fecha_inicio'    => $request->fecha_inicio,
                'fecha_fin'       => $request->fecha_fin,
                'descripcion'     => $request->descripcion,
                'estado'          => $request->estado,
            ]);

            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }









    public function indexContratoDetalle($id)
    {
        $contrato = Contrato::findOrFail($id);
        $arrayUnidadMedida = UnidadMedida::orderBy('nombre', 'ASC')->get();
        $arrayDepartamentos = Departamentos::orderBy('nombre', 'ASC')->get();

        return view(
            'backend.admin.procesocontrato.configuracion.contrato.detalle.vistadetallecontrato',
            compact('contrato', 'arrayUnidadMedida', 'arrayDepartamentos')
        );
    }

    public function tablaContratoDetalle($id)
    {
        $lista = ContratoDetalle::with(['unidadMedida', 'departamentos'])
            ->withCount('retiros')
            ->withSum('retiros as retirado', 'cantidad')
            ->where('id_contrato', $id)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($lista as $dato) {
            // Se multiplica con el precio completo (4 decimales) y se redondea solo al final
            $subtotal = round($dato->cantidad * $dato->precio, 4);

            $dato->retirado = (int) ($dato->retirado ?? 0);
            $dato->stock    = (int) $dato->cantidad - $dato->retirado;

            $dato->precioFormat   = '$' . number_format($dato->precio, 4);
            $dato->subtotalFormat = '$' . number_format($subtotal, 2);
        }

        return view('backend.admin.procesocontrato.configuracion.contrato.detalle.tabladetallecontrato', compact('lista', 'id'));
    }


    public function nuevoContratoDetalle(Request $request)
    {
        $request->validate([
            'id_contrato'             => 'required|exists:contrato,id',
            'id_unidadmedida'         => 'required|exists:unidadmedida,id',
            'nombre'                  => 'required|max:300',
            'cantidad'                => 'required|integer|min:1|max:1000000',
            'precio'                  => 'required|numeric|min:0|max:1000000',
            'departamentos'           => 'required|array|min:1',
            'departamentos.*.id'      => 'required|distinct|exists:departamentos,id',
            'departamentos.*.cantidad' => 'required|integer|min:1',
        ]);

        // La suma de lo distribuido debe ser exactamente el total
        $suma = collect($request->departamentos)->sum('cantidad');

        if ($suma !== (int) $request->cantidad) {
            return response()->json([
                'success' => 3,
                'message' => "La suma distribuida ($suma) debe ser igual a la cantidad total ({$request->cantidad})",
            ]);
        }

        try {
            DB::beginTransaction();

            $detalle = new ContratoDetalle();
            $detalle->id_contrato     = $request->id_contrato;
            $detalle->id_unidadmedida = $request->id_unidadmedida;
            $detalle->nombre          = $request->nombre;
            $detalle->cantidad        = $request->cantidad;
            $detalle->precio          = $request->precio;
            $detalle->save();

            // [id_departamento => ['cantidad' => x]]
            $sync = [];
            foreach ($request->departamentos as $dep) {
                $sync[$dep['id']] = ['cantidad' => $dep['cantidad']];
            }

            $detalle->departamentos()->sync($sync);

            DB::commit();
            return response()->json(['success' => 1]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error nuevoContratoDetalle: ' . $e->getMessage());
            return response()->json(['success' => 0]);
        }
    }



    public function informacionContratoDetalle(Request $request)
    {
        $detalle = ContratoDetalle::with('departamentos')->find($request->id);

        if (!$detalle) {
            return response()->json(['success' => 0]);
        }

        // [id_departamento => cantidad]
        $distribucion = $detalle->departamentos->pluck('pivot.cantidad', 'id');

        return response()->json([
            'success'      => 1,
            'info'         => $detalle->only(['id', 'nombre', 'id_unidadmedida', 'cantidad', 'precio']),
            'distribucion' => (object) $distribucion->all(),
        ]);
    }

    public function editarContratoDetalle(Request $request)
    {
        $request->validate([
            'id'                       => 'required|exists:contrato_detalle,id',
            'id_unidadmedida'          => 'required|exists:unidadmedida,id',
            'nombre'                   => 'required|max:300',
            'cantidad'                 => 'required|integer|min:1|max:1000000',
            'precio'                   => 'required|numeric|min:0|max:1000000',
            'departamentos'            => 'required|array|min:1',
            'departamentos.*.id'       => 'required|distinct|exists:departamentos,id',
            'departamentos.*.cantidad' => 'required|integer|min:1',
        ]);

        // La suma de lo distribuido debe ser exactamente el total
        $suma = (int) collect($request->departamentos)->sum('cantidad');

        if ($suma !== (int) $request->cantidad) {
            return response()->json([
                'success' => 3,
                'message' => "La suma distribuida ($suma) debe ser igual a la cantidad total ({$request->cantidad})",
            ]);
        }

        try {
            DB::beginTransaction();

            // lockForUpdate evita que se registre un retiro justo entre la verificación y el guardado
            $detalle = ContratoDetalle::lockForUpdate()->find($request->id);

            if (!$detalle) {
                DB::rollBack();
                return response()->json(['success' => 0, 'message' => 'Registro no encontrado']);
            }

            // Bloquear si ya tiene salidas (retiros) registradas
            if ($detalle->retiros()->exists()) {
                DB::rollBack();
                return response()->json([
                    'success' => 2,
                    'message' => 'No se puede editar este item porque ya tiene salidas registradas',
                ]);
            }

            $detalle->nombre          = $request->nombre;
            $detalle->id_unidadmedida = $request->id_unidadmedida;
            $detalle->cantidad        = $request->cantidad;
            $detalle->precio          = $request->precio;
            $detalle->save();

            // [id_departamento => ['cantidad' => x]]
            $sync = [];
            foreach ($request->departamentos as $dep) {
                $sync[$dep['id']] = ['cantidad' => $dep['cantidad']];
            }

            $detalle->departamentos()->sync($sync);

            DB::commit();
            return response()->json(['success' => 1]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error editarContratoDetalle: ' . $e->getMessage());
            return response()->json(['success' => 0]);
        }
    }

    public function eliminarContratoDetalle(Request $request)
    {
        $detalle = ContratoDetalle::find($request->id);

        if (!$detalle) {
            return response()->json(['success' => 0, 'message' => 'Registro no encontrado']);
        }

        // Bloquear si ya tiene retiros registrados
        if ($detalle->retiros()->exists()) {
            return response()->json([
                'success' => 2,
                'message' => 'No se puede eliminar este item porque ya tiene retiros registrados'
            ]);
        }

        // Las filas de la tabla pivote se borran solas (cascadeOnDelete)
        $detalle->delete();

        return response()->json(['success' => 1]);
    }






    //********************************************************************************************

    public function detalleCompletoContratoDetalle($id)
    {
        $detalle = ContratoDetalle::with(['unidadMedida', 'departamentos'])->findOrFail($id);

        $retirado   = (int) DB::table('retiro_contrato_detalle')
            ->where('id_contrato_detalle', $id)
            ->sum('cantidad');

        $disponible = $detalle->cantidad - $retirado;
        $subtotal   = round($detalle->cantidad * $detalle->precio, 4);

        $precioFormat   = '$' . number_format($detalle->precio, 4);
        $subtotalFormat = '$' . number_format($subtotal, 2);

        return view(
            'backend.admin.procesocontrato.configuracion.contrato.minitablas.detallecompletocontrato',
            compact('detalle', 'retirado', 'disponible', 'precioFormat', 'subtotalFormat')
        );
    }


    public function salidasContratoDetalle($id)
    {
        $detalle = ContratoDetalle::findOrFail($id);

        $salidas = DB::table('retiro_contrato_detalle as rd')
            ->join('retiro_contrato as r', 'r.id', '=', 'rd.id_retiro_contrato')
            ->leftJoin('departamentos as d', 'd.id', '=', 'r.id_departamento_destino')
            ->where('rd.id_contrato_detalle', $id)
            ->select(
                'r.id',
                'r.fecha',
                'r.otra_unidad',
                'r.no_factura',
                'r.fecha_factura',
                'r.descripcion',
                'rd.cantidad',
                'd.nombre as destino'
            )
            ->orderBy('r.fecha', 'desc')
            ->orderBy('r.id', 'desc')
            ->get();

        $totalRetirado = (int) $salidas->sum('cantidad');

        return view(
            'backend.admin.procesocontrato.configuracion.contrato.minitablas.tablasalidadetalle',
            compact('detalle', 'salidas', 'totalRetirado')
        );
    }










}
