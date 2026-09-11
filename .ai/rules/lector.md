---
paths:
  - 'app/Filament/Lector/**'
---

# Lector

## El panel del lector se diseña para la calle, no para la oficina
`LectorPanelProvider` (id `lector`, ruta `/lector`) existe por forma, no por seguridad: los permisos de Shield ya alcanzaban para esconderle cosas en /admin. Lo que no se arregla con permisos es la densidad — cuatro grupos y trece pantallas son una virtud frente a una computadora y un estorbo con el celular en una mano y el medidor en la otra.

Reglas del panel:
- Sin barra lateral (`topNavigation`) y la ruta responde en `/` vía `getRoutePath()`: el lector entra y ya está en su trabajo, sin tablero de por medio.
- Tema claro fijo (`darkMode(false)`): se lee con sol de frente.
- Sin Shield, igual que el portal. El acceso lo decide `User::canAccessPanel()` contra `config('lector.panel_roles')`.
- Todo campo numérico que se teclee en campo lleva `->inputMode('decimal')`, o el celular abre el teclado de letras en cada casa.
- Nada de esta pantalla puede enlazar a /admin: el Lector ya no entra ahí. Si hace falta una acción de oficina, se resuelve dentro del panel o el aviso dice «avise a la oficina».
- Los ajustes de tamaño y contraste viven en `resources/views/filament/lector/estilos.blade.php`, inyectados por render hook. Son CSS suelto a propósito: un tema compilado obligaría a recompilar assets para cambiar un tamaño de letra.
