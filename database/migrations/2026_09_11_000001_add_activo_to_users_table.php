<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Si la cuenta sigue vigente.
     *
     * Un usuario que ya trabajó no se puede borrar: cuatro claves foráneas lo
     * retienen con `restrictOnDelete` —las lecturas que tomó, los pagos que
     * registró, los documentos y evidencias que subió—, y está bien que así
     * sea, porque borrarlo borraría la trazabilidad de quién hizo qué.
     *
     * Sin esta columna, dar de baja a quien deja la oficina obligaba a quitarle
     * todos los roles: funciona, pero pierde el registro de qué era y
     * reactivarlo exige reasignarlos de memoria.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
