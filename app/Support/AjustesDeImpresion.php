<?php

namespace App\Support;

use App\Enums\FormatoPapel;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Storage;

/**
 * Cómo quiere esta oficina que salgan sus documentos impresos.
 *
 * Reúne en un solo lugar lo que hasta ahora estaba clavado en las plantillas:
 * el papel, el logotipo, el pie de página y cómo se llama cada concepto. Dos
 * oficinas distintas imprimen el mismo sistema de forma distinta sin tocar una
 * línea de código.
 */
class AjustesDeImpresion
{
    /**
     * Los nombres de fábrica de cada concepto del recibo.
     *
     * Están acá y no dispersos en las vistas para que la pantalla de
     * configuración pueda ofrecerlos como valores por defecto editables: cada
     * oficina le dice a las cosas como les dice su gente.
     *
     * @var array<string, string>
     */
    public const ETIQUETAS = [
        'recibo.titulo_cobro' => 'Detalle de cobro por servicio',
        'recibo.titulo_pago' => 'Recibo de pago',
        'recibo.contribuyente' => 'Contribuyente',
        'recibo.servicio' => 'Agua potable',
        'recibo.contador' => 'Contador',
        'recibo.canon' => 'Canon de Agua',
        'recibo.excedente' => 'Exceso de Agua / Servicio de Agua por Consumo',
        'recibo.lecturas' => 'Lecturas m³',
        'recibo.consumo' => 'Consumo',
        'recibo.total' => 'TOTAL',
        'recibo.saldo' => 'Saldo pendiente',
        'recibo.saldado' => 'BOLETA SALDADA',
        'recibo.recibi' => 'RECIBÍ',
    ];

    public function formato(): FormatoPapel
    {
        return FormatoPapel::tryFrom(
            Configuracion::obtener('impresion.formato', FormatoPapel::Termica80->value)
        ) ?? FormatoPapel::Termica80;
    }

    public function imprimeLogo(): bool
    {
        return Configuracion::obtener('impresion.mostrar_logo', '0') === '1'
            && $this->logoEnBase64() !== null;
    }

    /**
     * El logo va incrustado y no por URL: el documento se abre en una pestaña
     * aparte y se manda a imprimir enseguida, y una imagen que todavía no
     * cargó sale en blanco. Incrustada ya está cuando el navegador pinta.
     */
    public function logoEnBase64(): ?string
    {
        $ruta = Configuracion::obtener('entidad.logo');

        if (blank($ruta) || ! Storage::disk('local')->exists($ruta)) {
            return null;
        }

        $contenido = Storage::disk('local')->get($ruta);
        $mime = Storage::disk('local')->mimeType($ruta) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }

    public function pieDePagina(): ?string
    {
        $pie = Configuracion::obtener('impresion.pie_de_pagina');

        return blank($pie) ? null : $pie;
    }

    /**
     * El nombre que esta oficina le da a un concepto.
     */
    public function etiqueta(string $clave): string
    {
        $porDefecto = static::ETIQUETAS[$clave] ?? $clave;

        $propia = Configuracion::obtener("etiqueta.{$clave}");

        return blank($propia) ? $porDefecto : $propia;
    }

    /**
     * Los datos de la entidad que van en el encabezado.
     *
     * @return array<string, string|null>
     */
    public function entidad(): array
    {
        return [
            'nombre' => Configuracion::obtener('entidad.nombre', 'Oficina de Agua Potable'),
            'nit' => Configuracion::obtener('entidad.nit'),
            'direccion' => Configuracion::obtener('entidad.direccion'),
            'telefono' => Configuracion::obtener('entidad.telefono'),
            'municipio' => Configuracion::obtener('ubicacion.municipio'),
            'departamento' => Configuracion::obtener('ubicacion.departamento'),
        ];
    }

    /**
     * Si al registrar una lectura se emite su boleta en el acto.
     *
     * Apagado de fábrica: mientras la lectura no esté facturada se corrige sin
     * ceremonia, y en cuanto hay boleta corregirla obliga a anular un documento
     * contable y quemar un folio. La oficina que lee y cobra el mismo día lo
     * enciende; la que revisa la ruta antes de facturar, no.
     */
    public function emiteBoletaAlRegistrarLectura(): bool
    {
        return Configuracion::obtener('facturacion.emitir_al_registrar', '0') === '1';
    }
}
