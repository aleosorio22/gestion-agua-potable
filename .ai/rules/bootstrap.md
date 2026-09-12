---
paths:
  - bootstrap/entorno-inicial.php
---

# Bootstrap

## Sin .env la aplicación no llega ni a dibujar el instalador
Laravel necesita APP_KEY para el middleware de cookies, así que un proyecto recién descomprimido sin `.env` responde 500 (`MissingAppKeyException`) antes de poder mostrar `/instalar`. En un servidor con terminal lo resuelve `composer preparar`; en un hosting compartido no hay terminal.

Por eso `bootstrap/app.php` hace `require_once` de `bootstrap/entorno-inicial.php`, que en la primera visita escribe un `.env` mínimo desde `.env.example`: clave propia generada, APP_ENV=production, APP_DEBUG=false y CACHE_STORE=file (la caché en base de datos necesita una tabla que todavía no existe, porque el instalador corre antes que las migraciones). Usa `fopen(..., 'x')` para que dos visitas simultáneas no escriban dos claves distintas, y se salta el entorno `testing` para no pisar la configuración de `phpunit.xml`.

Si se agrega un ajuste nuevo al `.env.example` que el instalador necesite antes de migrar, revisar si también hay que fijarlo acá.
