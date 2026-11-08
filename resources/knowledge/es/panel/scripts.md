---
id: cookie-consent.panel.scripts
title: Cargar scripts solo con consentimiento
summary: Añade el código de servicios externos, como estadísticas o publicidad, para que solo se cargue a quien acepta su categoría.
category: site-settings
audience: panel
order: 63
keywords: [scripts, script, código, google analytics, analítica, estadísticas, píxel, pixel, meta pixel, facebook, publicidad, etiqueta, tag, head, body, noscript, cargar script, añadir script, insertar código, seguimiento]
related: [cookie-consent.panel.cookies, cookie-consent.panel.categories, cookie-consent.panel.banner-settings]
conditions:
  permissions:
    all: ['cookie-consent:cookie_categories.update']
---
Un script es el código que te da un servicio externo para ponerlo en la web: unas estadísticas de visitas, un píxel de publicidad, un chat… Si lo añades a una categoría, solo se carga a quien ha aceptado esa categoría.

## Añadir un script

1. En el menú lateral, abre **Sitio web** → **Cookies** y pulsa la categoría que necesita el script: **Cookies analíticas** para unas estadísticas, **Cookies de marketing** para la publicidad.
2. Debajo del formulario, abre la pestaña **Scripts** y pulsa **Crear script**.
3. Escribe el **Nombre**. La **Descripción** son notas internas: el visitante no la ve.
4. En **Orden**, pon su posición entre los scripts de la categoría, y deja activado **Activo**.
5. Pega el código en el campo que pida el servicio:
   - **Script en `<head>`**: en la cabecera de la página. Es lo más habitual.
   - **Script después de `<body>`**: justo al empezar la página.
   - **Script antes de `</body>`**: al final de la página.
6. Pulsa **Crear**.

El código se añade a la página tal cual lo pegas. Pega el fragmento completo que te da el servicio, con sus etiquetas `<script>`: sin ellas, el código saldría como texto en la página. El campo **Contenido `<noscript>`** se guarda, pero de momento no se añade a las páginas.

> [!WARNING]
> Un script se ejecuta en el navegador de cada visitante y puede leer todo lo que hay en la página. Pega solo código de servicios en los que confíes.

## Cuándo se carga

- Solo a quien ha aceptado la categoría del script, y solo si está **Activo**.
- Nunca antes de que el visitante elija en el aviso, ni siquiera en una categoría requerida.
- En cuanto el visitante acepta su categoría: la página se recarga sola para cargarlo. Si después la retira, se recarga otra vez y el script deja de estar.

Para editar o borrar un script, abre el menú **⋯** de su fila. Crear, editar o borrar un script desde su fila cambia la configuración: si está activado **Volver a solicitar consentimiento al cambiar configuración**, el aviso vuelve a salir a todos los visitantes.

> [!TIP]
> Google Tag Manager no se añade como script: se activa en los ajustes del banner.

## Si algo no sale bien

- **El script no se carga**: comprueba que está **Activo**, que estás en una categoría que has aceptado y recarga la página. Un script que acabas de crear o activar solo sale en las páginas que se carguen después.
- **Sale código como texto en la web**: le faltan las etiquetas `<script>`. Edítalo y pega el fragmento completo.

[[action:filament.admin.resources.cookie-categories.index|Ver las categorías de cookies]]
