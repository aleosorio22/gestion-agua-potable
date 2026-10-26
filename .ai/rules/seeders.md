---
paths:
  - 'database/seeders/**'
---

# Seeders

## firstOrCreate no es idempotente en columnas con cast date
Una columna con cast `date` se guarda como '2026-01-01 00:00:00'. `firstOrCreate(['vigente_desde' => '2026-01-01'])` no vuelve a encontrar la fila en la segunda corrida bajo SQLite y revienta con el UNIQUE; en MySQL el tipo DATE trunca la hora y el bug no se ve, así que pasa desapercibido corriendo el seeder a mano.

Para que un seeder se pueda repetir, comparar con `whereDate(...)->exists()` y luego `create()`. Ya pasó con las tarifas de DatosDePruebaSeeder: la corrida dos fallaba solo en los tests.
