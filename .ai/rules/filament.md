---
paths:
  - 'app/Providers/Filament/**'
---

# Filament

## Identidad de los paneles y grupos de navegación
El nombre y el logo de los tres paneles salen de `configuracion` vía `IdentidadDeLaEntidad`, y se pasan **como Closure**: un `PanelProvider` se construye en cada arranque, incluso corriendo `migrate` sobre una base vacía o `config:cache` en el despliegue. Consultar la tabla ahí rompe la instalación antes de existir; por eso `IdentidadDeLaEntidad` además verifica `Schema::hasTable()` y atrapa Throwable.

Filament NO fusiona el grupo de navegación de un Resource —que llega como enum `GrupoNavegacion`— con el de un plugin —que llega como texto—, aunque la etiqueta sea idéntica: el menú termina con el mismo grupo repetido. Por eso Shield vive en su propio grupo `Seguridad` en vez de compartir «Administración».

Tampoco funciona `->navigationGroups(GrupoNavegacion::class)`: agrupa por el nombre del case y deja fuera del orden a los Resources. Registrar las etiquetas una por una (`GrupoNavegacion::X->getLabel()`) es lo que funciona.
