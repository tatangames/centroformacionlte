<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetiroContrato extends Model
{
    protected $table = 'retiro_contrato';
    protected $fillable = [
        'id_contrato', 'otra_unidad', 'id_departamento_destino',
        'fecha', 'no_factura', 'fecha_factura', 'descripcion',
    ];
    public $timestamps = false;

    public function contrato() {
        return $this->belongsTo(Contrato::class, 'id_contrato');
    }

    // Unidad a la que fue el material (solo si otra_unidad = true)
    public function departamentoDestino()
    {
        return $this->belongsTo(Departamentos::class, 'id_departamento_destino');
    }

    public function detalle() {
        return $this->hasMany(RetiroContratoDetalle::class, 'id_retiro_contrato');
    }


}
