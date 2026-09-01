<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('paja_id')->constrained('pajas')->restrictOnDelete(); // paja contratada por este contador
            // El código identifica un medidor físico y no se reutiliza: un contador
            // dado de baja lógica conserva su código. Para volver a usarlo, restaurar
            // el registro existente en vez de crear uno nuevo.
            $table->string('codigo', 30)->unique();
            $table->string('ubicacion')->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'dañado'])->default('activo');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['cliente_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contadores');
    }
};
