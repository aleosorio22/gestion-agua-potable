<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    /**
     * Datos de la entidad que aparecen en el encabezado de la boleta impresa.
     * Se editan desde el panel; aquí solo se dejan los valores de arranque.
     */
    public function run(): void
    {
        $valores = [
            ['clave' => 'entidad.nombre', 'valor' => 'Oficina Municipal de Agua Potable', 'descripcion' => 'Nombre que aparece en la boleta'],
            ['clave' => 'entidad.nit', 'valor' => null, 'descripcion' => 'NIT de la entidad'],
            ['clave' => 'entidad.direccion', 'valor' => null, 'descripcion' => 'Dirección de la oficina'],
            ['clave' => 'entidad.telefono', 'valor' => null, 'descripcion' => 'Teléfono de contacto'],
            ['clave' => 'entidad.logo', 'valor' => null, 'descripcion' => 'Ruta del logo en storage'],
            ['clave' => 'ubicacion.departamento', 'valor' => null, 'descripcion' => 'Departamento donde opera'],
            ['clave' => 'ubicacion.municipio', 'valor' => null, 'descripcion' => 'Municipio donde opera'],
            ['clave' => 'facturacion.dias_vencimiento', 'valor' => '30', 'descripcion' => 'Días entre emisión y vencimiento'],
        ];

        foreach ($valores as $valor) {
            Configuracion::firstOrCreate(['clave' => $valor['clave']], $valor);
        }
    }
}
