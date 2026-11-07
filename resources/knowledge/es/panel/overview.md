---
id: cookie-consent.panel.overview
title: 'Cookies y consentimiento: cómo funcionan'
summary: Qué hace el aviso de cookies, cómo encajan categorías, cookies y scripts, cuándo se vuelve a preguntar y quién puede hacer cada cosa.
category: site-settings
audience: panel
order: 60
featured: true
keywords: [cookies, cookie, aviso de cookies, banner de cookies, consentimiento, rgpd, gdpr, lopd, privacidad, categorías de cookies, scripts, google analytics, google tag manager, consent mode, volver a preguntar, permisos, cómo funciona, introducción, cokies]
related: [cookie-consent.panel.categories, cookie-consent.panel.cookies, cookie-consent.panel.scripts, cookie-consent.panel.banner-settings, cookie-consent.panel.consents, core.panel.legal-pages]
conditions:
  permissions:
    any: ['cookie-consent:cookie_categories.view_any', 'cookie-consent:cookie_consents.view_any', settings.manage]
---
El módulo de cookies pone en la web de {{site_name}} un aviso que pide a cada visitante su consentimiento. Además, solo carga el código de servicios externos (estadísticas, publicidad…) a quien acepta la categoría que lo necesita. Esta guía explica las piezas. Las demás entran en el detalle de cada una.

## Las piezas

| Pieza | Qué es |
|---|---|
| **Categoría** | Un grupo de cookies con un mismo fin, como las analíticas o las de marketing. El visitante acepta o rechaza categorías, no cookies sueltas. Las marcadas como **Requerida** no se pueden rechazar. |
| **Cookie** | La ficha de una cookie: su nombre, quién la pone, para qué sirve y cuánto dura. Es informativa: el visitante la ve en el aviso, al pulsar **Ver cookies**. |
| **Script** | El código de un servicio externo. Solo se añade a las páginas de quien ha aceptado su categoría. |
| **Consentimiento** | El registro de cada elección de un visitante. Se consulta, pero no se puede cambiar ni borrar. |

Al habilitar el módulo se crean cuatro categorías del sistema, que no se pueden borrar: **Cookies necesarias** (requerida), **Cookies de preferencias**, **Cookies analíticas** y **Cookies de marketing**.

## Cómo funciona el aviso

1. El aviso sale en todas las páginas de la web mientras el visitante no ha elegido.
2. El visitante pulsa **Aceptar todas** o **Rechazar todas**, o elige categoría a categoría con **Configurar**. Son los nombres por defecto: se cambian en los ajustes del banner.
3. Su elección queda registrada en **Consentimientos** y se guarda en su navegador. Si no se puede registrar, no se aplica: el aviso sigue abierto y le pide que lo intente de nuevo.
4. Si la elección añade o quita scripts, la página se recarga sola para cargarlos o quitarlos.
5. Puede cambiar o retirar su elección cuando quiera desde **Preferencias de cookies**: el botón redondo con una galleta, abajo a la izquierda, o el enlace del pie de página si lo añades (mira la guía «Ajustes del banner de cookies»).

> [!IMPORTANT]
> La elección vale para un navegador, no para una cuenta.

## Cuándo se vuelve a preguntar

- Cuando pasan los **Días de validez del consentimiento** de los ajustes del banner.
- Si está activado **Volver a solicitar consentimiento al cambiar configuración**, cuando cambia la configuración: al crear o editar una categoría y al crear, editar o borrar una cookie o un script desde su fila. También cuando un módulo instalado añade sus propias cookies.

Cambiar los ajustes del banner, reordenar las categorías o borrar una categoría no vuelve a preguntar.

## Quién hace qué

| Permiso | Qué permite |
|---|---|
| **Ver categorías de cookies** | Ver la lista de categorías |
| **Crear categorías de cookies** | Crear categorías |
| **Editar categorías de cookies** | Editar categorías y añadir, cambiar o borrar sus cookies y sus scripts |
| **Eliminar categorías de cookies** | Borrar las categorías que no son del sistema ni requeridas |
| **Ver consentimientos** | Ver los consentimientos y su resumen en el escritorio |
| **Gestionar configuración del sitio** | Cambiar los ajustes del banner |

Ningún permiso del módulo se concede solo: se dan a cada rol en **Roles**. El permiso **Exportar consentimientos** todavía no tiene ninguna acción en el panel.

## Dónde está cada cosa

En el menú lateral, el grupo **Configuración** tiene **Cookies** (las categorías, con sus cookies y sus scripts), **Consentimientos** y **Banner de cookies**.

[[action:filament.admin.resources.cookie-categories.index|Ver las categorías de cookies|if=cookie-consent:cookie_categories.view_any]]
