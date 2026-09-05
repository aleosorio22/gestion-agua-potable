<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use App\Models\Paja;
use App\Models\TipoDocumento;
use Illuminate\Database\Seeder;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $pajas = [
            ['nombre' => '1 paja', 'equivalencia_m3' => 60000.00],
            ['nombre' => '1/2 paja', 'equivalencia_m3' => 30000.00],
            ['nombre' => '1/4 paja', 'equivalencia_m3' => 15000.00],
        ];

        foreach ($pajas as $paja) {
            Paja::firstOrCreate(['nombre' => $paja['nombre']], $paja);
        }

        $metodos = [
            ['codigo' => 'efectivo', 'nombre' => 'Efectivo', 'requiere_referencia' => false],
            ['codigo' => 'cheque', 'nombre' => 'Cheque', 'requiere_referencia' => true],
            ['codigo' => 'deposito', 'nombre' => 'Depósito bancario', 'requiere_referencia' => true],
            ['codigo' => 'transferencia', 'nombre' => 'Transferencia', 'requiere_referencia' => true],
            ['codigo' => 'tarjeta', 'nombre' => 'Tarjeta', 'requiere_referencia' => true],
        ];

        foreach ($metodos as $metodo) {
            MetodoPago::firstOrCreate(['codigo' => $metodo['codigo']], $metodo);
        }

        $tipos = [
            ['codigo' => 'dpi', 'nombre' => 'DPI', 'respalda_predio' => false],
            ['codigo' => 'nit', 'nombre' => 'Constancia de NIT', 'respalda_predio' => false],
            ['codigo' => 'recibo_luz', 'nombre' => 'Recibo de energía eléctrica', 'respalda_predio' => true],
            ['codigo' => 'escritura', 'nombre' => 'Escritura de la propiedad', 'respalda_predio' => true],
            ['codigo' => 'contrato', 'nombre' => 'Contrato de servicio', 'respalda_predio' => true],
            ['codigo' => 'confirmacion', 'nombre' => 'Confirmación de solicitud', 'respalda_predio' => true],
        ];

        foreach ($tipos as $tipo) {
            TipoDocumento::firstOrCreate(['codigo' => $tipo['codigo']], $tipo);
        }
    }
}
