# Base de datos — Propuesta de mejora

Cambios sugeridos sobre el esquema descrito en `BASE-DE-DATOS-ACTUAL.md`, con el criterio de: **3FN donde aporta, trazabilidad completa, y las restricciones del negocio garantizadas por el motor y no por buena voluntad del código.**

Nada de esto está implementado. Es para revisar y decidir.

> **Nota de terminología.** Este documento propone renombrar `facturas` a `boletas`. De ahí en adelante se usa "boleta" para el documento de cobro que emite la oficina, y "factura" solo para el documento tributario certificado ante SAT. Son cosas distintas y conviene no mezclarlas ni en el código ni en la conversación.

---

## Principios que guían la propuesta

Se trata de una **entidad pública que cobra dinero a ciudadanos**. Eso impone tres reglas:

**1. Los documentos de cobro son inmutables.** Una boleta emitida y un pago recibido no se editan ni se borran nunca. Si hay un error, se registra un hecho nuevo que lo corrige — una anulación, un reverso — y ambos quedan en el historial.

**2. Todo cambio tiene autor, fecha y motivo.** Aplica especialmente a usuarios, roles y permisos, que es donde se concentra el riesgo de abuso.

**3. El motor impone lo que puede imponer.** Una regla que solo vive en el código PHP se rompe el día que alguien corre un `UPDATE` desde phpMyAdmin. Lo que se puede expresar como `UNIQUE`, `CHECK` o `FOREIGN KEY`, se expresa ahí.

Un cuarto principio, práctico: **no construir lo que todavía no se usa.**

---

## Sobre `users.cliente_id`

**No es una violación de 3FN.** Es una llave foránea opcional en una relación uno-a-cero-o-uno. La objeción es de diseño.

El problema real: **`users` sirve a dos poblaciones distintas** — personal interno y ciudadanos del portal — con ciclos de vida y políticas completamente diferentes. De ahí tres síntomas:

- **Es semánticamente condicional.** Solo tiene sentido con el rol Cliente, y nada en el esquema lo impide.
- **Registra el resultado, no el hecho.** "¿Desde cuándo tiene acceso y quién se lo dio?" no se puede responder.
- **Asume uno a uno para siempre.** No admite que la esposa también consulte, ni un acceso por predio.

**Recomendación: quitarla ahora.** El portal es Could-have (requerimiento línea 59), no hay panel de cliente ni rutas, y **nada lee la columna**. Cuando llegue el portal, la forma correcta es una tabla puente:

```
cliente_accesos
  cliente_id     FK → clientes   UNIQUE (por ahora)
  user_id        FK → users      UNIQUE
  otorgado_por   FK → users
  otorgado_en    timestamp
  revocado_por   FK → users      nullable
  revocado_en    timestamp       nullable
```

---

# P0 — Antes de construir los Resources

## 1. `periodos` como entidad propia

Hoy `periodo` es un `varchar(7)` suelto en `lecturas` y `facturas`. El motor acepta `2026-8`, `202608` o `agosto`.

```
periodos
  id
  anio           smallint
  mes            tinyint
  fecha_inicio   date
  fecha_fin      date
  cerrado_en     timestamp  nullable
  cerrado_por    FK → users nullable
  UNIQUE (anio, mes)
```

Elimina el formato libre, elimina la cadena repetida en dos tablas, y sobre todo **permite cerrar un período**. Una vez cerrado, no entran lecturas nuevas ni se modifican las existentes. En una entidad pública eso es la diferencia entre un mes liquidado y un mes que cualquiera puede seguir tocando.

---

## 2. `tarifas.vigente_hasta` — el problema y cómo conservar el histórico

### El modelo actual y sus dos fallos

Hoy cada tarifa declara su rango explícito. Cuando entra una nueva, hay que **cerrar la anterior**:

| id | paja_id | monto_base | vigente_desde | vigente_hasta |
|---|---|---|---|---|
| 1 | 1 | 50.00 | 2025-01-01 | 2025-12-31 |
| 2 | 1 | 60.00 | 2026-01-01 | *NULL* |

**Fallo 1 — Solapamiento.** Alguien registra la de 2026 pero olvida cerrar la de 2025:

