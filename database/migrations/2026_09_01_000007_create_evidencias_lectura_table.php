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
            $table->foreignId('lectura_id')->constrained('lecturas');
            $table->string('archivo_url', 500); // ruta en storage/app/public
            $table->string('descripcion')->nullable();
            $table->foreignId('subido_por')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_lectura');
    }
};
