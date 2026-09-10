---
paths:
  - 'app/Policies/**'
---

# Policies

## shield:generate sobrescribe las policies editadas a mano
`php artisan shield:generate --all --panel=admin` regenera TODAS las policies desde su plantilla y borra cualquier método editado a mano, sin avisar. Pasó con `ClientePolicy::forceDelete()`, que estaba en `return false` fijo y volvió a `$authUser->can('ForceDelete:Cliente')`.

Después de correrlo: `git diff app/Policies/` y restaurar lo propio. Las policies que solo cambian de orden de imports o de espacios en blanco se pueden devolver con `git checkout --` y Pint arregla el resto.

Al agregar un Resource nuevo, preferí `shield:generate --resource=<X>` sobre `--all` para no tocar las demás.

El padrón (Cliente, Contador, Predio) tiene `forceDelete()`/`forceDeleteAny()` en `false` fijo a propósito: respaldan boletas, pagos y lecturas. Si se regeneran, hay que reponer ese candado.
