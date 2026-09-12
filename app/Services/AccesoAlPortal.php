<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ClienteAcceso;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Otorga y revoca el acceso de un vecino al portal de autoservicio.
 *
 * Dar acceso toca tres cosas —la cuenta, el rol y el registro del acceso— y
 * las tres tienen que ir juntas: una cuenta con rol pero sin fila en
 * `cliente_accesos` no entra a ningún panel, y una fila sin rol tampoco.
 * `User::canAccessPanel()` exige las dos.
 */
class AccesoAlPortal
{
    /**
     * Otorga el acceso, creando la cuenta si hace falta.
     *
     * @param  string|null  $contrasena  Solo para cuentas nuevas; las existentes conservan la suya.
     */
    public function otorgar(
        Cliente $cliente,
        string $correo,
        ?string $nombre = null,
        ?string $contrasena = null,
    ): ClienteAcceso {
        $this->verificarQueElClienteNoTengaAcceso($cliente);

        return DB::transaction(function () use ($cliente, $correo, $nombre, $contrasena): ClienteAcceso {
            $usuario = User::where('email', $correo)->first();

            if ($usuario === null) {
                $usuario = User::create([
                    'name' => $nombre ?: $cliente->nombre,
                    'email' => $correo,
                    'password' => Hash::make($contrasena ?: throw new RuntimeException(
                        'Una cuenta nueva necesita contraseña inicial.'
                    )),
                ]);
            }

            $this->verificarQueLaCuentaEsteLibre($usuario);

            // El rol solo no alcanza, pero sin él tampoco entra: canAccessPanel
            // exige rol Cliente Y acceso vigente.
            $usuario->assignRole('Cliente');

            return ClienteAcceso::create([
                'cliente_id' => $cliente->getKey(),
                'user_id' => $usuario->getKey(),
                'otorgado_por' => auth()->id(),
                'otorgado_en' => now(),
            ]);
        });
    }

    /**
     * Revoca el acceso vigente del cliente, si lo tiene.
     *
     * No se le quita el rol ni se borra la cuenta: sin acceso vigente el rol no
     * abre nada, y conservar la cuenta mantiene el rastro de quién consultó
     * qué. Volver a otorgarlo es reactivar, no recrear.
     */
    public function revocar(ClienteAcceso $acceso): void
    {
        if ($acceso->revocado_en !== null) {
            return;
        }

        $acceso->revocar(auth()->user());
    }

    private function verificarQueElClienteNoTengaAcceso(Cliente $cliente): void
    {
        if ($cliente->accesoActivo()->exists()) {
            throw new RuntimeException(
                "{$cliente->nombre} ya tiene acceso vigente al portal. Revóquelo antes de otorgar uno nuevo."
            );
        }
    }

    private function verificarQueLaCuentaEsteLibre(User $usuario): void
    {
        $acceso = $usuario->clienteAcceso()->with('cliente')->first();

        if ($acceso !== null) {
            throw new RuntimeException(
                "Esa cuenta ya consulta el portal de {$acceso->cliente->nombre}. "
                .'Una cuenta atiende a un solo cliente.'
            );
        }
    }
}
