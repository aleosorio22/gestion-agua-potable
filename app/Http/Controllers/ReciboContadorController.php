<?php

namespace App\Http\Controllers;

use App\Models\Boleta;
use App\Models\Configuracion;
use App\Models\Contador;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * El documento de cobro de un servicio.
 *
 * Se arma por contador y no por cliente: quien tiene dos medidores recibe dos
 * documentos, cada uno con la deuda de su tarjeta, igual que los recibos de la
 * oficina municipal que sirven de referencia.
 *
 * Acumula todas las boletas pendientes del servicio, no solo la del mes: el
 * vecino que debe cuatro meses recibe un solo papel con el total.
 */
class ReciboContadorController extends Controller
{
    public function __invoke(Contador $contador): View
    {
        $this->authorize('viewAny', Boleta::class);

        $contador->load(['cliente', 'predio.sector', 'paja']);

        $boletas = $contador->boletas()
            ->pendientes()
            ->with('periodo')
            ->get()
            ->sortBy(fn (Boleta $boleta): string => sprintf('%04d-%02d', $boleta->periodo->anio, $boleta->periodo->mes))
            ->values();

        // Imprimir es parte del circuito de cobro: deja constancia de cuándo
        // se le entregó el documento al vecino.
        Boleta::whereIn('id', $boletas->pluck('id'))->update(['impresa_en' => now()]);

        return view('recibos.contador', [
            'contador' => $contador,
            'boletas' => $boletas,
            'conceptos' => $this->conceptos($boletas),
            'total' => $boletas->sum(fn (Boleta $boleta): float => $boleta->saldo),
            'vence' => $boletas->min('fecha_vencimiento'),
            'entidad' => [
                'nombre' => Configuracion::obtener('entidad.nombre', 'Oficina de Agua Potable'),
                'nit' => Configuracion::obtener('entidad.nit'),
                'direccion' => Configuracion::obtener('entidad.direccion'),
                'telefono' => Configuracion::obtener('entidad.telefono'),
                'municipio' => Configuracion::obtener('ubicacion.municipio'),
                'departamento' => Configuracion::obtener('ubicacion.departamento'),
            ],
        ]);
    }

    /**
     * Una línea por concepto y por mes, como en el recibo de referencia: el
     * canon siempre, el exceso solo cuando lo hubo.
     *
     * @param  Collection<int, Boleta>  $boletas
     * @return Collection<int, array{concepto: string, periodo: string, monto: float}>
     */
    private function conceptos(Collection $boletas): Collection
    {
        return $boletas->flatMap(function (Boleta $boleta): array {
            $periodo = $boleta->periodo->etiqueta_larga;

            $lineas = [[
                'concepto' => 'Canon de Agua',
                'periodo' => $periodo,
                'monto' => (float) $boleta->monto_base,
            ]];

            if ((float) $boleta->monto_excedente > 0) {
                $lineas[] = [
                    'concepto' => 'Exceso de Agua / Servicio de Agua por Consumo',
                    'periodo' => $periodo,
                    'monto' => (float) $boleta->monto_excedente,
                ];
            }

            return $lineas;
        });
    }
}
