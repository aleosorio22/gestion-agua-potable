<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La persona titular del servicio.
     *
     * La dirección del servicio NO va aquí: vive en `predios`, porque un
     * cliente puede tener servicio en varias propiedades. Lo que queda aquí
     * es la dirección de notificación de la persona, que es opcional.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            // Código corto y estable que el ciudadano puede citar en ventanilla.
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 150);
            // NIT y DPI identifican a la persona: únicos para no duplicar clientes.
            // MySQL permite varios NULL en un índice único, así que siguen siendo opcionales.
            $table->string('nit', 20)->nullable()->unique();
            $table->string('dpi', 20)->nullable()->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            // Dirección de notificación de la persona, no del predio servido.
            $table->string('direccion_notificacion', 255)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            // Baja lógica: solo para registros creados por error o duplicados.
            // La situación real del negocio (suspendido por mora) va en `estado`.
            $table->softDeletes();
            $table->timestamps();

            $table->index(['estado', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
