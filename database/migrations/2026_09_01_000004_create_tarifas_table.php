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
            $table->foreignId('paja_id')->constrained('pajas')->restrictOnDelete();
            $table->decimal('monto_base', 10, 2); // cuota fija si no hay excedente
            $table->decimal('precio_m3_excedente', 10, 4);
            $table->date('vigente_desde');
            // vigente_hasta = NULL significa "es la tarifa vigente actual" de esa paja.
            // Se cierra automáticamente (se le pone fecha) al crear la siguiente,
            // esto se maneja en un Service/Observer de Laravel, no aquí.
            //
            // OJO: MySQL no soporta índices únicos parciales, así que no se puede
            // impedir a nivel de esquema que existan dos tarifas abiertas
            // (vigente_hasta NULL) para la misma paja. Esa invariante depende
            // enteramente del Service/Observer, que todavía está pendiente.
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();

            // Sirve al scope vigenteEn(), que filtra por paja y rango de fechas.
            $table->index(['paja_id', 'vigente_desde', 'vigente_hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
