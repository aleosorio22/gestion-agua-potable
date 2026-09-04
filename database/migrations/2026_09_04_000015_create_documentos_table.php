<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Respaldos escaneados del expediente del cliente.
     *
     * `predio_id` es nullable a propósito, porque hay dos clases de documento:
     *   - de la persona  (DPI, NIT)            → predio_id NULL
     *   - de la propiedad (recibo de luz,      → predio_id apunta al predio
     *     escritura, contrato de servicio)
     *
     * Eso permite que un cliente con servicio en dos casas tenga el recibo de
     * luz de cada una ligado a la propiedad que respalda, sin ambigüedad.
     */
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('predio_id')->nullable()->constrained('predios')->cascadeOnDelete();
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->restrictOnDelete();

            // --- Archivo ---
            $table->string('disco', 20)->default('public');
            $table->string('ruta', 500);
            $table->string('nombre_original', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('tamano_bytes');
            // Sin hash no hay forma de demostrar que un contrato escaneado no
            // fue sustituido después de firmado.
            $table->char('hash_sha256', 64);

            $table->boolean('firmado')->default(false);
            $table->date('fecha_firma')->nullable();
            $table->foreignId('subido_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['cliente_id', 'tipo_documento_id']);
            $table->index('predio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
