<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pajas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique(); // ej: '1 paja', '1/2 paja'
            // En m³. Es lo que se compara contra el consumo del período, así
            // que en litros el excedente no se cobraría nunca.
            $table->decimal('equivalencia_m3', 10, 2); // editable, ej: 30.00
            // Si una paja se descontinúa se marca inactiva; nunca se deja sin tarifa.
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pajas');
    }
};
