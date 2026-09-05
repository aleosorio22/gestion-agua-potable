<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Numeración correlativa configurable por entidad.
     *
     * El formato se arma con las piezas de esta tabla, de modo que cada oficina
     * define su nomenclatura desde la UI sin tocar código:
     *
     *   prefijo 'BOL' · separador ''  · sin año · 6 dígitos  →  BOL009130
     *   prefijo 'BOL' · separador '-' · con año · 6 dígitos  →  BOL-2026003131
     *
     * No se usa el id autoincremental como número de documento: AUTO_INCREMENT
     * deja huecos por diseño (un INSERT fallido consume el número igual).
     */
    public function up(): void
    {
        Schema::create('series_documento', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_documento', ['boleta', 'recibo_pago']);
            $table->string('codigo', 20); // identificador interno de la serie
            $table->string('prefijo', 10)->default('');
            $table->string('separador', 5)->default('');
            $table->boolean('incluye_anio')->default(false);
            $table->tinyInteger('longitud_numero')->default(6);
            $table->boolean('reinicia_cada_anio')->default(false);
            $table->smallInteger('ejercicio'); // año en curso de la numeración
            $table->unsignedInteger('siguiente_numero')->default(1);
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['tipo_documento', 'codigo']);
            $table->index(['tipo_documento', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series_documento');
    }
};
