<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La propiedad servida. Es permanente: el medidor se reemplaza, el predio no.
     *
     * La dirección se guarda estructurada (aldea, zona, casa) y no como texto
     * libre, para poder agrupar por sector y armar rutas de lectura.
     *
     * Departamento y municipio no van por predio: para una sola oficina son
     * siempre los mismos y viven en la tabla `configuracion`.
     */
    public function up(): void
    {
        Schema::create('predios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->nullable()->constrained('sectores')->restrictOnDelete();
            $table->string('aldea', 100)->nullable();     // 'El Porvenir'
            $table->string('zona', 10)->nullable();       // '0'
            $table->string('calle', 50)->nullable();
            $table->string('numero_casa', 20)->nullable(); // '1-31'
            $table->string('referencia', 255)->nullable(); // 'frente a la tienda'
            // Could-have del requerimiento: rutas optimizadas por geolocalización.
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['sector_id', 'aldea']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predios');
    }
};
