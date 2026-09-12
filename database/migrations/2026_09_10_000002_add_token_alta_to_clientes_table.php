<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Huella del alta guiada, para que reenviar el mismo formulario no cree
     * dos clientes.
     *
     * Mientras el código se tecleaba a mano era, sin proponérselo, la barrera
     * contra el doble envío: el segundo chocaba contra su índice único. Al
     * generarlo el sistema esa red desaparece —los dos envíos traerían códigos
     * distintos y válidos—, así que la identidad del intento tiene que viajar
     * aparte.
     *
     * Nullable porque las altas que no pasan por el asistente (importaciones,
     * seeders, la ficha del cliente) no tienen intento que identificar.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->uuid('token_alta')->nullable()->unique()->after('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['token_alta']);
            $table->dropColumn('token_alta');
        });
    }
};
