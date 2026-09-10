---
paths:
  - 'app/Filament/Portal/**'
---

# Portal

## El portal es un panel aparte, no una vista más de /admin
`PortalPanelProvider` (id `portal`, ruta `/portal`) es un panel de Filament completamente separado de `AdminPanelProvider`. La razón no es cosmética: el Cliente es una población externa, de solo lectura, y cada uno solo debe poder ver sus propios datos. Mezclarlo en `/admin` con permisos de Shield habría dejado la separación dependiendo de que nadie se equivoque asignando un permiso; con paneles separados, un Cliente no tiene forma de llegar al código que arma `/admin`, sin importar qué rol tenga.

## Sin Filament Shield en este panel
El panel admin usa `bezhansalleh/filament-shield` porque necesita permisos granulares por Resource y acción, para varios roles de staff. El portal no: hay un solo rol (`Cliente`), y todo es de solo lectura por construcción del código, no por configuración de permisos. No agregar `FilamentShieldPlugin::make()` al `PortalPanelProvider`, y no correr `shield:generate` para Resources de este panel — no tiene nada que generar.

## Todo Resource es de solo lectura, sin excepción
Ningún Resource de `Portal/Resources/**` debe registrar páginas `create`, `edit` ni `delete`. Solo `index` (y `view` cuando haga falta detalle). Cada Resource sobreescribe `canCreate(): bool { return false; }` explícitamente — no basta con omitir la página, hay que decirlo también en el método, para que el botón de "crear" no aparezca en ningún lado por descuido.

## `getEloquentQuery()` filtrado por el cliente autenticado — la regla que no se puede saltar
Cada Resource del portal DEBE sobreescribir `getEloquentQuery()` para acotar por el cliente del usuario en sesión:

```php
public static function getEloquentQuery(): Builder
{
    $clienteId = auth()->user()?->cliente()?->id;

    return parent::getEloquentQuery()->where('cliente_id', $clienteId ?? 0);
}
```

Esto no es opcional ni "buena práctica": es la única barrera real entre un Cliente y los datos de otro. No existe a nivel de permisos (no hay Shield aquí) ni a nivel de esquema (la BD no sabe quién está autenticado) — vive exclusivamente en esta línea, en cada Resource nuevo. El `?? 0` es a propósito: si por lo que sea `cliente()` devuelve null, la consulta no debe regresar la tabla completa por accidente, debe regresar cero filas.

Ver `BoletaResource::getEloquentQuery()` como ejemplo ya construido.

## El vínculo usuario↔cliente vive en `cliente_accesos`, no en una columna
`users.cliente_id` no existe — se reemplazó por la tabla puente `cliente_accesos` (quién otorgó el acceso, cuándo, y si se revocó). Para llegar del usuario autenticado a su `Cliente`, usar el atajo ya construido en el modelo: `auth()->user()->cliente()` (método, no relación Eloquent — devuelve `?Cliente` directo). No consultar `cliente_accesos` a mano fuera de ese método salvo que se necesite explícitamente el historial de accesos.

## `canAccessPanel()` distingue panel, no solo rol
`User::canAccessPanel(Panel $panel)` resuelve distinto según `$panel->getId()`: para `portal` exige rol `Cliente` **y** un `ClienteAcceso` activo; para cualquier otro panel, exige un rol de `config('admin.panel_roles')` o `super_admin`. Cualquier lógica de acceso nueva que se agregue ahí debe seguir revisando `$panel->getId()` primero — asumir que un solo chequeo de rol sirve para todos los paneles reabre el hueco que este método existe para cerrar.
