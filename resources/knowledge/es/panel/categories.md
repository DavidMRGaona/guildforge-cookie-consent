---
id: cookie-consent.panel.categories
title: Categorías de cookies
summary: Crea, edita, ordena y borra las categorías que el visitante acepta o rechaza en el aviso de cookies.
category: site-settings
audience: panel
order: 61
keywords: [categorías de cookies, categoría, crear categoría, editar categoría, borrar categoría, requerida, obligatoria, identificador, slug, orden, reordenar, necesarias, preferencias, analíticas, marketing, google consent mode, claves de consent mode, del sistema]
related: [cookie-consent.panel.overview, cookie-consent.panel.cookies, cookie-consent.panel.scripts]
conditions:
  permissions:
    all: ['cookie-consent:cookie_categories.view_any']
---
Las categorías son lo que el visitante acepta o rechaza en el aviso de cookies. Están en el menú lateral, en **Configuración** → **Cookies**. La lista muestra de cada una su **Identificador**, si es **Requerida**, su **Orden** y cuántas **Cookies** y **Scripts** tiene.

## Crear una categoría

Necesitas el permiso **Crear categorías de cookies**.

1. Pulsa **Crear categoría de cookies**.
2. Escribe el **Nombre**, que es lo que ve el visitante. Al salir del campo, el **Identificador** se rellena solo. Solo admite letras, números, guiones y guiones bajos, y no se puede repetir.
3. Escribe la **Descripción**. El visitante la lee debajo del nombre, así que explica para qué sirven estas cookies.
4. Activa **Requerida** si el visitante no debe poder rechazarla.
5. En **Orden**, pon su posición: los números más bajos salen antes.
6. Si usas Google Consent Mode, abre la sección **Google Consent Mode** y marca las **Claves de Consent Mode** que se conceden a Google cuando alguien acepta esta categoría.
7. Pulsa **Crear**.

Se abre la ficha de la categoría, donde añades sus cookies y sus scripts.

> [!WARNING]
> Una categoría **Requerida** ya no puede dejar de serlo ni se puede borrar.

## Editar una categoría

Necesitas el permiso **Editar categorías de cookies**.

1. Pulsa la categoría en la lista, o abre el menú **⋯** de su fila y pulsa **Editar**.
2. Cambia lo que necesites.
3. Pulsa **Guardar cambios**.

Cambiar el **Nombre** no cambia el **Identificador**.

> [!WARNING]
> No cambies a mano el **Identificador** de una categoría en uso. Los módulos, las cookies que declaran y lo que ya eligieron los visitantes reconocen la categoría por él (`necessary`, `preferences`, `analytics` y `marketing` en las del sistema), y con otro dejan de encontrarla. Google Consent Mode no depende del identificador: usa las **Claves de Consent Mode** de cada categoría.

## Cambiar el orden

El aviso muestra las categorías en el orden de la lista.

1. Pulsa **Reordenar registros**.
2. Arrastra las categorías al orden que quieras.
3. Pulsa **Terminar de reordenar registros**.

## Borrar una categoría

Necesitas el permiso **Eliminar categorías de cookies**. Las cuatro categorías del sistema y las requeridas no se pueden borrar.

1. Abre el menú **⋯** de la fila de la categoría y pulsa **Borrar**.
2. Confirma.

> [!WARNING]
> Al borrar una categoría se borran también sus cookies y sus scripts, y no se puede deshacer.

## Si algo no sale bien

- **No veo el botón para crear, editar o borrar**: te falta el permiso de esa acción. Pídelo a quien gestiona los roles.
- **No aparece Borrar en una categoría**: es una de las del sistema o es requerida.

[[action:filament.admin.resources.cookie-categories.index|Ver las categorías de cookies]]
