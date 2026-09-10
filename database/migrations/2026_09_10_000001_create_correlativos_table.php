<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Numeración correlativa del padrón: códigos de cliente y de contador.
     *
     * Va en tabla propia y no se deduce con un MAX(codigo)+1 por dos razones:
     * los códigos ya cargados no siguen un formato común del cual deducir nada
     * ('093190', 'CLI-0001', 'ctr01039'), y un MAX() entrega el mismo número a
     * dos altas simultáneas.
     *
     * No se mezcla con `series_documento` a propósito: aquella numera
     * documentos fiscales, con ejercicio y reinicio anual. Un código de cliente
     * no reinicia nunca.
     */
    public function up(): void
    {
        Schema::create('correlativos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 30)->unique(); // 'cliente', 'contador'
            $table->string('prefijo', 10)->default('');
            $table->unsignedTinyInteger('longitud')->default(4);
            $table->unsignedInteger('siguiente_numero')->default(1);
            $table->timestamps();
        });

        DB::table('correlativos')->insert([
            [
                'clave' => 'cliente',
                'prefijo' => 'CLI-',
                'longitud' => 4,
                'siguiente_numero' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'contador',
                'prefijo' => 'CTR-',
                'longitud' => 4,
                'siguiente_numero' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('correlativos');
    }
};
