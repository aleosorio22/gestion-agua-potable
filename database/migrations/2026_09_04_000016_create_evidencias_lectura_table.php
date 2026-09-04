<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fotos que respaldan la lectura tomada en campo.
     */
    public function up(): void
    {
        Schema::create('evidencias_lectura', function (Blueprint $table) {
            $table->id();
            // La evidencia no tiene vida propia: si se borra la lectura, se va con ella.
            $table->foreignId('lectura_id')->constrained('lecturas')->cascadeOnDelete();

            $table->string('disco', 20)->default('public');
            $table->string('ruta', 500);
            $table->string('nombre_original', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('tamano_bytes');
            $table->char('hash_sha256', 64);

            $table->string('descripcion')->nullable();
            $table->foreignId('subido_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('lectura_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_lectura');
    }
};
