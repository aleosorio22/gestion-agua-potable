---
paths:
  - 'app/Observers/**'
---

# Observers

## Model::observe() descarta la instancia y guarda solo la clase
`Model::observe(new MiObserver('algo'))` NO conserva el objeto: Eloquent resuelve el nombre de la clase y deja que el contenedor la vuelva a construir sin argumentos. Un observer con constructor parametrizado revienta con `BindingResolutionException` dentro de un evento del modelo, y el error se pierde: el proceso de pruebas muere sin salida ni log.

Los observers no llevan estado por constructor. Si hacen falta datos distintos por modelo, que los declare el modelo (ver `Cliente::CLAVE_CORRELATIVO` y `CodigoCorrelativoObserver`).

Para ver errores que `laravel/pao` se traga durante los tests: `PAO_DISABLE=1 vendor/bin/pest`.
