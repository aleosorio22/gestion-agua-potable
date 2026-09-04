<?php

namespace App\Providers;

use App\Models\Boleta;
use App\Models\Lectura;
use App\Models\Pago;
use App\Observers\BoletaObserver;
use App\Observers\LecturaObserver;
use App\Observers\PagoObserver;
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
    }
}
