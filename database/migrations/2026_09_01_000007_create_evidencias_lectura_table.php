<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias_lectura', function (Blueprint $table) {
            $table->id();
            // La evidencia no tiene vida propia: si se borra la lectura, se va con ella.
            $table->foreignId('lectura_id')->constrained('lecturas')->cascadeOnDelete();
            $table->string('archivo_url', 500); // ruta en storage/app/public
            $table->string('descripcion')->nullable();
            $table->foreignId('subido_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_lectura');
    }
};
