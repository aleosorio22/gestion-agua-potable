<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qué parte del padrón le toca caminar a cada lector.
     *
     * Sin esto todos los lectores ven todos los contadores: la única barrera
     * contra el recorrido duplicado es el unique de `lecturas`, que avisa recién
     * cuando el segundo ya llegó a la casa y sacó el celular.
     *
     * La asignación es fija y no por período a propósito: una oficina
     * comunitaria reparte los sectores una vez y los sostiene meses. Repartir
     * cada ciclo sería trabajo administrativo mensual para resolver algo que
     * casi nunca cambia.
     *
     * Sin filas para un lector, ve todo el padrón — es el caso de la oficina de
     * un solo lector, que no tiene nada que repartir.
     */
    public function up(): void
    {
        Schema::create('lector_sectores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained('sectores')->cascadeOnDelete();
            $table->timestamps();

            // Asignar dos veces el mismo sector al mismo lector no significa
            // nada distinto de asignarlo una.
            $table->unique(['user_id', 'sector_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lector_sectores');
    }
};
