<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contrato_detalle_departamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_contrato_detalle')->constrained('contrato_detalle')->cascadeOnDelete();
            $table->foreignId('id_departamento')->constrained('departamentos');
            $table->integer('cantidad');

            $table->unique(['id_contrato_detalle', 'id_departamento'], 'cdd_detalle_depto_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrato_detalle_departamento');
    }
};
