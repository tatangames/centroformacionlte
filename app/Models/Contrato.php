<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    use HasFactory;
    protected $table = 'contrato';
    public $timestamps = false;

    protected $fillable = [
        'id_proveedor',
        'codigo',
        'nombre_proceso',
        'fecha_inicio',
        'fecha_fin',
        'descripcion',
        'estado',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    // Contrato
    public function detalle() {

        return $this->hasMany(ContratoDetalle::class, 'id_contrato');
    }

    public function retiros() {

        return $this->hasMany(RetiroContrato::class, 'id_contrato');
    }





}
