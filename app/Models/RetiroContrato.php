<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetiroContrato extends Model
{
    protected $table = 'retiro_contrato';
    protected $fillable = ['id_contrato', 'fecha', 'no_factura', 'fecha_factura', 'descripcion'];
    public $timestamps = false;

    public function contrato() {
        return $this->belongsTo(Contrato::class, 'id_contrato');
    }

    public function detalle()  {
        return $this->hasMany(RetiroContratoDetalle::class, 'id_retiro_contrato');
    }

}
