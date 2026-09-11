<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Los papeles en que puede salir un documento de la oficina.
 *
 * Cada oficina imprime con lo que tiene: la que compró una térmica de
 * ventanilla usa rollo de 80 o 58 mm; la que imprime en la impresora de la
 * computadora usa carta u oficio. No es una preferencia estética — un recibo
 * maquetado para 80 mm sale ilegible en A4 y al revés.
 *
 * El ancho útil descuenta los márgenes: es el que se le da al cuerpo del
 * documento para que el navegador no reparta el contenido fuera de la hoja.
 */
enum FormatoPapel: string implements HasLabel
{
    case Termica80 = 'termica_80';
    case Termica58 = 'termica_58';
    case Carta = 'carta';
    case Oficio = 'oficio';
    case A4 = 'a4';

    public function getLabel(): string
    {
        return match ($this) {
            self::Termica80 => 'Rollo térmico 80 mm',
            self::Termica58 => 'Rollo térmico 58 mm',
            self::Carta => 'Carta (216 × 279 mm)',
            self::Oficio => 'Oficio (216 × 330 mm)',
            self::A4 => 'A4 (210 × 297 mm)',
        };
    }

    /**
     * Lo que va en la regla `@page size`.
     */
    public function tamanoCss(): string
    {
        return match ($this) {
            self::Termica80 => '80mm auto',
            self::Termica58 => '58mm auto',
            self::Carta => '216mm 279mm',
            self::Oficio => '216mm 330mm',
            self::A4 => '210mm 297mm',
        };
    }

    public function margen(): string
    {
        return $this->esRollo() ? '4mm' : '14mm';
    }

    /**
     * Ancho del cuerpo del documento, ya descontados los márgenes.
     */
    public function anchoUtil(): string
    {
        return match ($this) {
            self::Termica80 => '72mm',
            self::Termica58 => '50mm',
            self::Carta, self::Oficio => '188mm',
            self::A4 => '182mm',
        };
    }

    /**
     * El rollo se lee de cerca y en tipografía monoespaciada; la hoja tiene
     * espacio para respirar.
     */
    public function tamanoDeLetra(): string
    {
        return match ($this) {
            self::Termica58 => '9px',
            self::Termica80 => '10.5px',
            default => '12.5px',
        };
    }

    public function esRollo(): bool
    {
        return in_array($this, [self::Termica80, self::Termica58], true);
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $formato): array => [$formato->value => $formato->getLabel()])
            ->all();
    }
}