| id | monto_base | vigente_desde | vigente_hasta | |
|---|---|---|---|---|
| 1 | 50.00 | 2025-01-01 | *NULL* | ← quedó abierta |
| 2 | 60.00 | 2026-01-01 | *NULL* | ← también abierta |

Al facturar el 15 de marzo de 2026, el scope `vigenteEn()` devuelve **las dos filas**. El código hace `->first()` y toma la que MySQL entregue primero, normalmente la vieja. Resultado: **cobras Q50 cuando lo aprobado era Q60**, sin error, sin log, sin que nadie se entere.

**Fallo 2 — Hueco.** Alguien cierra con la fecha equivocada:

| id | monto_base | vigente_desde | vigente_hasta |
|---|---|---|---|
| 1 | 50.00 | 2025-01-01 | 2025-11-30 |
| 2 | 60.00 | 2026-01-01 | *NULL* |

**Diciembre de 2025 no tiene tarifa aplicable.** La consulta devuelve cero filas: o revienta, o genera la boleta en Q0.00.

### Por qué MySQL no puede impedirlo

Haría falta un índice único parcial (`UNIQUE WHERE vigente_hasta IS NULL`) o una restricción de exclusión de rangos. PostgreSQL tiene ambas (`EXCLUDE USING gist`); **MySQL no tiene ninguna**.

Y el truco obvio no sirve: `UNIQUE(paja_id, vigente_hasta)` falla porque **MySQL considera que dos `NULL` son distintos entre sí**. Las dos filas abiertas del Fallo 1 pasarían ese índice sin problema.

### El modelo propuesto

```
tarifas
  id | paja_id | monto_base | precio_m3_excedente | vigente_desde
  UNIQUE (paja_id, vigente_desde)
```

| id | paja_id | monto_base | vigente_desde |
|---|---|---|---|
| 1 | 1 | 50.00 | 2025-01-01 |
| 2 | 1 | 60.00 | 2026-01-01 |

Se lee: **"rige desde esta fecha, hasta que empiece la siguiente."**

```sql
SELECT * FROM tarifas
WHERE paja_id = 1 AND vigente_desde <= '2026-03-15'
ORDER BY vigente_desde DESC
LIMIT 1;
```

*"De todas las que ya habían empezado, dame la más reciente."* Para marzo 2026 → Q60. Para marzo 2025 → Q50.

**Sin solapamiento**, porque "la más reciente" es siempre exactamente una: `ORDER BY ... LIMIT 1` devuelve una fila o ninguna, nunca dos. **Sin huecos**, porque toda fecha posterior a la primera tarifa tiene alguna que ya había empezado. El único caso sin tarifa es una fecha anterior a la primera registrada, que es una situación real y honesta.

**No hay estado inválido que evitar, porque no se puede escribir.**

### Tu pregunta: ¿y el histórico de vigencias?

Es la objeción correcta, y tiene tres respuestas que se complementan.

#### a) El rango se deriva, no se pierde

El `vigente_hasta` de una tarifa es *el `vigente_desde` de la siguiente, menos un día*. MySQL 8 lo calcula con una función de ventana:

```sql
CREATE VIEW tarifas_vigencia AS
SELECT
    t.*,
    DATE_SUB(
        LEAD(t.vigente_desde) OVER (
            PARTITION BY t.paja_id ORDER BY t.vigente_desde
        ),
        INTERVAL 1 DAY
    ) AS vigente_hasta
FROM tarifas t;
```

Consultando `tarifas_vigencia` ves **exactamente la misma tabla que hoy**:

| id | paja_id | monto_base | vigente_desde | vigente_hasta |
|---|---|---|---|---|
| 1 | 1 | 50.00 | 2025-01-01 | 2025-12-31 |
| 2 | 1 | 60.00 | 2026-01-01 | *NULL* |

Misma información en pantalla, mismo reporte, misma exportación a Excel. La diferencia es que **ahora es imposible que ese rango mienta**, porque no es un dato que alguien pudo escribir mal: se calcula del orden real de las tarifas.

En Eloquent se resuelve con un modelo apuntando a la vista, o con un accessor.

#### b) Para auditoría contable, el histórico ya está en la boleta

