<?php

namespace App\Support;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Cómo se llama y se ve la oficina en los paneles.
 *
 * Los paneles decían «Laravel», que es el nombre por defecto del framework. Los
 * datos para arreglarlo ya estaban en Configuración desde el primer día, sin
 * que nadie los leyera fuera de los documentos impresos.
 *
 * Todo lo de acá se consulta mientras se pinta la pantalla, nunca al registrar
 * el panel: un `PanelProvider` se construye en cada arranque de la aplicación,
 * incluso cuando se corre `migrate` sobre una base vacía o `config:cache` en el
 * despliegue. Consultar la tabla ahí rompería la instalación antes de existir.
 */
class IdentidadDeLaEntidad
{
    public function nombre(): string
    {
        return $this->ajuste('entidad.nombre') ?? config('app.name');
    }

    /**
     * El logotipo para el encabezado del panel, si la oficina lo cargó y pidió
     * que se muestre.
     *
     * Reusa el mismo interruptor que los documentos impresos: una oficina que
     * no quiere su logo en el papel tampoco lo quiere en la pantalla, y dos
     * ajustes para lo mismo solo invitan a que se contradigan.
     */
    public function logo(): ?string
    {
        if ($this->ajuste('impresion.mostrar_logo') !== '1') {
            return null;
        }

        return $this->seguro(fn (): ?string => app(AjustesDeImpresion::class)->logoEnBase64());
    }

    /**
     * Lo que se lee en la pestaña del navegador, después del nombre de la
     * pantalla: «Usuarios — Oficina Municipal de Agua».
     */
    public function sufijoDeTitulo(): string
    {
        return $this->nombre();
    }

    private function ajuste(string $clave): ?string
    {
        return $this->seguro(fn (): ?string => Configuracion::obtener($clave));
    }

    /**
     * Devuelve null en vez de estallar si la tabla todavía no existe.
     *
     * Pasa de verdad: `php artisan migrate` sobre una base recién creada
     * levanta la aplicación entera —paneles incluidos— antes de que exista
     * `configuracion`.
     *
     * @template T
     *
     * @param  callable(): T  $consulta
     * @return T|null
     */
    private function seguro(callable $consulta): mixed
    {
        try {
            if (! Schema::hasTable('configuracion')) {
                return null;
            }

            return $consulta();
        } catch (Throwable) {
            return null;
        }
    }
}
