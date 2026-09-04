<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ciclo mensual de facturación.
     *
     * Reemplaza al varchar(7) que antes se repetía en lecturas y facturas, y
     * que aceptaba '2026-8', '202608' o cualquier cosa. Sobre todo, permite
     * cerrar el período: una vez cerrado no entran lecturas nuevas ni se
     * modifican las existentes, que es lo que separa un mes liquidado de uno
     * que cualquiera puede seguir tocando.
     */
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('anio');
            $table->tinyInteger('mes');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestamp('cerrado_en')->nullable();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['anio', 'mes']);
            $table->index('cerrado_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
