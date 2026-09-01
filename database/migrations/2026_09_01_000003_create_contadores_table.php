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
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('paja_id')->constrained('pajas'); // paja contratada por este contador
            $table->string('codigo', 30)->unique();
            $table->string('ubicacion')->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'dañado'])->default('activo');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contadores');
    }
};
