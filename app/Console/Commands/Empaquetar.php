<?php

namespace App\Console\Commands;

use App\Services\EmpaquetadorDeDistribucion;
use Illuminate\Console\Command;
use RuntimeException;

use function Laravel\Prompts\confirm;

/**
 * Arma el ZIP que se sube a un hosting compartido.
 */
class Empaquetar extends Command
{
    protected $signature = 'app:empaquetar
        {--destino= : Dónde escribir el archivo (por omisión, storage/app/paquetes)}
        {--sin-preguntar : No pedir confirmación por los cambios sin confirmar}';

    protected $description = 'Arma el paquete instalable para hosting compartido';

    public function handle(EmpaquetadorDeDistribucion $empaquetador): int
    {
        $faltantes = $empaquetador->loQueFalta();

        if ($faltantes !== []) {
            $this->components->error('El proyecto todavía no está listo para empaquetarse.');

            foreach ($faltantes as $faltante) {
                $this->components->bulletList([$faltante]);
            }

            return self::FAILURE;
        }

        if (! $this->confirmarLosCambiosSinGuardar($empaquetador)) {
            return self::FAILURE;
        }

        $destino = $this->destino();

        $this->newLine();

        try {
            $empaquetador->empaquetar($destino, function (string $paso): void {
                $this->components->task($paso);
            });
        } catch (RuntimeException $error) {
            $this->newLine();
            $this->components->error($error->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Paquete listo: '.$destino);
        $this->line('  Adentro va un LEEME.txt con los pasos para el técnico que lo instale.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * El paquete sale de lo que está confirmado en git, no del directorio de trabajo.
     */
    private function confirmarLosCambiosSinGuardar(EmpaquetadorDeDistribucion $empaquetador): bool
    {
        $cambios = $empaquetador->cambiosSinConfirmar();

        if ($cambios === [] || $this->option('sin-preguntar')) {
            return true;
        }

        $this->components->warn('Hay cambios sin confirmar; el paquete se arma con el último commit y los deja fuera:');
        $this->components->bulletList(array_slice($cambios, 0, 10));

        if (count($cambios) > 10) {
            $this->line('  ... y '.(count($cambios) - 10).' más.');
        }

        return confirm('¿Empaquetar de todos modos?', default: false);
    }

    private function destino(): string
    {
        if ($elegido = $this->option('destino')) {
            return $elegido;
        }

        // El nombre no sale de APP_NAME: esa variable la cambia cada oficina y
        // el paquete tiene que llamarse igual siempre.
        return storage_path('app/paquetes/agua-potable-'.now()->format('Y-m-d').'.zip');
    }
}
