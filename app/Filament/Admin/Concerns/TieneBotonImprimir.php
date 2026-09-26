<?php

namespace App\Filament\Admin\Concerns;

use Filament\Actions\Action;

trait TieneBotonImprimir
{
    protected function getImprimirAction(): Action
    {
        return Action::make('imprimir')
            ->label('Imprimir')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url('javascript:window.print()');
    }
}
