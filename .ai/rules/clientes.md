---
paths:
  - 'app/Filament/Admin/Resources/Clientes/**'
---

# Clientes

## Clientes: baja lógica guardada, nunca borrado definitivo
El padrón de clientes reusa el mismo mecanismo que los catálogos: `EsCatalogo` en el modelo (relaciones que bloquean: contadores, boletas, documentos) y `AccionesCatalogo::eliminar()` en tabla y edición. El trait no es solo para catálogos.

`ForceDeleteAction` y las bulk actions de borrado quedaron fuera a propósito, y `ClientePolicy::forceDelete()`/`forceDeleteAny()` devuelven `false` fijo, ignorando el permiso de Shield: un titular respalda boletas y pagos que hay que poder mostrar años después. La única baja es `deleted_at`, y es para el alta duplicada o tecleada por error; la baja real del servicio va en `estado`.

`getRecordRouteBindingEloquentQuery()` quita el `SoftDeletingScope` para que un cliente eliminado se pueda abrir y restaurar.
