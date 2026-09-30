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
        Schema::create('retiro_contrato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_contrato')->constrained('contrato');

            $table->boolean('otra_unidad')->default(false);
            $table->foreignId('id_departamento_destino')->nullable()->constrained('departamentos');

            $table->date('fecha');
            $table->string('no_factura', 100)->nullable();
            $table->date('fecha_factura')->nullable();
            $table->text('descripcion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retiro_contrato');
    }
};