Esta es la respuesta más fuerte, y es independiente de cómo modeles `tarifas`.

Cada boleta guarda `tarifa_id` **y** el `monto` ya calculado. Es el snapshot. Así que la pregunta que de verdad importa en una entidad pública — *"¿con qué tarifa se le cobró Q60 a este señor en marzo de 2026?"* — se responde mirando la boleta, no reconstruyendo rangos.

Si mañana alguien corrige una tarifa, las boletas ya emitidas **no cambian**. Eso es lo que protege el histórico contable, y funciona igual con o sin `vigente_hasta`.

#### c) Para saber cuándo se registró el cambio, está la auditoría

`vigente_desde` dice *desde cuándo aplica* la tarifa. La tabla `audits` dice *cuándo se metió al sistema y quién la metió*. Son dos preguntas distintas y hoy solo la segunda queda registrada — porque una tarifa con `vigente_desde = 2026-01-01` pudo haberse capturado en diciembre (programada) o en marzo (retroactiva), y eso importa.

Con `Tarifa` ya implementando `Auditable`, esa información existe. Vale la pena mostrarla junto a la vigencia en la pantalla de tarifas.

#### Resumen de la decisión

| Pregunta | Cómo se responde |
|---|---|
| ¿Qué tarifa rige hoy? | `ORDER BY vigente_desde DESC LIMIT 1` |
| ¿Desde y hasta cuándo rigió cada una? | Vista `tarifas_vigencia` |
| ¿Con qué tarifa se cobró esta boleta? | `boletas.tarifa_id` + `boletas.monto` (snapshot) |
| ¿Cuándo y quién registró esta tarifa? | Tabla `audits` |

No se pierde nada. Se deja de guardar una copia que podía contradecir al resto.

### Cómo cambia la operación

El 1 de enero de 2026 sube de Q50 a Q60:

| | Modelo actual | Modelo propuesto |
|---|---|---|
| Operaciones | **Dos**: `UPDATE` de la vieja + `INSERT` de la nueva | **Una**: `INSERT` |
| Necesita transacción | Sí | No hay nada que coordinar |
| Necesita Observer | Sí | No |
| Puede quedar mal | Sí (solapamiento o hueco) | No es representable |

Programar un aumento a futuro sale gratis: insertas con `vigente_desde = 2026-07-01` y entra en vigor sola.

### Contrapartida real

No se puede expresar *"esta paja dejó de tener tarifa y no hay reemplazo"*. En una oficina de agua siempre hay tarifa vigente mientras el servicio exista. Si una paja se descontinúa, lo correcto es marcar la **paja** como inactiva, no dejarla sin tarifa.

---

## 3. Boleta, factura y correlativos configurables

### Son dos documentos distintos, no dos nombres

| | **Boleta de cobro** | **Factura electrónica (FEL)** |
|---|---|---|
| Qué es | Documento interno de la oficina | Documento tributario ante SAT |
| Quién asigna serie y número | **La propia entidad** | **El certificador** |
| Requiere terceros | No | Sí: certificador autorizado |
| Requiere NIT del receptor | No | Sí |
| Anulación | Interna, con motivo | Evento electrónico notificado a SAT |
| En tu producto | Capa gratuita | **Premium** |

**El detalle que importa:** bajo FEL tu sistema **no genera** la serie ni el número. Arma el DTE, lo envía al certificador, y este devuelve serie, número y el **Número de Autorización** (UUID de 36 caracteres). Tu sistema los *almacena*, no los *inventa*. Son dos numeraciones con dueños distintos y no pueden compartir columna.

### Series con formato configurable

Los ejemplos que diste — `BOL009130`, `BOL-2026003131` — se descomponen en piezas:

```
BOL 009130          →  prefijo + número de 6 dígitos
BOL - 2026 003131   →  prefijo + separador + año + número de 6 dígitos
```

