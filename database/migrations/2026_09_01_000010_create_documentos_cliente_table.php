<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->enum('tipo', ['confirmacion', 'contrato']);
            $table->string('archivo_url', 500);
            $table->boolean('firmado')->default(false);
            $table->date('fecha_firma')->nullable();
            $table->foreignId('subido_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['cliente_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_cliente');
    }
};
