# Gestión de Agua Potable

Sistema de gestión para oficinas municipales y comités de agua potable: padrón de
clientes y predios, lectura de contadores, tarifas por paja, emisión de boletas,
cobro en ventanilla y reportes.

Nació como proyecto universitario para una oficina municipal de Guatemala, y está
publicado para que cualquier comité que lleve el agua en papel pueda usarlo o
adaptarlo. Todo —código, interfaz y mensajes de error— está en español.

Construido sobre **Laravel 13** con **Filament 5**.

---

## Qué incluye

**Tres accesos separados**, cada uno con su propia pantalla de entrada:

| Acceso | Para quién | Qué hace |
|---|---|---|
| `/admin` | Oficina | Todo el sistema: padrón, lecturas, boletas, pagos, reportes y configuración |
| `/lector` | Lectores de campo | Solo su ruta de lectura del período, ordenada por sector, con captura de foto |
| `/portal` | Vecinos | Consulta de sus boletas y su estado de cuenta |

**Módulos**

- **Padrón** — clientes, predios y contadores, con códigos correlativos automáticos
  y alta guiada paso a paso (cliente → predio → contador).
- **Expediente** — documentos escaneados que respaldan la identidad de la persona
  y la propiedad del predio. Se guardan en disco privado, nunca en `public/`.
- **Períodos y lecturas** — un período por mes; la lectura calcula el consumo y
  puede emitir la boleta en el mismo acto.
- **Facturación y cobro** — tarifa vigente por paja, cuota base y excedente,
  correlativos configurables, recibo imprimible y reverso con motivo.
- **Reportes** — 13 reportes en PDF y Excel, agrupados en Cobranza, Operación,
  Padrón y Control.
- **Configuración** — nombre y datos de la oficina, logotipo, formato de papel
  (A4, oficio, carta, rollo térmico de 80 y 58 mm) y nombres de los campos de la
  boleta. La oficina decide cómo se ve su documento sin tocar código.
- **Auditoría** — todo el dominio escribe en `audits`, incluidos los cambios
  hechos por consola.

---

## Instalación

Hay dos caminos. El primero no necesita terminal y es el que conviene en un
hosting compartido; el segundo es para un servidor propio o para desarrollar.

### Opción A — Paquete listo para subir (recomendada)

Pensada para hosting compartido tipo **Hostinger**, cPanel o Plesk, donde no hay
acceso a línea de comandos. El paquete ya trae las dependencias y los recursos
compilados: solo se sube, se descomprime y se abre el navegador.

