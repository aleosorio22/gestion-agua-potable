<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('lectura_id')->constrained('lecturas');
            $table->foreignId('tarifa_id')->constrained('tarifas'); // snapshot de qué tarifa aplicó
            $table->string('periodo', 7);
            $table->decimal('consumo_m3', 10, 2); // copiado de la lectura al emitir (inmutable)
            $table->decimal('monto', 10, 2);       // monto_base [+ excedente] ya calculado
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['pendiente', 'pagada', 'vencida', 'anulada'])->default('pendiente');
            $table->timestamp('impresa_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
