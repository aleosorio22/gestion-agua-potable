<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contador_id')->constrained('contadores')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete(); // Lector
            $table->decimal('lectura_anterior', 10, 2);
            $table->decimal('lectura_actual', 10, 2);
            // Columna generada: la calcula la base de datos, Eloquent solo la lee.
            // No va en $fillable y no puede desincronizarse.
            $table->decimal('consumo_m3', 10, 2)
                ->storedAs('lectura_actual - lectura_anterior');
            $table->date('fecha_lectura');
            $table->string('observaciones')->nullable();
            $table->timestamps();

            // Idempotencia a nivel de BD: un contador no se lee dos veces en el
            // mismo período, aunque haya doble clic o reintento de red.
            $table->unique(['contador_id', 'periodo_id']);
            $table->index('periodo_id');
        });

        // No se admiten consumos negativos. El caso de un medidor que da la
        // vuelta o se reemplaza se resuelve registrando el cambio de medidor,
        // no aceptando una lectura menor que la anterior.
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::getConnection()->statement(
                'ALTER TABLE lecturas ADD CONSTRAINT lecturas_consumo_no_negativo CHECK (lectura_actual >= lectura_anterior)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};
