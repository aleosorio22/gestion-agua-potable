<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `pajas.equivalencia_m3` estaba sembrada en litros (60000, 30000, 15000)
     * pese a llamarse m³.
     *
     * `Tarifa::calcularMonto()` compara el consumo del período —que viene en
     * m³— contra este número, así que con los valores en litros el excedente
     * no se cobraba nunca: ningún consumo real supera 30000.
     *
     * El umbral de 1000 distingue sin ambigüedad: una paja se mide en decenas
     * de m³, así que cualquier valor por encima de mil está en litros.
     */
    public function up(): void
    {
        DB::table('pajas')
            ->where('equivalencia_m3', '>=', 1000)
            ->update([
                'equivalencia_m3' => DB::raw('equivalencia_m3 / 1000'),
            ]);
    }

    public function down(): void
    {
        DB::table('pajas')
            ->where('equivalencia_m3', '<', 1000)
            ->update([
                'equivalencia_m3' => DB::raw('equivalencia_m3 * 1000'),
            ]);
    }
};
