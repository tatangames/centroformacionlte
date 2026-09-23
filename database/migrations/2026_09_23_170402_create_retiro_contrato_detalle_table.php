<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DETALLE DE RETIRO
     */
    public function up(): void
    {
        Schema::create('retiro_contrato_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_retiro_contrato')->constrained('retiro_contrato');
            $table->foreignId('id_contrato_detalle')->constrained('contrato_detalle');

            $table->integer('cantidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retiro_contrato_detalle');
    }
};
