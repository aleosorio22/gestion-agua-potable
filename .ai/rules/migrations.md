---
paths:
  - 'database/migrations/**'
---

# Migrations

## Unicidad condicional: columna generada, no índice a secas
Cuando la regla es «uno vigente a la vez» y la tabla guarda historial, un `unique()` sobre la columna sola deja UNA fila para siempre: se revoca una vez y nunca se puede volver a otorgar. Pasó en `cliente_accesos`.

El patrón correcto es una columna generada que valga 1 mientras rija y NULL cuando no: los índices únicos admiten varios NULL, así que quedan N filas históricas y una sola vigente.

```php
$table->unsignedTinyInteger('vigente')
    ->virtualAs('case when revocado_en is null then 1 else null end');
$table->unique(['cliente_id', 'vigente']);
```

Dos trampas al migrar una tabla existente: SQLite no admite agregar columnas generadas STORED con ALTER TABLE (usar `virtualAs`), y MySQL se niega a soltar un índice único mientras sea el único que sostiene una clave foránea — crear el nuevo índice ANTES de soltar el viejo, con la misma columna a la izquierda.
