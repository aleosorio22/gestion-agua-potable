---
paths:
  - 'tests/**'
---

# Tests

## Los helpers de los archivos Pest son globales
Una `function` declarada dentro de un archivo de test de Pest queda en el espacio global y la ven todos los demás archivos. Dos archivos con un helper del mismo nombre revientan la suite entera con «Cannot redeclare», aunque cada uno pase por separado.

Nombrar los helpers por lo que arman y no por su tipo: `boletaDelCliente()`, no `boletaDe()`. Ya chocaron `boletaDe()` entre PagosPanelTest y DashboardTest.

Y correr la suite completa antes de dar por terminado un archivo nuevo: el choque no aparece corriendo solo ese archivo.
