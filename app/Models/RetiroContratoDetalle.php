<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetiroContratoDetalle extends Model
{
    protected $table = 'retiro_contrato_detalle';
    protected $fillable = ['id_retiro_contrato', 'id_contrato_detalle', 'cantidad'];
    public $timestamps = false;

    public function retiro()          {
        return $this->belongsTo(RetiroContrato::class, 'id_retiro_contrato');
    }
    public function contratoDetalle() {
        return $this->belongsTo(ContratoDetalle::class, 'id_contrato_detalle');
    }


    public function retiroContrato()
    {
        return $this->belongsTo(RetiroContrato::class, 'id_retiro_contrato');
    }



}
