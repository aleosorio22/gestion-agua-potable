<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\LectorPanelProvider;
use App\Providers\Filament\PortalPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    LectorPanelProvider::class,
    PortalPanelProvider::class,
];
