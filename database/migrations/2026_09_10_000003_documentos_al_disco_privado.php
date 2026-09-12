<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El expediente guarda DPI, escrituras y contratos de vecinos. El disco
     * `public` de Laravel sirve los archivos por URL directa, sin pasar por
     * autenticación: cualquiera que adivine la ruta se descarga el documento
     * de identidad de otra persona.
     *
     * La columna se conserva —un despliegue puede tener su propio disco, S3 por
     * ejemplo— pero el valor por defecto deja de invitar al error.
     */
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->string('disco', 20)->default('local')->change();
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->string('disco', 20)->default('public')->change();
        });
    }
};
