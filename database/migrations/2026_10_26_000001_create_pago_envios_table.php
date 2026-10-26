<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado del envío de cada pago al banco y al ERP municipal.
     *
     * Va en tabla aparte y no como columnas de `pagos` por dos razones: el
     * estado de la integración es otro concepto —el cobro ya ocurrió, lo que
     * falta es avisarle a dos sistemas externos—, y los pagos son inmutables
     * por observer y por policy, así que colgarles columnas que cambian con
     * cada intento habría peleado con esa garantía.
     */
    public function up(): void
    {
        Schema::create('pago_envios', function (Blueprint $table) {
            $table->id();

            // Único: a un pago le corresponde un envío. Un reintento sube la
            // `ronda`, no crea una fila nueva.
            $table->foreignId('pago_id')->unique()->constrained('pagos')->restrictOnDelete();

            // La identidad del pago ante el banco (header `Idempotency-Key`) y
            // ante el ERP (`documento_origen.numero`). Es estable: todos los
            // reintentos del mismo pago la reusan, y es justamente eso lo que
            // permite a los destinos reconocer el duplicado en vez de cobrar
            // dos veces.
            $table->string('llave', 64)->unique();

            // Un reintento manual sube la ronda, para no confundir el
            // resultado nuevo con el de un intento viejo que llegue tarde.
            $table->unsignedInteger('ronda')->default(1);

            $table->enum('estado_envio', ['PENDIENTE', 'ENCOLADO', 'ENVIADO', 'ERROR'])
                ->default('PENDIENTE');

            // Estado por destino: un pago puede quedar entregado en el ERP y
            // rechazado por el banco. Es el caso real del cheque, que el banco
            // no admite por este canal. Un solo estado no podría representarlo.
            $table->enum('estado_banco', ['PENDIENTE', 'OK', 'ERROR'])->default('PENDIENTE');
            $table->enum('estado_erp', ['PENDIENTE', 'OK', 'ERROR'])->default('PENDIENTE');

            $table->string('comprobante_banco', 40)->nullable();
            $table->string('comprobante_erp', 40)->nullable();
            $table->string('partida_erp', 40)->nullable();
            $table->string('error_banco')->nullable();
            $table->string('error_erp')->nullable();

            // El mismo identificador viaja por el producer, la cola, el
            // consumer, los dos destinos y DynamoDB: es lo que permite seguir
            // un pago por todos los registros a la vez.
            $table->uuid('correlation_id')->nullable();

            $table->timestamp('encolado_at')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamps();

            // La consulta del producer: lo pendiente, más lo encolado que se
            // quedó sin respuesta.
            $table->index(['estado_envio', 'encolado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_envios');
    }
};
