<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Paja;
use App\Models\Sector;

class PortalController extends Controller
{
    /**
     * Página pública informativa: quién es la oficina, qué tarifas
     * cobra actualmente y qué sectores cubre. No requiere sesión.
     */
    public function index()
    {
        $entidad = [
            'nombre' => Configuracion::obtener('entidad.nombre', 'Oficina de Agua Potable'),
            'direccion' => Configuracion::obtener('entidad.direccion'),
            'telefono' => Configuracion::obtener('entidad.telefono'),
            'departamento' => Configuracion::obtener('ubicacion.departamento'),
            'municipio' => Configuracion::obtener('ubicacion.municipio'),
        ];

        $tarifas = Paja::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(function (Paja $paja) {
                $tarifa = $paja->tarifaVigenteEn();

                return [
                    'paja' => $paja->nombre,
                    'equivalencia_m3' => $paja->equivalencia_m3,
                    'monto_base' => $tarifa?->monto_base,
                    'precio_m3_excedente' => $tarifa?->precio_m3_excedente,
                ];
            });

        $sectores = Sector::query()
            ->activos()
            ->orderBy('orden')
            ->pluck('nombre');

        return view('portal.index', compact('entidad', 'tarifas', 'sectores'));
    }
}