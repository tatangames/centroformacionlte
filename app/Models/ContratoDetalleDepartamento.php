<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContratoDetalleDepartamento extends Model
{
    protected $table = 'retiro_contrato_detalle';
    protected $fillable = ['id_retiro_contrato', 'id_contrato_detalle', 'id_departamento', 'cantidad'];
    public $timestamps = false;

    public function retiro() {
        return $this->belongsTo(RetiroContrato::class, 'id_retiro_contrato');
    }

    public function retiroContrato() {
        return $this->belongsTo(RetiroContrato::class, 'id_retiro_contrato');
    }

    public function contratoDetalle() {
        return $this->belongsTo(ContratoDetalle::class, 'id_contrato_detalle');
    }

    // Unidad de la que sale el material
    public function departamentoOrigen()
    {
        return $this->belongsTo(Departamentos::class, 'id_departamento');
    }

}
