<?php

namespace Database\Seeders;

use App\Models\SerieDocumento;
use Illuminate\Database\Seeder;

class SerieDocumentoSeeder extends Seeder
{
    /**
     * Series de arranque. Cada entidad ajusta el formato desde el panel:
     * con esta configuración el primer folio sale como BOL-2026000001.
     */
    public function run(): void
    {
        $series = [
            [
                'tipo_documento' => 'boleta',
                'codigo' => 'BOL',
                'prefijo' => 'BOL',
                'separador' => '-',
                'incluye_anio' => true,
                'longitud_numero' => 6,
                'reinicia_cada_anio' => true,
                'ejercicio' => (int) now()->year,
                'siguiente_numero' => 1,
                'activa' => true,
            ],
            [
                'tipo_documento' => 'recibo_pago',
                'codigo' => 'REC',
                'prefijo' => 'REC',
                'separador' => '-',
                'incluye_anio' => true,
                'longitud_numero' => 6,
                'reinicia_cada_anio' => true,
                'ejercicio' => (int) now()->year,
                'siguiente_numero' => 1,
                'activa' => true,
            ],
        ];

        foreach ($series as $serie) {
            SerieDocumento::firstOrCreate(
                ['tipo_documento' => $serie['tipo_documento'], 'codigo' => $serie['codigo']],
                $serie
            );
        }
    }
}
