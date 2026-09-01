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
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            // Único: una lectura genera como mucho una factura (Lectura::factura() es hasOne).
            // Sin esto, un doble clic en "emitir" facturaba dos veces el mismo consumo.
            $table->foreignId('lectura_id')->unique()->constrained('lecturas')->restrictOnDelete();
            $table->foreignId('tarifa_id')->constrained('tarifas')->restrictOnDelete(); // snapshot de qué tarifa aplicó
            $table->string('periodo', 7);
            $table->decimal('consumo_m3', 10, 2); // copiado de la lectura al emitir (inmutable)
            $table->decimal('monto', 10, 2);       // monto_base [+ excedente] ya calculado
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['pendiente', 'pagada', 'vencida', 'anulada'])->default('pendiente');
            $table->timestamp('impresa_en')->nullable();
            $table->timestamps();

            // Estado de cuenta del cliente y cortes por período.
            $table->index(['cliente_id', 'periodo']);
            // Cobranza: "pendientes vencidas al día de hoy".
            $table->index(['estado', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
