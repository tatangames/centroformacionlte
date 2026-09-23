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
        Schema::create('contrato_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_contrato')->constrained('contrato');
            $table->foreignId('id_unidadmedida')->constrained('unidadmedida');

            $table->string('nombre', 300);
            $table->integer('cantidad');
            $table->decimal('precio', 10, 4);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrato_detalle');
    }
};
