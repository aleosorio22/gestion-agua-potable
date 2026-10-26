<?php

namespace App\Http\Controllers;

use App\Http\Resources\PagoCanonicoResource;
use App\Models\PagoEnvio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * La puerta por la que el microservicio despachador se lleva los pagos
 * cobrados y devuelve el resultado de su entrega.
 *
 * El microservicio no toca la base: todo pasa por acá. Así las reglas del
 * negocio —qué pago está listo para salir, cuándo se da por entregado— viven
 * en un solo lugar, y una migración del monolito no rompe el despachador en
 * silencio.
 */
class IntegracionPagosController extends Controller
{
    /**
     * Entrega los pagos que faltan por enviar y los marca como encolados.
     *
     * Las dos cosas ocurren en la misma transacción con bloqueo de fila: si
     * dos productores llaman a la vez, el segundo espera, y cuando lee ya no
     * encuentra esos pagos disponibles. Sin eso, un mismo pago podría viajar
     * dos veces a la cola.
     */
    public function reclamar(Request $request): AnonymousResourceCollection
    {
        $limite = min(
            max((int) $request->query('limite', 50), 1),
            (int) config('integracion.limite_maximo'),
        );

        $reclamados = DB::transaction(function () use ($limite): array {
            $envios = PagoEnvio::query()
                ->reclamables((int) config('integracion.minutos_reclamo'))
                ->orderBy('id')
                ->limit($limite)
                ->lockForUpdate()
                ->get();

            foreach ($envios as $envio) {
                $envio->update([
                    'estado_envio' => PagoEnvio::ENCOLADO,
                    'encolado_at' => now(),
                    // El identificador de rastreo se conserva entre reintentos:
                    // así los registros del primer intento y los del último
                    // siguen hablando del mismo pago.
                    'correlation_id' => $envio->correlation_id ?? (string) Str::uuid(),
                ]);
            }

            return $envios->pluck('id')->all();
        });

        // Las relaciones se cargan fuera de la transacción y de una sola vez:
        // dentro, cada acceso habría alargado el bloqueo sin necesidad.
        $envios = PagoEnvio::query()
            ->whereIn('id', $reclamados)
            ->with([
                'pago.boleta.cliente',
                'pago.boleta.periodo',
                'pago.boleta.lectura.contador',
                'pago.metodoPago',
            ])
            ->orderBy('id')
            ->get();

        return PagoCanonicoResource::collection($envios);
    }

    /**
     * Registra el resultado de un destino.
     *
     * Tiene que poder repetirse sin cambiar nada: DynamoDB Streams entrega
     * cada evento al menos una vez, así que el mismo resultado puede llegar
     * dos veces. Por eso un OK nunca se degrada a ERROR y los resultados de
     * una ronda ya superada se descartan.
     */
    public function actualizarEnvio(Request $request, string $llave): JsonResponse
    {
        $datos = $request->validate([
            'destino' => 'required|in:BANCO,ERP',
            'estado' => 'required|in:OK,ERROR',
            'ronda' => 'required|integer|min:1',
            'comprobante' => 'nullable|string|max:40',
            'partida' => 'nullable|string|max:40',
            'error' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($datos, $llave): JsonResponse {
            $envio = PagoEnvio::query()
                ->where('llave', $llave)
                ->lockForUpdate()
                ->firstOrFail();

            if ($datos['ronda'] < $envio->ronda) {
                return response()->json([
                    'ignorado' => 'El resultado corresponde a una ronda anterior.',
                    'estado_envio' => $envio->estado_envio,
                ]);
            }

            $destino = $datos['destino'] === 'BANCO' ? 'banco' : 'erp';

            // Un destino ya entregado no se vuelve a tocar: si el mismo
            // resultado llega repetido, la segunda vez no cambia nada.
            if ($envio->{"estado_{$destino}"} !== 'OK') {
                $envio->{"estado_{$destino}"} = $datos['estado'];
                $envio->{"error_{$destino}"} = $datos['estado'] === 'ERROR'
                    ? $datos['error']
                    : null;

                if (filled($datos['comprobante'] ?? null)) {
                    $envio->{"comprobante_{$destino}"} = $datos['comprobante'];
                }

                if ($destino === 'erp' && filled($datos['partida'] ?? null)) {
                    $envio->partida_erp = $datos['partida'];
                }
            }

            $envio->estado_envio = match (true) {
                $envio->estado_banco === 'OK' && $envio->estado_erp === 'OK' => PagoEnvio::ENVIADO,
                $envio->estado_banco === 'ERROR' || $envio->estado_erp === 'ERROR' => PagoEnvio::ERROR,
                default => $envio->estado_envio,
            };

            if ($envio->estado_envio === PagoEnvio::ENVIADO) {
                // La primera vez que quedó completo, no la última que avisaron.
                $envio->enviado_at ??= now();
            }

            $envio->save();

            return response()->json([
                'ok' => true,
                'estado_envio' => $envio->estado_envio,
            ]);
        });
    }
}
