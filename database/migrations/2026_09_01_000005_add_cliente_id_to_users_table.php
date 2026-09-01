<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Solo se llena para el rol Cliente (login del portal de autoservicio).
            // NULL para Administrador/Secretaria/Operario.
            // Único: un cliente tiene como mucho un login (Cliente::usuario() es hasOne).
            // MySQL admite varios NULL en un índice único, así que el resto de
            // roles siguen pudiendo tener cliente_id nulo sin chocar entre sí.
            $table->foreignId('cliente_id')
                ->nullable()
                ->after('id')
                ->unique()
                ->constrained('clientes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });
    }
};
