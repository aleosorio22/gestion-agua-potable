<?php

namespace App\Http\Resources;

use App\Models\PagoEnvio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RuntimeException;

/**
 * El pago, en el formato neutral que entiende el microservicio despachador.
 *
 * Es el contrato entre Laravel y Java. A propósito no se parece ni al del
 * banco ni al del ERP: cada uno pide los campos con otros nombres, otra
 * unidad y otro idioma, y traducir a los dos es trabajo del consumer. Lo que
 * sí garantiza esta clase es que ningún destino reciba un dato que el
 * monolito no tiene.
 *
 * @mixin PagoEnvio
 */
class PagoCanonicoResource extends JsonResource
{
    /**
     * Los cinco métodos del catálogo contra los cuatro que admiten los
     * contratos.
     *
     * `deposito` se colapsa a TRANSFERENCIA porque para el banco ambos son
     * dinero que entró por la vía bancaria; el matiz se conserva del lado del
     * ERP, donde el tipo de documento pasa a ser una boleta de depósito.
     *
     * `cheque` se manda tal cual aunque el banco lo rechace: ese rechazo es
     * una respuesta legítima del contrato, no un error que haya que esconder.
     *
     * @var array<string, string>
     */
    private const FORMAS_DE_PAGO = [
        'efectivo' => 'EFECTIVO',
        'tarjeta' => 'TARJETA',
        'transferencia' => 'TRANSFERENCIA',
        'deposito' => 'TRANSFERENCIA',
        'cheque' => 'CHEQUE',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pago = $this->pago;
        // El pago no conoce al cliente: llega a él por la boleta que cubre,
        // que es la que guarda el snapshot del cobro.
        $boleta = $pago->boleta;
        $periodo = $boleta->periodo;

        $total = self::monto($pago->monto);
        $etiquetaPeriodo = sprintf('%04d-%02d', $periodo->anio, $periodo->mes);

        return [
            'llave' => $this->llave,
            'pagoId' => $pago->id,
            // El número del recibo que el vecino tiene en la mano: no es la
            // llave de idempotencia, pero es por el que va a preguntar.
            'folio' => $pago->folio,
            'ronda' => $this->ronda,
            'correlationId' => $this->correlation_id,
            'cliente' => [
                'codigo' => $boleta->cliente->codigo,
                'nombre' => $boleta->cliente->nombre,
                // El NIT es opcional en el padrón; sin él, el contribuyente
                // es consumidor final.
                'nit' => $boleta->cliente->nit ?: 'CF',
                'email' => $boleta->cliente->email,
            ],
            'contador' => $boleta->lectura->contador->codigo,
            'periodo' => [
                'anio' => (int) $periodo->anio,
                'mes' => (int) $periodo->mes,
            ],
            'formaPago' => self::formaDePago($pago->metodoPago->codigo),
            // La hora real del cobro sale de created_at: `fecha_pago` es una
            // columna de fecha y daría siempre medianoche, y los dos contratos
            // piden fecha y hora con zona horaria.
            'fechaPago' => $pago->created_at->toIso8601String(),
            // La fecha de valor del recibo, que es lo que el ERP pide como
            // fecha de emisión del documento de origen.
            'fechaEmision' => $pago->fecha_pago->toDateString(),
            'total' => $total,
            'descripcion' => mb_substr("Servicio de agua {$etiquetaPeriodo}", 0, 140),
            // Una sola línea por el total. Partirla en cuota base y excedente
            // sería tentador, pero esos montos son de la boleta y un pago
            // puede ser un abono parcial: entonces el desglose no sumaría el
            // total y el ERP rechazaría el documento.
            'detalle' => [
                [
                    'rubro' => 'AGUA_POTABLE',
                    'descripcion' => "Consumo {$etiquetaPeriodo}",
                    'monto' => $total,
                ],
            ],
        ];
    }

    /**
     * Falla fuerte si aparece un método de pago sin traducción: mandarlo como
     * efectivo por descarte falsearía el corte de caja de los dos destinos.
     */
    private static function formaDePago(string $codigo): string
    {
        return self::FORMAS_DE_PAGO[$codigo]
            ?? throw new RuntimeException(
                "El método de pago '{$codigo}' no tiene equivalente en los contratos del banco y el ERP."
            );
    }

    /**
     * Texto con dos decimales exactos. Java lo lee como BigDecimal, así que
     * ningún monto pasa por un flotante en todo el camino.
     */
    private static function monto(string|float $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
