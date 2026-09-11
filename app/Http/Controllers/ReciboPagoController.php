<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Support\AjustesDeImpresion;
use Illuminate\Contracts\View\View;

/**
 * El comprobante que se le entrega al vecino cuando paga.
 *
 * Es un documento distinto de la boleta: aquella dice cuánto debe, este dice
 * cuánto entregó y con qué. Lleva su propio correlativo justamente porque en
 * una oficina que recibe efectivo el recibo numerado es el control de caja.
 */
class ReciboPagoController extends Controller
{
    public function __invoke(Pago $pago): View
    {
        $this->authorize('view', $pago);

        abort_if($pago->esta_revertido, 404, 'Ese pago fue revertido y su recibo ya no tiene validez.');

        $pago->load(['boleta.cliente', 'boleta.lectura.contador.predio', 'boleta.periodo', 'metodoPago', 'usuario']);

        return view('recibos.pago', [
            'pago' => $pago,
            'boleta' => $pago->boleta,
            'saldo' => $pago->boleta->saldo,
            'ajustes' => app(AjustesDeImpresion::class),
        ]);
    }
}
