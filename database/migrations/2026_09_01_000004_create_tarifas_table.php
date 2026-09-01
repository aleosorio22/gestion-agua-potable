<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paja_id')->constrained('pajas');
            $table->decimal('monto_base', 10, 2); // cuota fija si no hay excedente
            $table->decimal('precio_m3_excedente', 10, 4);
            $table->date('vigente_desde');
            // vigente_hasta = NULL significa "es la tarifa vigente actual" de esa paja.
            // Se cierra automáticamente (se le pone fecha) al crear la siguiente,
            // esto se maneja en un Service/Observer de Laravel, no aquí.
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
