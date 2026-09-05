# Gestión de Agua Potable

Sistema de gestión para una oficina de agua potable: clientes, contadores, lectura de consumo, tarifas por paja, facturación y pagos. Construido sobre Laravel 13 con Filament 5 como panel administrativo.

## Requisitos

- PHP 8.3 o superior
- Composer 2
- MySQL 8 (o MariaDB equivalente)
- Node.js 20 y npm

## Instalación desde cero

```bash
git clone https://github.com/aleosorio22/gestion-agua-potable.git
cd gestion-agua-potable
```

Crear la base de datos vacía en MySQL:

```sql
CREATE DATABASE aqua_gest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Copiar el entorno y ajustar credenciales:

```bash
cp .env.example .env
```

Editar en `.env` como mínimo `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` y **cambiar `ADMIN_PASSWORD`**.

Instalar y preparar todo con un solo comando:

```bash
composer setup
```

Ese script equivale a:

```bash
composer install
php artisan key:generate
php artisan migrate --force --seed
php artisan storage:link
npm install --ignore-scripts
npm run build
```

Levantar el entorno de desarrollo:

```bash
composer dev
```

El panel queda en `/admin`. Se entra con el `ADMIN_EMAIL` y `ADMIN_PASSWORD` del `.env`.

## Qué siembra `db:seed`

Los seeders corren en este orden y son idempotentes: se pueden repetir sin duplicar nada.

| Seeder | Qué hace |
|---|---|
| `ShieldSeeder` | Genera los permisos de Filament Shield y arma el rol `super_admin` con todos ellos |
| `RoleSeeder` | Crea los roles de negocio definidos en `config/admin.php` |
| `AdminUserSeeder` | Crea el administrador inicial y le asigna `super_admin` |
| `ConfiguracionSeeder` | Datos de la entidad (nombre, NIT, dirección) que salen en la boleta |
| `CatalogosSeeder` | Pajas, métodos de pago y tipos de documento |
| `SerieDocumentoSeeder` | Series correlativas de boletas y recibos |
| `PeriodoSeeder` | Abre el período del mes en curso |

El orden importa: `AdminUserSeeder` va después de `ShieldSeeder` porque necesita que el rol `super_admin` ya exista.

## Acceso al panel

`App\Models\User` implementa `FilamentUser`. Sin ese contrato Filament responde **403 a todos los usuarios** en cuanto `APP_ENV` deja de ser `local`, así que no se debe quitar.

Quién entra a `/admin` se controla en `config/admin.php`:

- `panel_roles` — roles internos con acceso: Administrador, Secretaria, Lector
- El rol `Cliente` queda fuera a propósito: su acceso será el portal de autoservicio

## Permisos

Los permisos los genera Shield a partir de los Resources del panel. Al agregar un Resource nuevo hay que regenerar:

```bash
php artisan shield:generate --all --panel=admin
```

Eso crea las policies en `app/Policies` (son archivos y van commiteados) y sincroniza la tabla `permissions`. En despliegue basta con `php artisan db:seed`, que regenera solo los permisos sin tocar el disco.

## Modelo de datos

```
sectores ──< predios ──< contadores >── clientes
                 │            │              │
                 │            │         pajas┘ └──< tarifas
                 │            │
                 │       lecturas >── periodos
                 │            │
                 │            ├──< evidencias_lectura
                 │            │
                 │        boletas ──< pagos
                 │            │           │
                 │     series_documento ──┘
                 │
                 └──< documentos >── tipos_documento
```

Decisiones que conviene conocer antes de tocar el esquema:

- **Predio y contador son cosas distintas.** El predio es la propiedad (permanente, con dirección estructurada); el contador es el aparato, que se reemplaza. Un cliente puede tener servicio en varios predios.
- **Los documentos con `predio_id` respaldan esa propiedad** (recibo de luz, escritura); los que lo tienen en `NULL` son de la persona (DPI, NIT).
- **`lecturas.consumo_m3` es una columna generada (STORED)**. La calcula la base de datos. No está en `$fillable` y no se debe escribir desde Eloquent.
- **`lecturas` tiene único `(contador_id, periodo_id)`** y `boletas.lectura_id` es único: no se cobra dos veces el mismo consumo.
- **Las tarifas no guardan fecha de fin.** Rigen desde `vigente_desde` hasta que empieza la siguiente; la vigente es la más reciente que ya empezó (`Tarifa::vigenteEn()`). Así no existen solapamientos ni huecos. El rango completo se consulta en la vista `tarifas_vigencia` o con el accessor `$tarifa->vigente_hasta`.
- **Las boletas no tienen columna `estado`.** Pagada, pendiente y vencida se derivan de los pagos y la fecha, así que no se desincronizan. Solo la anulación se almacena, con autor y motivo.
- **Boleta ≠ factura.** La boleta es el documento interno; la factura electrónica (FEL) es tributaria, su serie y número los asigna el certificador, y va en una tabla aparte que aún no existe.
- **Los correlativos son configurables** por entidad en `series_documento` (prefijo, separador, año, dígitos). El folio se congela al emitir: cambiar el formato no reescribe documentos ya entregados.
- **Boletas y pagos son inmutables.** Los observers lo impiden: una boleta se anula, un pago se revierte. Nunca se editan ni se borran.
- **Un período cerrado no admite lecturas nuevas ni cambios.**
- **Auditoría**: todos los modelos del dominio, más `User`, roles y permisos, escriben en `audits`. `audit.console` está activo, así que los cambios por consola también quedan.

## Servicios

La lógica que el esquema no puede imponer vive en `app/Services`:

- **`EmisorBoletas`** — valida período abierto, busca la tarifa vigente, calcula el monto y reserva el correlativo dentro de una transacción.
- **`RegistradorPagos`** — valida saldo y referencia, y emite el recibo numerado.

## Pruebas

```bash
php artisan test --compact
```

Las pruebas corren sobre SQLite en memoria (ver `phpunit.xml`), no sobre MySQL. El esquema está verificado en ambos motores, pero al agregar migraciones conviene comprobar que sigan corriendo en los dos.

## Antes de desplegar a producción

- [ ] `ADMIN_PASSWORD` cambiado por uno real
- [ ] `APP_DEBUG=false` y `APP_ENV=production`
- [ ] `php artisan config:cache` — ojo: a partir de aquí `env()` devuelve `null` fuera de los archivos `config/`, por eso las credenciales del admin se leen vía `config('admin.*')`
- [ ] `php artisan storage:link` (las evidencias y documentos se sirven desde `storage/app/public`)
- [ ] `npm run build`

## Estado del proyecto

El modelado de datos, los roles, el acceso al panel y los servicios de emisión y cobro están terminados y cubiertos por pruebas.

**Todavía no existen Resources de Filament** (`app/Filament/`), así que el panel arranca vacío. Ese es el siguiente paso: las pantallas de Cliente, Predio, Contador, Tarifa, Lectura, Boleta y Pago, más el recibo imprimible.

Pendiente también: la factura electrónica (FEL) como función premium, en la tabla `documentos_fiscales` que aún no se creó — está pensada como tabla aparte con relación 1:1 opcional contra `boletas`, así que agregarla no requerirá tocar el esquema existente.