```
series_documento
  id
  tipo_documento      varchar(20)   -- 'boleta' | 'recibo_pago'
  prefijo             varchar(10)   -- 'BOL', 'REC'
  separador           varchar(5)    -- '' o '-'
  incluye_anio        boolean       -- intercala el ejercicio
  longitud_numero     tinyint       -- 6 → 009130
  reinicia_cada_anio  boolean
  ejercicio           smallint      -- año en curso de la numeración
  siguiente_numero    int unsigned  -- default 1
  activa              boolean
  UNIQUE (tipo_documento, serie_codigo)
```

| Configuración | Resultado |
|---|---|
| `BOL` · sep `''` · sin año · 6 dígitos | `BOL009130` |
| `BOL` · sep `-` · con año · 6 dígitos | `BOL-2026003131` |
| `REC` · sep `-` · sin año · 5 dígitos | `REC-00412` |

Cada entidad define su nomenclatura desde la UI, sin tocar código.

### El folio se congela al emitir

Este punto es importante y suele pasarse por alto.

```
boletas
  serie_id     FK → series_documento
  ejercicio    smallint
  numero       int unsigned
  folio        varchar(30)      -- 'BOL-2026003131', texto ya armado
  UNIQUE (serie_id, ejercicio, numero)
  UNIQUE (folio)
```

Se guarda **el número como entero** (para ordenar, buscar rangos y garantizar unicidad) **y el folio ya renderizado como texto**.

¿Por qué ambos? Porque si dentro de un año la entidad cambia el formato — pasa de 6 a 7 dígitos, o agrega el año — **las boletas ya emitidas no pueden cambiar de número**. El ciudadano tiene ese papel en la mano. El folio es parte del snapshot del documento, igual que el monto y el consumo.

Si solo guardaras las piezas y renderizaras al mostrar, cambiar la configuración reescribiría retroactivamente el número de miles de boletas ya entregadas. Es exactamente el tipo de cosa que un auditor detecta y no perdona.

### Cómo asignar el número sin duplicados ni huecos

Dentro de la misma transacción que inserta el documento, tomando el siguiente número con bloqueo de fila:

```sql
BEGIN;
  SELECT siguiente_numero, ejercicio FROM series_documento
   WHERE id = ? FOR UPDATE;          -- bloquea la fila

  INSERT INTO boletas (serie_id, ejercicio, numero, folio, ...) VALUES (...);

  UPDATE series_documento
     SET siguiente_numero = siguiente_numero + 1
   WHERE id = ?;
COMMIT;
```

- **Sin duplicados**, aunque dos secretarias emitan a la vez: el `FOR UPDATE` serializa el acceso y el `UNIQUE` es la red de seguridad.
- **Sin huecos**, porque el número se consume solo si la boleta se inserta. Si la transacción falla, el `UPDATE` se revierte con todo lo demás.

Lo que **no** hay que hacer es usar el `id` autoincremental: `AUTO_INCREMENT` deja huecos por diseño — un `INSERT` fallido consume el número igual.

### Correlativo también en los pagos

En una entidad pública que recibe efectivo en ventanilla, **el recibo numerado es el control de caja básico**: es lo que permite cuadrar al final del día y detectar un cobro que entró sin registrarse. La boleta dice cuánto debe; el recibo prueba que pagó.

### La factura fiscal, en su propia tabla

```
documentos_fiscales          -- premium; 0 o 1 por boleta
  boleta_id                  FK → boletas   UNIQUE
  serie                      -- la que devuelve el certificador
  numero
  numero_autorizacion        char(36)  UNIQUE   -- UUID del DTE
  fecha_certificacion        timestamp
  certificador_nit           varchar(20)
  nit_receptor               varchar(20)
  nombre_receptor            varchar(150)
  estado                     -- pendiente | certificada | rechazada | anulada
  xml_ruta                   varchar(500)
  xml_hash_sha256            char(64)
  anulada_en                 timestamp    nullable
  motivo_anulacion           varchar(255) nullable
```

**No la construyan ahora.** Lo único que hay que hacer hoy es *no bloquearla*: al ser tabla aparte con relación 1:1 opcional, se agrega después sin tocar una columna de `boletas`. Los usuarios de la capa gratuita nunca tienen filas ahí.

---

## 4. Predios, direcciones y documentos de respaldo

Aquí está el hueco más grande que tiene el modelo, y lo detectaste tú.

### Qué hay hoy

**Direcciones: prácticamente nada.**

