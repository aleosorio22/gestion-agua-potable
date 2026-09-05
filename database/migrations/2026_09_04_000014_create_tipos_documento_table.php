<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique(); // 'recibo_luz', 'escritura', 'dpi'
            $table->string('nombre', 80);
            // Distingue los documentos que prueban propiedad (van ligados a un
            // predio) de los que solo identifican a la persona (DPI, NIT).
            $table->boolean('respalda_predio')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_documento');
    }
};
