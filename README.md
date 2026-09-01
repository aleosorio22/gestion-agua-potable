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
| `PajaSeeder` | Carga las pajas base (1, 1/2 y 1/4) con su equivalencia en m³ |

El orden importa: `AdminUserSeeder` va después de `ShieldSeeder` porque necesita que el rol `super_admin` ya exista.

## Acceso al panel

`App\Models\User` implementa `FilamentUser`. Sin ese contrato Filament responde **403 a todos los usuarios** en cuanto `APP_ENV` deja de ser `local`, así que no se debe quitar.

Quién entra a `/admin` se controla en `config/admin.php`:

- `panel_roles` — roles internos con acceso: Administrador, Secretario, Operario
- El rol `Cliente` queda fuera a propósito: su acceso será el portal de autoservicio

## Permisos

Los permisos los genera Shield a partir de los Resources del panel. Al agregar un Resource nuevo hay que regenerar:

```bash
php artisan shield:generate --all --panel=admin
```

Eso crea las policies en `app/Policies` (son archivos y van commiteados) y sincroniza la tabla `permissions`. En despliegue basta con `php artisan db:seed`, que regenera solo los permisos sin tocar el disco.

## Modelo de datos

```
clientes ──< contadores >── pajas ──< tarifas
    │            │                       │
    │            └──< lecturas ──< evidencias_lectura
    │                    │
    └──< documentos_cliente
                         │
              facturas ──┴── (cliente, lectura, tarifa)
                  │
                  └──< pagos
```

Decisiones que conviene conocer antes de tocar el esquema:

- **`lecturas.consumo_m3` es una columna generada (STORED)**. La calcula la base de datos como `lectura_actual - lectura_anterior`. No está en `$fillable` y no se debe escribir desde Eloquent.
- **`lecturas` tiene único `(contador_id, periodo)`**. Un contador no se lee dos veces en el mismo período, aunque haya doble clic o reintento de red.
- **`facturas.lectura_id` es único**. Una lectura genera como mucho una factura.
- **`clientes` y `contadores` usan borrado lógico**. Un contador dado de baja conserva su código: para reutilizarlo hay que restaurar el registro, no crear uno nuevo.
- **Las tarifas se versionan por fecha**. `vigente_hasta = NULL` significa que es la tarifa vigente. MySQL no soporta índices únicos parciales, así que **nada impide a nivel de esquema que haya dos tarifas abiertas para la misma paja**: esa invariante depende del Service/Observer que todavía está pendiente.
- **Los pagos no se editan ni se borran**. Es regla de negocio y se refuerza en las policies, no en el esquema.
- **Auditoría**: los modelos del dominio implementan `Auditable` (owen-it/laravel-auditing) y escriben en la tabla `audits`.

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

El modelado de datos, los roles y el acceso al panel están terminados. **Todavía no existen Resources de Filament** (`app/Filament/`), así que el panel arranca vacío: la capa de CRUD es el siguiente paso, junto con el Service que calcula el monto de la factura a partir de la tarifa vigente.
