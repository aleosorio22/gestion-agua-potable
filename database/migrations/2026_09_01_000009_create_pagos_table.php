<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas');
            $table->foreignId('usuario_id')->constrained('users'); // Secretaria que registró el pago
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia']);
            $table->timestamps();
            // Nota: no se editan ni se borran (regla de negocio) — se refuerza
            // en el Model Policy de Filament, no a nivel de esquema.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
