<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContratoDetalle extends Model
{
    use HasFactory;
    protected $table = 'contrato_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_contrato',
        'id_unidadmedida',
        'nombre',
        'cantidad',
        'precio',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'id_contrato');
    }

    // ContratoDetalle
    public function retiros() {
        return $this->hasMany(RetiroContratoDetalle::class, 'id_contrato_detalle');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidadmedida');
    }

    public function retiroDetalles()
    {
        return $this->hasMany(RetiroContratoDetalle::class, 'id_contrato_detalle');
    }


}
