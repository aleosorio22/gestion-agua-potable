---
paths:
  - 'lang/**'
---

# Lang

## Traducciones: lang/es cubre lo que Laravel no trae
`app.locale` es `es`. Filament ya trae sus propias traducciones en español dentro de vendor, pero el framework solo incluye `en`: sin `lang/es/validation.php` los errores de validación salen en inglés en pantallas por lo demás en español.

Al agregar reglas de validación nuevas, poner el mensaje en español con `->validationMessages([...])` en el componente cuando el texto genérico no explique bien qué pasó (ver ClienteForm y SerieDocumentoForm).
