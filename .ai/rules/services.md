---
paths:
  - app/Services/Instalador.php
---

# Services

## El instalador y el flag APP_INSTALLED
`/instalar` pone el sistema en marcha en un servidor nuevo: requisitos, base de datos, datos de la oficina y administrador. Se cierra solo cuando `config('app.installed')` es verdadero — dejarlo accesible permitiría reescribir el `.env` y el administrador de una oficina que ya opera.

El flag se lee de **config**, no del `.env` con expresión regular: así el entorno de pruebas puede declararlo (`phpunit.xml` lo pone en true, si no el middleware redirige todas las rutas web y revienta la suite entera) y `config:cache` no lo deja fuera. Al marcar instalado hay que refrescar config en memoria y correr `config:clear`, o el sistema sigue creyéndose sin instalar.

**Los paneles de Filament no pasan por el grupo `web`.** Un middleware global agregado en `bootstrap/app.php` no los cubre: hay que declararlo también en el `->middleware([...])` de cada PanelProvider. Sin eso, `/admin` en un servidor sin instalar manda a `/admin/login` en vez de al asistente.

El instalador no vacía la base: si encuentra tablas, avisa y se detiene. Un sistema que guarda boletas y pagos de vecinos no debe tener a mano un botón que borre todo.