| Dónde | Qué es |
|---|---|
| `clientes.direccion` | `varchar(255)` de texto libre |
| `contadores.ubicacion` | `varchar(255)` de texto libre, nullable |

No hay aldea, ni zona, ni sector, ni número de casa. Todo va en una sola cadena que cada secretaria escribirá a su manera: *"Aldea El Porvenir Z0 casa 1-31"*, *"el porvenir, zona 0, 1-31"*, *"Casa 1-31, Aldea El Porvenir"*. Buscar por aldea o agrupar por sector es imposible, y la Could-have de **rutas de lectura por sector** (requerimiento línea 58) no tiene dónde apoyarse.

**Documentos: colgados de la persona, no de la propiedad.**

`documentos_cliente` tiene `cliente_id` y nada más. Exactamente el problema que planteas: si el cliente A tiene servicio en dos casas, los recibos de luz de ambas cuelgan del mismo cliente sin distinguir cuál respalda cuál.

### El problema de fondo

El modelo confunde **la persona** con **la propiedad servida**. Un cliente con tres predios tiene tres contadores, tres direcciones y tres juegos de documentos de respaldo. Hoy todo eso se aplasta en un cliente con una dirección.

Y hay un segundo efecto: si un medidor se daña y se reemplaza, la propiedad sigue siendo la misma. Con el modelo actual, donde el contador *es* la propiedad, o pierdes el historial o reutilizas una fila que ya no describe el mismo aparato.

### La propuesta

Separar **predio** (la propiedad, permanente, con dirección) de **contador** (el aparato, reemplazable):

```
sectores                        -- para las rutas de lectura
  id
  nombre         varchar(50)    -- 'Aldea El Porvenir', 'Sector 2'
  orden          smallint       -- orden sugerido de recorrido
  activo         boolean

predios
  id
  sector_id      FK → sectores  nullable
  aldea          varchar(100)   -- 'El Porvenir'
  zona           varchar(10)    -- '0'
  calle          varchar(50)    nullable
  numero_casa    varchar(20)    -- '1-31'
  referencia     varchar(255)   nullable  -- 'frente a la tienda de doña Mari'
  latitud        decimal(10,7)  nullable  -- Could-have: geolocalización
  longitud       decimal(10,7)  nullable

contadores
  predio_id      FK → predios   -- el medidor está instalado en un predio
  cliente_id     FK → clientes  -- quién es el titular del servicio
  ...
```

El departamento y el municipio no van en cada predio: para una sola oficina son siempre los mismos, así que van en la tabla `configuracion` (sección 5). Un catálogo completo de los 340 municipios de Guatemala sería exactamente el tipo de cosa que el requerimiento pide evitar.

### Documentos

```
tipos_documento                 -- catálogo, ampliable desde la UI
  id
  codigo         varchar(20)    -- 'recibo_luz', 'escritura', 'dpi', 'contrato'
  nombre         varchar(80)
  respalda_predio boolean       -- si prueba propiedad o solo identidad
  activo         boolean

documentos
  id
  cliente_id     FK → clientes
  predio_id      FK → predios   nullable    -- ← la pieza que faltaba
  tipo_id        FK → tipos_documento
  disco          varchar(20)
  ruta           varchar(500)
  nombre_original varchar(255)
  mime           varchar(100)
  tamano_bytes   int unsigned
  hash_sha256    char(64)
  firmado        boolean
  fecha_firma    date           nullable
  subido_por     FK → users
```

`predio_id` es **nullable a propósito**, porque hay dos clases de documento:

| Tipo | `predio_id` | Ejemplo |
|---|---|---|
| De la persona | `NULL` | DPI, NIT |
| De la propiedad | apunta al predio | Recibo de luz, escritura, contrato de servicio |

Así el caso que describes queda modelado exacto: *el cliente A solicitó servicio para su casa en Aldea El Porvenir, Zona 0, casa 1-31*, y el recibo de luz que respalda **esa** propiedad cuelga de **ese** predio. Si mañana pide servicio para otra casa, es otro predio con su propio documento, sin ambigüedad.

### ¿Vale la pena para el prototipo?

Es la decisión más grande de este documento, así que va con honestidad.

