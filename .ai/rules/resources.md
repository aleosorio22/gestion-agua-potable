---
paths:
  - 'app/Filament/Admin/Resources/**'
---

# Resources

## Catálogos: se desactivan, no se borran
Todo Resource de catálogo usa el trait `EsCatalogo` en su modelo (implementa `relacionesQueImpidenBorrado()` con etiquetas "singular|plural") y `AccionesCatalogo::eliminar()` en la tabla y en la página de edición. Las FK ya son restrictOnDelete; eso solo lo traduce a un mensaje.

Al crear un Resource nuevo: fijar `$slug` explícito (si el directorio y el plural del modelo difieren, Filament arma URLs dobles tipo `admin/sectores/sectors`), poner `$navigationGroup = GrupoNavegacion::Catalogos`, y correr `php artisan shield:generate --all --panel=admin`.

Trampa: el cast `date` guarda '2026-01-01 00:00:00', así que `->unique()` de Filament compara contra '2026-01-01' y no encuentra nada. Para unicidad sobre fechas usar una `->rule()` propia con `whereDate()` (ver TarifaForm). Y una columna NOT NULL con default '' necesita `->dehydrateStateUsing(fn (?string $s): string => (string) $s)`, porque Filament manda null cuando el campo queda vacío.
