<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Devuelve cada tarifa con su rango de vigencia completo.
     *
     * `vigente_hasta` no se almacena (ver la migración de tarifas): se deriva
     * como el `vigente_desde` de la siguiente tarifa de esa paja, menos un día.
     * La última de cada paja devuelve NULL, que significa "vigente hoy".
     *
     * La información en pantalla es idéntica a cuando la columna existía, con
     * la diferencia de que ahora no puede contradecir al resto de la tabla.
     *
     * Requiere funciones de ventana: MySQL 8+ / MariaDB 10.2+ / SQLite 3.25+.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS tarifas_vigencia');

        DB::statement(<<<'SQL'
            CREATE VIEW tarifas_vigencia AS
            SELECT
                t.id,
                t.paja_id,
                t.monto_base,
                t.precio_m3_excedente,
                t.vigente_desde,
                (
                    SELECT MIN(s.vigente_desde)
                    FROM tarifas s
                    WHERE s.paja_id = t.paja_id
                      AND s.vigente_desde > t.vigente_desde
                ) AS proxima_vigencia,
                t.created_at,
                t.updated_at
            FROM tarifas t
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS tarifas_vigencia');
    }
};