**A favor de hacerlo ahora:** cuesta dos tablas y una llave foránea. No hay Resources construidos ni datos reales, así que el costo es casi cero hoy. Hacerlo después significa migrar direcciones de texto libre a campos estructurados — un trabajo manual, registro por registro, que nadie quiere hacer con la oficina ya operando. Y desbloquea las rutas por sector, que es de las Could-have más vendibles.

**En contra:** son dos entidades más que modelar en las pantallas, con 11 días para el Demo Day.

**Alternativa mínima**, si el tiempo aprieta: no crear `predios`, y en su lugar estructurar la dirección **sobre `contadores`** (agregar `sector_id`, `aldea`, `zona`, `numero_casa`, `referencia`) y agregar `contador_id` a `documentos`. Resuelve tu caso concreto y el de las rutas por sector, pero mantiene la confusión entre aparato y propiedad — que reaparece el día que reemplacen un medidor.

**Mi recomendación:** hacer `predios`. La distinción entre propiedad y aparato es real en el dominio y no desaparece por ignorarla; solo se paga más caro después.

---

## 5. Datos de la entidad y `boletas.estado`

### Falta dónde guardar los datos de la oficina

No hay ninguna tabla con nombre, NIT, dirección, teléfono ni logo de la entidad. La boleta impresa los necesita en el encabezado.

```
configuracion
  clave       varchar(50)  PK    -- 'entidad.nombre', 'entidad.nit',
  valor       text               --  'ubicacion.departamento', ...
  updated_by  FK → users
```

Una tabla clave-valor basta, y además queda auditable. En `.env` no: cambiar el teléfono requeriría acceso al servidor y no quedaría registro de quién lo hizo.

### Reemplazar `boletas.estado` por hechos

```
boletas
  -- se elimina: estado
  anulada_en        timestamp    nullable
  anulada_por       FK → users   nullable
  motivo_anulacion  varchar(255) nullable
```

| Estado | Cómo se obtiene |
|---|---|
| pagada | `SUM(pagos no revertidos) >= monto` |
| pendiente | no anulada, no pagada |
| vencida | pendiente y `fecha_vencimiento < CURDATE()` |
| anulada | `anulada_en IS NOT NULL` |

Hoy `estado = 'vencida'` está almacenado pero depende de comparar una fecha con *hoy*: **miente todos los días hasta que alguien corra el proceso que lo actualiza**. Derivarlo elimina ese proceso y de paso registra quién anuló y por qué, que hoy se pierde por completo.

**Nota sobre `impresa_en`:** hoy es un timestamp único, así que solo registra una impresión. Si sirve como "quedó emitida y ya no se toca", está bien. Si quieren saber cuántas veces se reimprimió y quién, hace falta un log aparte.

---

## 6. Auditar usuarios, roles y permisos

- `User` implementa `Auditable`, con `password` y `remember_token` excluidos.
- `EvidenciaLectura` implementa `Auditable` — hoy es el único modelo del dominio que no lo hace.
- Auditar `Role`, `Permission` y las asignaciones de rol de spatie.
- `config/audit.php`: **`console => true`**. Hoy lo hecho desde comando o seeder no deja rastro.
- `audits.old_values` y `new_values` de `text` a **`json`**.

Para una entidad pública, la bitácora de quién obtuvo qué permiso pesa más que la de los datos operativos.

---

# P1 — Antes de producción

## 7. Reverso de pagos

```
pagos
  revertido_en       timestamp    nullable
  revertido_por      FK → users   nullable
  motivo_reverso     varchar(255) nullable
```

Cheque rechazado, error de digitación, cobro duplicado. El pago original queda intacto y visible; el saldo considera solo los no revertidos. Es el contra-asiento contable de toda la vida.

## 8. `metodos_pago` como catálogo

```
metodos_pago
  id | codigo varchar(20) UNIQUE | nombre varchar(50) | activo boolean
```

El `enum` obliga a una migración para agregar "cheque" o "depósito bancario", que van a aparecer.

*Precisión:* un `enum` **no viola 3FN** — el valor es atómico. El argumento en contra es de mantenimiento y portabilidad. Para máquinas de estado internas el enum está bien; para listas que el negocio va a ampliar, catálogo.

