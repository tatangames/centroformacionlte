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
use App\Models\Rubro;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        return view('backend.admin.procesocontrato.configuracion.contrato.detalle.vistadetallecontrato', compact('contrato', 'arrayUnidadMedida'));
    }

    public function tablaContratoDetalle($id)
    {
        $lista = ContratoDetalle::with('unidadMedida')
            ->where('id_contrato', $id)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($lista as $dato) {
            // Se multiplica con el precio completo (4 decimales) y se redondea solo al final
            $subtotal = round($dato->cantidad * $dato->precio, 4);

            $dato->precioFormat   = '$' . number_format($dato->precio, 4);
            $dato->subtotalFormat = '$' . number_format($subtotal, 2);
        }

        return view('backend.admin.procesocontrato.configuracion.contrato.detalle.tabladetallecontrato', compact('lista', 'id'));
    }

    public function nuevoContratoDetalle(Request $request)
    {
        $detalle = new ContratoDetalle();
        $detalle->id_contrato = $request->id_contrato;
        $detalle->id_unidadmedida = $request->id_unidadmedida;
        $detalle->nombre      = $request->nombre;
        $detalle->cantidad    = $request->cantidad;
        $detalle->precio      = $request->precio;
        $detalle->save();

        return response()->json(['success' => 1]);
    }

    public function informacionContratoDetalle(Request $request)
    {
        $detalle = ContratoDetalle::find($request->id);

        if ($detalle) {
            return response()->json(['success' => 1, 'info' => $detalle]);
        }

        return response()->json(['success' => 0]);
    }

    public function editarContratoDetalle(Request $request)
    {
        $detalle = ContratoDetalle::find($request->id);

        if ($detalle) {
            $detalle->nombre   = $request->nombre;
            $detalle->id_unidadmedida = $request->id_unidadmedida;
            $detalle->cantidad = $request->cantidad;
            $detalle->precio   = $request->precio;
            $detalle->save();

            return response()->json(['success' => 1]);
        }

        return response()->json(['success' => 0]);
    }

    public function eliminarContratoDetalle(Request $request)
    {
        $detalle = ContratoDetalle::find($request->id);

        if ($detalle) {
            $detalle->delete();

            return response()->json(['success' => 1]);
        }

        return response()->json(['success' => 0]);
    }










}
