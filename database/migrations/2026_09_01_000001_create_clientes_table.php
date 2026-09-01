<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            // NIT y DPI identifican a la persona: únicos para no duplicar clientes.
            // MySQL permite varios NULL en un índice único, así que siguen siendo opcionales.
            $table->string('nit', 20)->nullable()->unique();
            $table->string('dpi', 20)->nullable()->unique();
            $table->string('direccion', 255);
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            // Catálogo -> baja lógica, nunca borrado físico (buena práctica del README)
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
