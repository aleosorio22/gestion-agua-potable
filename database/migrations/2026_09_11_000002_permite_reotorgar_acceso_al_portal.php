<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `cliente_accesos` nació con `unique()` sobre `cliente_id` y sobre
     * `user_id` a secas, sin mirar si el acceso seguía vigente. Eso deja una
     * sola fila por cliente en toda la historia de la tabla: se puede revocar
     * una vez y nunca volver a otorgar, ni a esa persona ni a otra.
     *
     * Contradice el propósito de la tabla, que existe justamente para revocar
     * sin perder el historial.
     *
     * La unicidad correcta es «un acceso vigente por cliente», y eso no se
     * expresa con un índice común. El truco es una columna generada que vale 1
     * mientras el acceso rige y NULL cuando se revocó: como los índices únicos
     * admiten varios NULL, quedan tantas filas revocadas como haga falta y una
     * sola vigente.
     *
     * Va `virtualAs` y no `storedAs` porque SQLite —la base de las pruebas— no
     * admite agregar columnas generadas STORED con ALTER TABLE.
     */
    public function up(): void
    {
        Schema::table('cliente_accesos', function (Blueprint $table) {
            $table->unsignedTinyInteger('vigente')
                ->virtualAs('case when revocado_en is null then 1 else null end');
        });

        // Los nuevos van antes de soltar los viejos: MySQL se niega a quitar un
        // índice mientras sea el único que sostiene una clave foránea, y estos
        // llevan la misma columna a la izquierda, así que sirven de relevo.
        Schema::table('cliente_accesos', function (Blueprint $table) {
            $table->unique(['cliente_id', 'vigente'], 'cliente_accesos_cliente_vigente_unique');
            $table->unique(['user_id', 'vigente'], 'cliente_accesos_user_vigente_unique');
        });

        Schema::table('cliente_accesos', function (Blueprint $table) {
            $table->dropUnique(['cliente_id']);
            $table->dropUnique(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cliente_accesos', function (Blueprint $table) {
            $table->unique('cliente_id');
            $table->unique('user_id');
        });

        Schema::table('cliente_accesos', function (Blueprint $table) {
            $table->dropUnique('cliente_accesos_cliente_vigente_unique');
            $table->dropUnique('cliente_accesos_user_vigente_unique');
            $table->dropColumn('vigente');
        });
    }
};
