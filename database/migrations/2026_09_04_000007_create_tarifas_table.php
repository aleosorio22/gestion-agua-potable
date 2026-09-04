<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tarifa por paja, versionada por fecha de inicio.
     *
     * No existe `vigente_hasta` a propósito: una tarifa rige desde su fecha
     * hasta que empieza la siguiente. La aplicable en una fecha dada es la de
     * mayor `vigente_desde` menor o igual a esa fecha (ver scope vigenteEn).
     *
     * Guardar el fin explícitamente permitía dos estados inválidos que MySQL
     * no puede impedir — solapamientos (dos tarifas abiertas a la vez, que
     * hacían cobrar la tarifa vieja en silencio) y huecos (una fecha sin
     * tarifa). Con este modelo ninguno de los dos es representable.
     *
     * El rango completo se consulta en la vista `tarifas_vigencia`, que lo
     * deriva con una función de ventana.
     */
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paja_id')->constrained('pajas')->restrictOnDelete();
            $table->decimal('monto_base', 10, 2); // cuota fija si no hay excedente
            $table->decimal('precio_m3_excedente', 10, 4);
            $table->date('vigente_desde');
            $table->timestamps();

            // Impide dos tarifas que empiecen el mismo día para la misma paja,
            // que es el único caso ambiguo posible. Funciona de verdad porque
            // `vigente_desde` nunca es NULL.
            $table->unique(['paja_id', 'vigente_desde']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
