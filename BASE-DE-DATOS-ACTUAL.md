# Base de datos — Estado actual

Documentación del esquema tal como está hoy en MySQL, leída directamente con `SHOW CREATE TABLE`, no de las migraciones. Fecha: 1 de septiembre de 2026, rama `fix/setup-y-esquema-inicial`.

El documento complementario `BASE-DE-DATOS-PROPUESTA.md` contiene las mejoras sugeridas.

---

## Inventario

**15 migraciones, 19 tablas.** Se agrupan en tres bloques:

| Bloque | Tablas |
|---|---|
| **Dominio** (9) | `clientes`, `pajas`, `contadores`, `tarifas`, `lecturas`, `evidencias_lectura`, `facturas`, `pagos`, `documentos_cliente` |
| **Autenticación y permisos** (7) | `users`, `password_reset_tokens`, `sessions`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` |
| **Infraestructura** (3) | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `audits` |

Motor: **InnoDB**, charset `utf8mb4`, collation `utf8mb4_unicode_ci`. Zona horaria de la aplicación: **UTC** (`config/app.php`).

---

## Diagrama de relaciones

```
                    ┌──────────┐
                    │  pajas   │
                    └────┬─────┘
                         │ 1:N
              ┌──────────┴──────────┐
              │                     │
        ┌─────▼──────┐        ┌─────▼─────┐
        │ contadores │        │  tarifas  │
        └─────┬──────┘        └─────┬─────┘
              │ N:1                 │
        ┌─────▼──────┐              │
        │  clientes  │              │
        └─────┬──────┘              │
              │                     │
              │  ┌──────────┐       │
              │  │ lecturas │◄──────┼── contador_id
              │  └────┬─────┘       │
              │       │ 1:N         │
              │  ┌────▼──────────────────┐
              │  │  evidencias_lectura   │
              │  └───────────────────────┘
              │       │ 1:1
        ┌─────▼───────▼─────┐
        │     facturas      │◄── tarifa_id
        └─────────┬─────────┘
                  │ 1:N
            ┌─────▼─────┐
            │   pagos   │
            └───────────┘

        ┌────────────────────┐
        │ documentos_cliente │──► clientes
        └────────────────────┘

        users ──► clientes  (cliente_id, nullable, único)
        users ◄── lecturas.usuario_id, pagos.usuario_id,
                  evidencias_lectura.subido_por,
                  documentos_cliente.subido_por
