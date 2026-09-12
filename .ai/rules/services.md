---
paths:
  - app/Services/Instalador.php
  - app/Services/EmpaquetadorDeDistribucion.php
---

# Services

## El instalador y el flag APP_INSTALLED
`/instalar` pone el sistema en marcha en un servidor nuevo: requisitos, base de datos, datos de la oficina y administrador. Se cierra solo cuando `config('app.installed')` es verdadero — dejarlo accesible permitiría reescribir el `.env` y el administrador de una oficina que ya opera.

El flag se lee de **config**, no del `.env` con expresión regular: así el entorno de pruebas puede declararlo (`phpunit.xml` lo pone en true, si no el middleware redirige todas las rutas web y revienta la suite entera) y `config:cache` no lo deja fuera. Al marcar instalado hay que refrescar config en memoria y correr `config:clear`, o el sistema sigue creyéndose sin instalar.

**Los paneles de Filament no pasan por el grupo `web`.** Un middleware global agregado en `bootstrap/app.php` no los cubre: hay que declararlo también en el `->middleware([...])` de cada PanelProvider. Sin eso, `/admin` en un servidor sin instalar manda a `/admin/login` en vez de al asistente.

El instalador no vacía la base: si encuentra tablas, avisa y se detiene. Un sistema que guarda boletas y pagos de vecinos no debe tener a mano un botón que borre todo.

## El paquete sale de git archive, no del directorio de trabajo
`php artisan app:empaquetar` arma el ZIP para hosting compartido desde `git archive HEAD` en una carpeta temporal, no comprimiendo el proyecto local. Así el `.gitignore` se encarga de dejar fuera el `.env`, el `vendor/` de desarrollo y la carpeta privada de documentos, sin tener que enumerarlos.

Consecuencia práctica: lo que está editado pero sin confirmar no viaja en el paquete. El comando avisa antes.

Lo único que se copia del directorio de trabajo es `public/build`, porque no está versionado. Los recursos de Filament sí lo están. Antes de empaquetar hace falta `npm run build`; `loQueFalta()` lo comprueba.

`SOBRA_EN_PRODUCCION` quita lo versionado que no sirve en el servidor de una oficina (pruebas, notas internas, configuración de las herramientas del equipo). Nunca meter ahí `vendor`, `public`, `config`, `bootstrap` ni `.env.example`.
