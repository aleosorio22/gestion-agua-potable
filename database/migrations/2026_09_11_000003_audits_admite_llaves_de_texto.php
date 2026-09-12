<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `audits.auditable_id` venía de `morphs()`, o sea entero sin signo, pero
     * `configuracion` usa la clave de texto como llave primaria.
     *
     * Resultado: cada intento de auditar un cambio de configuración moría con
     * «Incorrect integer value». No se había notado porque hasta ahora nadie
     * escribía en esa tabla desde la interfaz —el seeder corre con
     * `WithoutModelEvents`, así que jamás disparó el observer—, aunque la
     * migración de `configuracion` decía desde el principio que cada cambio
     * quedaba auditado.
     *
     * Ensanchar la columna es lo que documenta laravel-auditing para modelos
     * con llave no entera, y no toca la llave primaria de nadie. Los ids
     * numéricos que ya están guardados siguen sirviendo: la comparación es por
     * igualdad y MySQL convierte.
     */
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->string('auditable_id', 191)->change();
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->unsignedBigInteger('auditable_id')->change();
        });
    }
};