**[Descargar el paquete más reciente](https://drive.google.com/drive/folders/1HC-iLZIWIePs_2XY4_SaTeH8i1QnEJ19?usp=sharing)**

1. **Cree la base de datos.** Desde el panel del hosting, cree una base vacía y
   un usuario con todos los permisos sobre ella. Anote nombre, usuario y
   contraseña. El servidor suele ser `localhost`.

2. **Suba y descomprima el ZIP.** Dos formas, según lo que permita el hosting:

   - **Apuntando el dominio a `public/`** *(preferible)* — suba el archivo a una
     carpeta fuera de la carpeta pública, descomprímalo ahí y apunte el dominio a
     la subcarpeta `public`.
   - **Descomprimiendo en `public_html`** — si el hosting no deja mover la
     carpeta del dominio. El `.htaccess` que viene en el paquete redirige todo a
     `public/` y bloquea el acceso al `.env`.

   Descomprima siempre desde el administrador de archivos del panel. Subir 16 mil
   archivos por FTP uno por uno casi siempre deja alguno a medias.

3. **Revise los permisos de escritura** (755 o 775, aplicados también a las
   subcarpetas) en `storage` y `bootstrap/cache`.

4. **Abra `https://su-dominio/instalar`** y siga los cinco pasos: bienvenida,
   requisitos del servidor, base de datos, datos de la oficina y cuenta del
   administrador. El asistente crea las tablas, siembra los catálogos y se cierra
   solo al terminar.

No hace falta crear el `.env` ni generar la clave de la aplicación: el sistema lo
hace en la primera visita.

> **Requisitos del servidor:** PHP 8.3 o superior con `pdo_mysql`, `mbstring`,
> `intl`, `zip`, `gd`, `dom`, `curl` y `fileinfo`; MySQL 8 o MariaDB. La pantalla
> de requisitos las revisa una por una y dice para qué sirve cada una.

### Opción B — Desde el código fuente

Para un servidor propio (VPS, EC2) o para desarrollar.

```bash
git clone https://github.com/aleosorio22/gestion-agua-potable.git
cd gestion-agua-potable
composer preparar
```

`composer preparar` instala las dependencias, crea el `.env`, genera la clave de
la aplicación y compila los recursos. No toca la base de datos.

Cree la base vacía —el instalador crea sus propias tablas, pero la base tiene que
existir:

```sql
CREATE DATABASE aqua_gest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Y abra **`/instalar`** en el navegador, igual que en la opción A.

Para levantar el entorno de desarrollo:

```bash
composer dev
```

<details>
<summary>Instalación completa por terminal, sin pasar por el navegador</summary>

Editar `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` y **cambiar `ADMIN_PASSWORD`**
en el `.env` antes de sembrar:

```bash
composer setup
```

Después hay que poner `APP_INSTALLED=true` en el `.env` a mano, o el sistema
seguirá mandando al asistente.

</details>

### Armar el paquete de la opción A

Quien publica una versión nueva genera el ZIP así:

```bash
npm run build
php artisan app:empaquetar
```

Queda en `storage/app/paquetes/`. El paquete no sale del directorio de trabajo
sino de una copia limpia hecha con `git archive`, así que **lo que esté sin
confirmar no viaja** —el comando avisa antes— y nunca se cuelan el `.env` local
ni las dependencias de desarrollo.

---

## Acceso a los paneles

`App\Models\User` implementa `FilamentUser` y resuelve el acceso panel por panel.
Sin ese contrato, Filament responde **403 a todos los usuarios** en cuanto
`APP_ENV` deja de ser `local`, así que no se debe quitar.

Quién entra a dónde se controla en config, no en código:

| Panel | Config | Roles con acceso |
|---|---|---|
| `/admin` | `config/admin.php` → `panel_roles` | Administrador, Secretaria |
| `/lector` | `config/lector.php` → `panel_roles` | Lector, Administrador |
| `/portal` | Acceso individual por cliente | Cliente |

El rol **Lector no entra a `/admin`**: su trabajo es la ruta de lectura y nada
más. Sigue administrándose desde `/admin` como cualquier otro usuario. Los
administradores sí entran al panel del lector, para poder ver lo que ve su
personal en campo.

## Permisos

Los permisos los genera **Filament Shield** a partir de los Resources del panel de
administración. Los otros dos paneles no usan Shield. Al agregar un Resource nuevo
hay que regenerar:

```bash
php artisan shield:generate --all --panel=admin
```

Eso crea las policies en `app/Policies` (son archivos y van commiteados) y
sincroniza la tabla `permissions`. En despliegue basta con `php artisan db:seed`,
que regenera solo los permisos sin tocar el disco.

> ⚠️ `shield:generate --all` **sobrescribe las policies editadas a mano**. Varias
> llevan candados propios (por ejemplo, el bloqueo de `forceDelete`). Revise el
> diff antes de commitear.

## Qué siembra `db:seed`

Los seeders corren en este orden y son idempotentes: se pueden repetir sin
duplicar nada.

| Seeder | Qué hace |
|---|---|
| `ShieldSeeder` | Genera los permisos de Filament Shield y arma el rol `super_admin` con todos ellos |
| `RoleSeeder` | Crea los roles de negocio definidos en `config/admin.php` |
| `AdminUserSeeder` | Crea el administrador inicial y le asigna `super_admin` |
| `ConfiguracionSeeder` | Datos de la entidad (nombre, NIT, dirección) que salen en la boleta |
| `CatalogosSeeder` | Pajas, métodos de pago y tipos de documento |
| `SerieDocumentoSeeder` | Series correlativas de boletas y recibos |
| `PeriodoSeeder` | Abre el período del mes en curso |

El orden importa: `AdminUserSeeder` va después de `ShieldSeeder` porque necesita
que el rol `super_admin` ya exista.

---

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

- **Predio y contador son cosas distintas.** El predio es la propiedad
  (permanente, con dirección estructurada); el contador es el aparato, que se
  reemplaza. Un cliente puede tener servicio en varios predios.
- **Los documentos con `predio_id` respaldan esa propiedad** (recibo de luz,
  escritura); los que lo tienen en `NULL` son de la persona (DPI, NIT).
- **`lecturas.consumo_m3` es una columna generada (STORED)**. La calcula la base
  de datos. No está en `$fillable` y no se debe escribir desde Eloquent.
- **`lecturas` tiene único `(contador_id, periodo_id)`** y `boletas.lectura_id` es
  único: no se cobra dos veces el mismo consumo.
- **Las tarifas no guardan fecha de fin.** Rigen desde `vigente_desde` hasta que
  empieza la siguiente; la vigente es la más reciente que ya empezó
  (`Tarifa::vigenteEn()`). Así no existen solapamientos ni huecos. El rango
  completo se consulta en la vista `tarifas_vigencia` o con el accessor
  `$tarifa->vigente_hasta`.
- **Las boletas no tienen columna `estado`.** Pagada, pendiente y vencida se
  derivan de los pagos y la fecha, así que no se desincronizan. Solo la anulación
  se almacena, con autor y motivo.
- **Boleta ≠ factura.** La boleta es el documento interno; la factura electrónica
  (FEL) es tributaria, su serie y número los asigna el certificador, y va en una
  tabla aparte que aún no existe.
- **Los correlativos son configurables** por entidad en `series_documento`
  (prefijo, separador, año, dígitos). El folio se congela al emitir: cambiar el
  formato no reescribe documentos ya entregados.
- **Boletas y pagos son inmutables.** Los observers lo impiden: una boleta se
  anula, un pago se revierte. Nunca se editan ni se borran.
- **Un período cerrado no admite lecturas nuevas ni cambios.**
- **Auditoría**: todos los modelos del dominio, más `User`, roles y permisos,
  escriben en `audits`. `audit.console` está activo, así que los cambios por
  consola también quedan.

## Catálogos

Las pantallas del grupo **Catálogos** del panel administran los datos maestros:
sectores, pajas, tarifas, métodos de pago, tipos de documento y series de
documento.

Todas siguen la misma regla: **una fila de catálogo que ya respalda documentos no
se borra, se desactiva.** Las claves foráneas ya son `restrictOnDelete`, así que
el motor lo impide de todas formas; el trait `EsCatalogo` traduce ese candado a un
mensaje entendible (`motivoDeUso()` devuelve «1 contador, 2 tarifas») y
`AccionesCatalogo::eliminar()` deshabilita el botón antes de que alguien lo
descubra chocando.

Dos catálogos llevan candados propios, en observers, porque el esquema no puede
expresarlos:

| Regla | Dónde | Por qué |
|---|---|---|
| Una tarifa que ya emitió boletas no se edita ni se borra | `TarifaObserver` | El precio con el que se cobró es parte del expediente. Para subir el precio se registra otra tarifa con nueva fecha de vigencia. Mientras no haya cobrado nada sí se corrige. |
| El formato del folio se congela al emitir el primer documento | `SerieDocumentoObserver` | Cambiarlo produciría dos folios distintos con el mismo número. Lo que sí se puede es desactivar la serie y abrir otra. |
| El correlativo solo lo mueve `reservarNumero()` | `SerieDocumentoObserver` | Fijarlo a mano repite números o deja huecos que nadie puede explicar en una auditoría. Al **crear** la serie sí se elige el número inicial, para arrancar donde quedó el talonario de papel. |
| Solo una serie activa por tipo de documento | `SerieDocumentoObserver` | `SerieDocumento::activaPara()` resuelve con `->first()`: con dos activas el documento saldría con una u otra según el orden del motor. |

Los códigos de `metodos_pago` y `tipos_documento` se fijan al crearlos y ya no
cambian: son el identificador estable que referencian cortes de caja y
expedientes. El nombre visible sí se corrige libremente.

## Servicios

La lógica que el esquema no puede imponer vive en `app/Services`:

| Servicio | Qué resuelve |
|---|---|
| `EmisorBoletas` | Valida período abierto, busca la tarifa vigente, calcula el monto y reserva el correlativo dentro de una transacción |
| `EmisorAutomatico` | Emite la boleta al registrar la lectura, cuando la oficina lo tiene activado |
| `RegistradorPagos` | Valida saldo y referencia, y emite el recibo numerado |
| `ArchivadorDeExpediente` | Guarda los documentos escaneados en disco privado |
| `AccesoAlPortal` | Otorga y revoca el acceso del vecino desde su ficha |
| `Instalador` | Requisitos, base de datos, oficina y administrador del asistente `/instalar` |
| `EmpaquetadorDeDistribucion` | Arma el ZIP instalable de la opción A |

## Pruebas

```bash
php artisan test --compact
```

Las pruebas corren sobre SQLite en memoria (ver `phpunit.xml`), no sobre MySQL. El
esquema está verificado en ambos motores, pero al agregar migraciones conviene
comprobar que sigan corriendo en los dos.

Además de las reglas de negocio, hay pruebas que **renderizan cada pantalla de los
paneles**: listado, alta y edición. Eso es lo que atrapa una columna mal escrita o
una relación que no existe, cosas que las pruebas de reglas no ven porque nunca
renderizan.

> Los helpers declarados dentro de un archivo de Pest quedan en el espacio global
> y los ven todos los demás. Dos archivos con un helper del mismo nombre revientan
> la suite entera. Nómbrelos por lo que arman: `boletaDelCliente()`, no
> `boletaDe()`.

## Antes de desplegar a producción

- [ ] `APP_DEBUG=false` y `APP_ENV=production`
- [ ] `ADMIN_PASSWORD` cambiado por uno real, si sembró por terminal
- [ ] `php artisan config:cache` — ojo: a partir de aquí `env()` devuelve `null`
      fuera de los archivos `config/`, por eso las credenciales del admin se leen
      vía `config('admin.*')`
- [ ] `npm run build`

El paquete de la opción A ya viene con `APP_ENV=production`, `APP_DEBUG=false` y
los recursos compilados. Y no hace falta `storage:link`: los documentos del
expediente se guardan en disco privado y el logotipo va incrustado en los
documentos impresos, así que nada se sirve desde `storage/app/public`.

---

## Colaboradores

| | |
|---|---|
| **René Osorio** | [alejandroosorio022@gmail.com](mailto:alejandroosorio022@gmail.com) |
| **Rudy González** | [Rudygona05@gmail.com](mailto:Rudygona05@gmail.com) |
| **Keven López** | [klopezp40@miumg.edu.gt](mailto:klopezp40@miumg.edu.gt) |
| **Byron Escobar** | [bescobarq@miumg.edu.gt](mailto:bescobarq@miumg.edu.gt) |

## Cómo contribuir

El trabajo se organiza en una rama por módulo, salida siempre de `develop`, y se
integra por Pull Request.

```bash
git checkout develop && git pull
git checkout -b feat/lo-que-sea
```

Antes de abrir el PR:

1. `vendor/bin/pint` — el formato lo decide Pint, no la discusión.
2. `php artisan test --compact` — la suite completa, no solo el archivo que tocó.
3. Mergear `develop` en su rama, para que el PR no llegue con conflictos.

Todo va en español: nombres, comentarios, mensajes de la interfaz, errores y
mensajes de commit. Un comentario explica **por qué**, no qué hace la línea de
abajo.

El proyecto lleva sus decisiones no obvias en [`.ai/rules/`](.ai/rules). Si va a
tocar una carpeta, lea antes la regla que le corresponde: ahí está lo que ya se
intentó y por qué se hizo de otra manera.
