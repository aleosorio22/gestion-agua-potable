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
            $table->foreignId('cliente_id')
                ->nullable()
                ->after('id')
                ->constrained('clientes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });
    }
};
