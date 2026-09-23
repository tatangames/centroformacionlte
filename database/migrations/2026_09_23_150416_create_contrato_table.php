<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CONTATOS O PROCESOS
     */
    public function up(): void
    {
        Schema::create('contrato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_proveedor')->constrained('proveedores');

            // CPB-050-202XSAN
            $table->string('codigo', 300)->nullable();
            $table->string('nombre_proceso', 300);

            // FECHAS DE CONTRATO
            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            $table->text('descripcion')->nullable();

            $table->enum('estado', [
                'vigente',
                'finalizado',
            ])->default('vigente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrato');
    }
};