## 9. Encadenar `lecturas.lectura_anterior`

Mantener la columna — el medidor puede reemplazarse o dar la vuelta — pero validar al guardar que coincide con la `lectura_actual` de la lectura previa del mismo contador, salvo cambio de medidor registrado. Complemento: `CHECK (lectura_actual >= lectura_anterior)`.

---

# P2 — Deseable, puede esperar

## 10. Historial de titularidad del predio

Hoy `contadores.cliente_id` se sobrescribe si la propiedad cambia de dueño y se pierde el titular anterior.

```
predio_titulares
  predio_id  FK | cliente_id FK | desde date | hasta date nullable
```

## 11. Renombrar roles

`Secretario` → **Secretaria**, `Operario` → **Lector**. Es el vocabulario del requerimiento y de la junta.

## 12. Código de cliente visible

Un `codigo` corto y estable que el ciudadano pueda citar por teléfono.

## 13. Definir `estado` frente a `deleted_at`

`deleted_at` solo para registros creados por error o duplicados; `estado` para la situación real del negocio — un cliente suspendido por mora sigue existiendo.

---

## Qué NO cambiar

**El snapshot de la boleta.** `periodo`, `consumo_m3` y `cliente_id` duplicados es correcto y deliberado: un documento de cobro conserva los datos con que se emitió. Lo que falta es **proteger el origen** — una lectura ya facturada no debería poder modificarse.

**`lecturas.consumo_m3` como columna generada.** El motor garantiza que nunca se desincronice. No tocar.

**Los `UNIQUE` existentes.** `(contador_id, periodo)` y `lectura_id` son las dos restricciones que impiden cobrar dos veces. Lo mejor que tiene el esquema.

**`decimal` para dinero.** Correcto. Nunca `float`.

**Ausencia de `deleted_at` en boletas y pagos.** Correcto.

---

## Resumen para decidir

| # | Cambio | Prioridad | Esfuerzo |
|---|---|---|---|
| 1 | Tabla `periodos` | P0 | Medio |
| 2 | Quitar `tarifas.vigente_hasta` + vista `tarifas_vigencia` | P0 | Bajo |
| 3a | Renombrar `facturas` → `boletas` | P0 | Bajo |
| 3b | `series_documento` con formato configurable + `folio` congelado | P0 | Medio |
| 3c | Correlativo en `pagos` | P0 | Bajo |
| 4a | **Tablas `sectores` y `predios` (direcciones estructuradas)** | P0 | Medio |
| 4b | **`documentos` ligados a predio + `tipos_documento`** | P0 | Medio |
| 5a | Tabla `configuracion` | P0 | Bajo |
| 5b | `boletas.estado` → hechos | P0 | Medio |
| 6 | Auditar users, roles, permisos | P0 | Bajo |
| — | `users.cliente_id`: quitarlo | P0 | Bajo |
| 7 | Reverso de pagos | P1 | Bajo |
| 8 | Catálogo `metodos_pago` | P1 | Bajo |
| 9 | Encadenar `lectura_anterior` | P1 | Medio |
| 10 | Historial de titularidad | P2 | Alto |
| 11 | Renombrar roles | P2 | Bajo |
| 12 | Código de cliente | P2 | Bajo |
| 13 | `estado` vs `deleted_at` | P2 | Bajo |
| — | `documentos_fiscales` (FEL) | **Premium** | No construir |

**Si solo hubiera tiempo para cuatro:** 2, 4a+4b, 5b y 6. El primero cierra un agujero que produce cobros incorrectos; el segundo resuelve el hueco de direcciones y documentos, que es el que más caro sale migrar después; el tercero elimina un estado que se desincroniza solo; el cuarto es lo que un auditor de entidad pública pide primero.

**Cronograma:** faltan 11 días para el Demo Day y no existe ningún Resource de Filament. Los P0 conviene hacerlos ahora porque tocan tablas sobre las que se van a construir las pantallas. Los P1 y P2 pueden ir después sin costo de retrabajo, salvo el 7 y el hash de archivos, que son baratos y se agradecen antes de manejar dinero real.