```

---

## Detalle por tabla

### `clientes`

Titular del servicio. Catálogo con baja lógica.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `id` | bigint unsigned | no | PK autoincremental |
| `nombre` | varchar(150) | no | |
| `nit` | varchar(20) | sí | **único** |
| `dpi` | varchar(20) | sí | **único** |
| `direccion` | varchar(255) | no | |
| `telefono` | varchar(20) | sí | |
| `email` | varchar(150) | sí | sin restricción de unicidad |
| `estado` | enum('activo','inactivo') | no | default `activo` |
| `deleted_at` | timestamp | sí | borrado lógico |
| `created_at` / `updated_at` | timestamp | sí | |

**Índices:** PK(`id`) · UNIQUE(`nit`) · UNIQUE(`dpi`) · KEY(`estado`,`nombre`)

### `pajas`

Catálogo de caudal contratado. Concepto propio de este dominio: una "paja" equivale a un volumen de agua.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `id` | bigint unsigned | no | PK |
| `nombre` | varchar(50) | no | **único** — `1 paja`, `1/2 paja`, `1/4 paja` |
| `equivalencia_m3` | decimal(10,2) | no | editable, ej. 60000.00 |

**Sembrado:** 3 filas por `PajaSeeder`.

### `contadores`

Medidor físico instalado en un predio.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `id` | bigint unsigned | no | PK |
| `cliente_id` | bigint unsigned | no | FK → `clientes` **RESTRICT** |
| `paja_id` | bigint unsigned | no | FK → `pajas` **RESTRICT** |
| `codigo` | varchar(30) | no | **único** |
| `ubicacion` | varchar(255) | sí | |
| `fecha_instalacion` | date | sí | |
| `estado` | enum('activo','inactivo','dañado') | no | default `activo` |
| `deleted_at` | timestamp | sí | borrado lógico |

**Índices:** PK · UNIQUE(`codigo`) · KEY(`paja_id`) · KEY(`cliente_id`,`estado`)

### `tarifas`

Precio vigente por paja, versionado por fecha.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `id` | bigint unsigned | no | PK |
| `paja_id` | bigint unsigned | no | FK → `pajas` RESTRICT |
| `monto_base` | decimal(10,2) | no | cuota fija |
| `precio_m3_excedente` | decimal(10,4) | no | 4 decimales |
| `vigente_desde` | date | no | |
| `vigente_hasta` | date | **sí** | `NULL` = tarifa vigente actual |

**Índices:** PK · KEY(`paja_id`,`vigente_desde`,`vigente_hasta`)

### `lecturas`

Registro de la visita del lector. Núcleo del sistema.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `id` | bigint unsigned | no | PK |
| `contador_id` | bigint unsigned | no | FK → `contadores` RESTRICT |
| `usuario_id` | bigint unsigned | no | FK → `users` RESTRICT — quién leyó |
| `periodo` | varchar(7) | no | formato `2026-08` |
| `lectura_anterior` | decimal(10,2) | no | |
| `lectura_actual` | decimal(10,2) | no | |
| `consumo_m3` | decimal(10,2) | — | **GENERATED ALWAYS AS (lectura_actual - lectura_anterior) STORED** |
| `fecha_lectura` | date | no | |

**Índices:** PK · **UNIQUE(`contador_id`,`periodo`)** · KEY(`usuario_id`) · KEY(`periodo`)

### `evidencias_lectura`

Fotos que respaldan la lectura.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `lectura_id` | bigint unsigned | no | FK → `lecturas` **CASCADE** |
| `archivo_url` | varchar(500) | no | ruta en `storage/app/public` |
| `descripcion` | varchar(255) | sí | |
| `subido_por` | bigint unsigned | no | FK → `users` RESTRICT |

### `facturas`

Documento de cobro emitido. Guarda copia inmutable de los datos con que se calculó.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `id` | bigint unsigned | no | PK |
| `cliente_id` | bigint unsigned | no | FK → `clientes` RESTRICT |
| `lectura_id` | bigint unsigned | no | FK → `lecturas` RESTRICT · **único** |
| `tarifa_id` | bigint unsigned | no | FK → `tarifas` RESTRICT |
| `periodo` | varchar(7) | no | copia de la lectura |
| `consumo_m3` | decimal(10,2) | no | copia de la lectura |
| `monto` | decimal(10,2) | no | ya calculado |
| `fecha_emision` | date | no | |
| `fecha_vencimiento` | date | no | |
| `estado` | enum('pendiente','pagada','vencida','anulada') | no | default `pendiente` |
| `impresa_en` | timestamp | sí | |

**Índices:** PK · **UNIQUE(`lectura_id`)** · KEY(`tarifa_id`) · KEY(`cliente_id`,`periodo`) · KEY(`estado`,`fecha_vencimiento`)

### `pagos`

Cobro recibido en oficina.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `factura_id` | bigint unsigned | no | FK → `facturas` RESTRICT |
| `usuario_id` | bigint unsigned | no | FK → `users` RESTRICT — quién cobró |
| `monto` | decimal(10,2) | no | |
| `fecha_pago` | date | no | |
| `metodo_pago` | enum('efectivo','tarjeta','transferencia') | no | |

**Índices:** PK · KEY(`factura_id`) · KEY(`usuario_id`) · KEY(`fecha_pago`)

Sin `deleted_at`: los pagos no se borran. La regla se refuerza en las policies, no en el esquema.

### `documentos_cliente`

Contratos y confirmaciones escaneadas.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `cliente_id` | bigint unsigned | no | FK → `clientes` **CASCADE** |
| `tipo` | enum('confirmacion','contrato') | no | |
| `archivo_url` | varchar(500) | no | |
| `firmado` | tinyint(1) | no | default 0 |
| `fecha_firma` | date | sí | |
| `subido_por` | bigint unsigned | no | FK → `users` RESTRICT |

### `users`

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `id` | bigint unsigned | no | PK |
| `cliente_id` | bigint unsigned | **sí** | FK → `clientes` RESTRICT · **único** — solo para el rol Cliente |
| `name`, `email`, `password` | varchar | no | `email` único |
| `email_verified_at` | timestamp | sí | sin flujo implementado |
| `remember_token` | varchar(100) | sí | |

Roles y permisos vía `spatie/laravel-permission` (tablas `roles`, `permissions`, `model_has_roles`, `role_has_permissions`). Teams desactivado.

### `audits`

Bitácora de `owen-it/laravel-auditing`.

| Columna | Tipo | Notas |
|---|---|---|
| `user_type` / `user_id` | varchar / bigint | quién hizo el cambio, nullable |
| `event` | varchar | `created`, `updated`, `deleted`, `restored` |
| `auditable_type` / `auditable_id` | morphs | sobre qué registro |
| `old_values` / `new_values` | **text** | JSON serializado como texto |
| `url`, `ip_address`, `user_agent`, `tags` | | contexto de la petición |

---

## Estado de la auditoría

| Modelo | ¿Auditado? |
|---|---|
| `Cliente`, `Contador`, `Paja`, `Tarifa`, `Lectura`, `Factura`, `Pago`, `DocumentoCliente` | ✅ |
| `EvidenciaLectura` | ❌ |
| `User` | ❌ |
| Roles y permisos (spatie) | ❌ |

Configuración relevante en `config/audit.php`:

- `events`: `created`, `updated`, `deleted`, `restored`
- `timestamps: false` — no se auditan cambios de `created_at`/`updated_at`
- **`console: false`** — los cambios hechos desde comandos de consola y seeders **no se registran**
- `strict: false`
- `threshold: 0` — sin límite de registros por modelo

---

## Análisis

### Lo que está bien resuelto

**`lecturas.consumo_m3` como columna generada STORED.** Es la forma correcta de almacenar un dato derivado: la base de datos garantiza que nunca se desincronice, y Eloquent solo la lee. No es una violación de normalización precisamente porque el motor la mantiene.

**`UNIQUE(contador_id, periodo)` en lecturas.** Idempotencia real a nivel de motor: un contador no se lee dos veces en el mismo período aunque haya doble clic o reintento de red.

**`UNIQUE(lectura_id)` en facturas.** Una lectura no se factura dos veces.

**Tipos monetarios correctos.** `decimal(10,2)` para montos y `decimal(10,4)` para el precio unitario. Nunca `float`.

**Políticas `ON DELETE` explícitas.** `RESTRICT` en todo lo contable, `CASCADE` solo en hijos sin vida propia (evidencias, documentos).

**Sin `deleted_at` en `facturas` ni `pagos`.** Correcto: los registros contables no se borran.

**Versionado de tarifas por fecha.** El histórico existe, que es justo lo que pide el requerimiento.

### Denormalizaciones deliberadas

`facturas` guarda `periodo`, `consumo_m3` y `cliente_id`, que son derivables navegando `factura → lectura → contador → cliente`. Formalmente son **dependencias transitivas**, es decir, incumplen 3FN.

**Están justificadas.** Una factura es un documento contable: debe conservar los datos con los que se emitió aunque después se corrija la lectura o el predio cambie de titular. Es el patrón *snapshot*, estándar en sistemas de facturación.

Pero justificada no es lo mismo que gratis: **exigen que el dato origen sea inmutable una vez facturado**, y hoy nada lo impide. Si alguien edita una lectura ya facturada, la factura y la lectura quedan contradiciéndose en silencio.

### Problemas reales

**1. `tarifas.vigente_hasta` es redundante y permite estados inconsistentes.**
El rango de vigencia es derivable: una tarifa vale hasta que empieza la siguiente. Al almacenarlo explícitamente se abren dos fallos que el esquema no puede impedir — **solapamientos** (dos tarifas vigentes para la misma paja a la misma fecha) y **huecos** (una fecha sin tarifa aplicable). MySQL no soporta índices únicos parciales, así que no hay forma de restringirlo a nivel de motor. Hoy depende de un Service/Observer que todavía no existe.

**2. `facturas.estado` mezcla hechos con datos derivados.**
`pagada` se deduce de la suma de pagos. `vencida` se deduce de comparar `fecha_vencimiento` con hoy — y como está almacenado, **requiere un proceso que lo actualice a diario o se desincroniza solo**. Solo `anulada` es un hecho real que hay que registrar, y hoy se guarda sin dejar rastro de quién anuló, cuándo ni por qué.

**3. `periodo` es un `varchar(7)` repetido en dos tablas.**
No hay nada que garantice el formato: `2026-8`, `2026-08` y `202608` son todos aceptables para el motor. Y no existe el concepto de **cerrar un período**, que en una entidad pública es lo que impide que alguien agregue o modifique lecturas de un mes ya liquidado.

**4. `lecturas.lectura_anterior` no está encadenada.**
Nada garantiza que coincida con la `lectura_actual` de la lectura previa del mismo contador. Un error de digitación aquí produce un consumo incorrecto que el sistema acepta sin protestar.

**5. Los pagos no se pueden revertir.**
No hay mecanismo para un cheque rechazado o un error de digitación. Borrar rompe la trazabilidad; editar también. Falta el contra-asiento.

**6. No hay correlativo de documento.**
El único identificador de una factura o un recibo es el `id` autoincremental. Una entidad pública necesita numeración correlativa propia, y el `id` no sirve para eso: no es controlable, no es reiniciable por ejercicio y no debe exponerse.

**7. Huecos de auditoría.**
`User` no se audita: no queda rastro de quién creó, desactivó o cambió la contraseña de un usuario. Los cambios de roles y permisos tampoco. Para una entidad pública eso es exactamente lo que más importa registrar. Además `audit.console = false` significa que lo que se hace por comando o seeder es invisible.

**8. `estado` y `deleted_at` conviven sin criterio definido.**
En `clientes` y `contadores` hay dos formas de decir "este registro ya no está activo" y ninguna documentación de cuál usar cuándo.

**9. `old_values` / `new_values` son `text`, no `json`.**
MySQL 8 tiene tipo `JSON` nativo, que valida el contenido y permite consultarlo. Con `text` no se puede preguntar "todos los cambios donde se modificó el monto".

**10. Los archivos se guardan solo como ruta.**
`archivo_url` es un `varchar(500)` sin hash, tamaño, tipo MIME ni nombre original. No hay forma de demostrar que un contrato escaneado no fue sustituido después.

### Veredicto de normalización

El esquema **cumple 1FN y 2FN sin objeciones**: todos los atributos son atómicos y no hay llaves compuestas con dependencias parciales.

Sobre **3FN**, hay cinco dependencias transitivas o datos derivados almacenados:

| Caso | ¿Problema? |
|---|---|
| `lecturas.consumo_m3` | **No.** Columna generada, el motor la mantiene |
| `facturas.periodo`, `consumo_m3`, `cliente_id` | Denormalización **justificada** (snapshot contable). Documentar y proteger el origen |
| `facturas.estado` (pagada/vencida) | **Sí.** Derivable y se desincroniza |
| `tarifas.vigente_hasta` | **Sí.** Derivable y permite estados inválidos |
| `lecturas.lectura_anterior` | Justificable (el medidor puede cambiarse o reiniciarse), pero necesita validación |

**Conclusión:** la base es sólida y las decisiones de fondo son correctas. Lo que falta no es rediseñar, sino cerrar los puntos donde el esquema permite estados que el negocio no admite, y completar la trazabilidad que exige una entidad pública.

Las correcciones concretas están en `BASE-DE-DATOS-PROPUESTA.md`.