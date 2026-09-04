<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cobro recibido en ventanilla.
     *
     * Lleva correlativo propio: en una oficina que recibe efectivo, el recibo
     * numerado es el control de caja básico — es lo que permite cuadrar al
     * final del día y detectar un cobro que entró sin registrarse.
     *
     * No se editan ni se borran. Un cheque rechazado o un error de digitación
     * se resuelven con el reverso, que deja ambos hechos en el historial.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            // --- Correlativo del recibo ---
            $table->foreignId('serie_id')->constrained('series_documento')->restrictOnDelete();
            $table->smallInteger('ejercicio');
            $table->unsignedInteger('numero');
            $table->string('folio', 30);

            $table->foreignId('boleta_id')->constrained('boletas')->restrictOnDelete();
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete(); // quién cobró
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->string('referencia', 100)->nullable(); // boleta de depósito, no. de cheque

            // --- Reverso (contra-asiento) ---
            $table->timestamp('revertido_en')->nullable();
            $table->foreignId('revertido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_reverso')->nullable();

            $table->timestamps();

            $table->unique(['serie_id', 'ejercicio', 'numero']);
            $table->unique('folio');
            $table->index(['boleta_id', 'revertido_en']);
            $table->index('fecha_pago'); // cortes de caja por día
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
