<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contador_id')->constrained('contadores')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete(); // Operario que tomó la lectura
            $table->string('periodo', 7); // ej: '2026-08'
            $table->decimal('lectura_anterior', 10, 2);
            $table->decimal('lectura_actual', 10, 2);
            $table->decimal('consumo_m3', 10, 2)
                ->storedAs('lectura_actual - lectura_anterior');
            $table->date('fecha_lectura');
            $table->timestamps();

            // Idempotencia a nivel de BD: un contador no se lee dos veces en el
            // mismo período, aunque haya doble clic o reintento de red.
            $table->unique(['contador_id', 'periodo']);

            // Listados de "lecturas del período" en la pantalla de facturación.
            $table->index('periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};
