<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de la entidad que opera el sistema (nombre, NIT, dirección, logo).
     *
     * Va en base de datos y no en .env porque la boleta impresa los necesita
     * en el encabezado, la secretaria debe poder cambiarlos sin acceso al
     * servidor, y cada cambio queda auditado.
     */
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->string('clave', 50)->primary();
            $table->text('valor')->nullable();
            $table->string('descripcion')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
