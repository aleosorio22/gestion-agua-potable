<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documento de cobro que emite la oficina.
     *
     * Se llama boleta y no factura a propósito: la factura es el documento
     * tributario certificado ante SAT, cuya serie y número los asigna el
     * certificador, no este sistema. Eso vive en `documentos_fiscales`.
     *
     * Guarda copia inmutable (snapshot) de los datos con que se emitió. Que
     * `consumo_m3` y `cliente_id` estén duplicados incumple 3FN a propósito:
     * un documento contable conserva lo que decía el día que se entregó,
     * aunque después se corrija la lectura o el predio cambie de titular.
     */
    public function up(): void
    {
        Schema::create('boletas', function (Blueprint $table) {
            $table->id();

            // --- Correlativo ---
            $table->foreignId('serie_id')->constrained('series_documento')->restrictOnDelete();
            $table->smallInteger('ejercicio');
            $table->unsignedInteger('numero');
            // El folio ya renderizado se congela al emitir: si mañana la entidad
            // cambia el formato, las boletas ya entregadas no pueden cambiar de
            // número. Es parte del snapshot, igual que el monto.
            $table->string('folio', 30);

            // --- Relaciones ---
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            // Único: una lectura genera como mucho una boleta. Sin esto, un
            // doble clic en "emitir" cobraba dos veces el mismo consumo.
            $table->foreignId('lectura_id')->unique()->constrained('lecturas')->restrictOnDelete();
            $table->foreignId('tarifa_id')->constrained('tarifas')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();

            // --- Snapshot del cálculo ---
            $table->decimal('consumo_m3', 10, 2);
            $table->decimal('monto_base', 10, 2);
            $table->decimal('monto_excedente', 10, 2)->default(0);
            $table->decimal('monto', 10, 2);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->timestamp('impresa_en')->nullable();

            // --- Anulación ---
            // No hay columna `estado`: pagada, pendiente y vencida se derivan de
            // los pagos y de la fecha. Solo la anulación es un hecho que hay que
            // registrar, y con autor y motivo.
            $table->timestamp('anulada_en')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion')->nullable();

            $table->timestamps();

            $table->unique(['serie_id', 'ejercicio', 'numero']);
            $table->unique('folio');
            $table->index(['cliente_id', 'periodo_id']);
            $table->index(['anulada_en', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletas');
    }
};
