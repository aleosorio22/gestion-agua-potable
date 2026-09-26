<?php

namespace App\Providers;

use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Documento;
use App\Models\Lectura;
use App\Models\Pago;
use App\Models\SerieDocumento;
use App\Models\Tarifa;
use App\Observers\BoletaObserver;
use App\Observers\CodigoCorrelativoObserver;
use App\Observers\DocumentoObserver;
use App\Observers\LecturaObserver;
use App\Observers\PagoObserver;
use App\Observers\SerieDocumentoObserver;
use App\Observers\TarifaObserver;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Lectura::observe(LecturaObserver::class);
        Boleta::observe(BoletaObserver::class);
        Pago::observe(PagoObserver::class);
        Tarifa::observe(TarifaObserver::class);
        SerieDocumento::observe(SerieDocumentoObserver::class);
        Documento::observe(DocumentoObserver::class);

        Cliente::observe(CodigoCorrelativoObserver::class);
        Contador::observe(CodigoCorrelativoObserver::class);

        $this->aplicarContadorDeCaracteres(TextInput::class);
        $this->aplicarContadorDeCaracteres(Textarea::class);
    }

    /**
     * Aplica un contador de caracteres vivo a todo TextInput/Textarea que
     * ya tenga ->maxLength() definido. Los campos sin límite no se tocan.
     */
    private function aplicarContadorDeCaracteres(string $clase): void
    {
        $clase::configureUsing(function (TextInput|Textarea $component): void {
            $component
                ->live(condition: fn (): bool => $component->getMaxLength() !== null)
                ->hint(function (?string $state) use ($component): ?string {
                    $max = $component->getMaxLength();

                    if ($max === null) {
                        return null;
                    }

                    $actual = mb_strlen($state ?? '');

                    return "{$actual}/{$max} caracteres";
                })
                ->hintColor(function (?string $state) use ($component): string {
                    $max = $component->getMaxLength();

                    if ($max === null) {
                        return 'gray';
                    }

                    $actual = mb_strlen($state ?? '');

                    return $actual >= $max ? 'danger' : 'gray';
                });
        });
    }
}
