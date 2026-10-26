<?php

namespace App\Console\Commands;

use App\Models\Pago;
use App\Models\PagoEnvio;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Le crea su registro de envío a los pagos que ya existían antes de que
 * hubiera integración.
 */
class BackfillPagoEnvios extends Command
{
    protected $signature = 'integracion:backfill
        {--incluir-demo : También a los pagos de los vecinos de prueba (DEMO-)}';

    protected $description = 'Crea el registro de envío de los pagos cobrados antes de la integración';

    public function handle(): int
    {
        $pagos = Pago::query()->doesntHave('envio');

        // Los vecinos de prueba son cientos de pagos inventados: mandarlos al
        // banco y al ERP ensucia los dos sistemas con datos que no existieron.
        if (! $this->option('incluir-demo')) {
            $pagos->whereHas(
                'boleta.cliente',
                fn ($cliente) => $cliente->where('codigo', 'not like', 'DEMO-%')
            );
        }

        $creados = 0;

        $pagos->chunkById(200, function (Collection $tanda) use (&$creados): void {
            foreach ($tanda as $pago) {
                PagoEnvio::create([
                    'pago_id' => $pago->id,
                    'llave' => PagoEnvio::llaveDe($pago),
                ]);

                $creados++;
            }

            $this->getOutput()->write('.');
        });

        $this->getOutput()->writeln('');

        $this->info($creados === 0
            ? 'Todos los pagos ya tenían su registro de envío.'
            : "Creados {$creados} registros de envío.");

        return self::SUCCESS;
    }
}
