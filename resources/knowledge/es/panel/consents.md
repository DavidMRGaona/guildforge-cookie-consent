---
id: cookie-consent.panel.consents
title: Consultar los consentimientos
summary: Mira qué eligió cada visitante en el aviso de cookies, filtra la lista y consulta el resumen del escritorio.
category: site-settings
audience: panel
order: 65
keywords: [consentimientos, consentimiento, registro de consentimientos, quién aceptó, visitantes, preferencias, rgpd, gdpr, prueba, hash ip, identificador de visitante, estadísticas, total consentimientos, exportar, descargar]
related: [cookie-consent.panel.overview, cookie-consent.panel.banner-settings]
conditions:
  permissions:
    all: ['cookie-consent:cookie_consents.view_any']
---
Cada vez que un visitante elige en el aviso de cookies, se guarda un consentimiento: qué categorías aceptó y cuándo. Cada elección es una fila nueva, así que un mismo visitante puede tener varias, por ejemplo cuando el aviso vuelve a salir.

## La lista

En el menú lateral, abre **Configuración** y elige **Consentimientos**. Los más recientes salen primero.

- **ID visitante**: un identificador anónimo que crea el navegador del visitante. Pasa el ratón por encima para verlo entero.
- **Usuario**: la cuenta del visitante, si había iniciado sesión. Si no, sale **Anónimo**.
- **Método**: cómo se dio. El aviso siempre registra **Banner**.
- **Versión config**: la versión de la configuración de cookies cuando eligió. Sube cuando cambian las categorías, las cookies o los scripts.
- **Preferencias**: los identificadores de las categorías que aceptó.
- **Fecha consentimiento**: cuándo eligió.

Las columnas **Expira** y **Hash IP** están ocultas: muéstralas con el botón de columnas de la tabla.

## Filtrar

Con los filtros de la tabla puedes ver solo un **Método**, solo los de **Usuario autenticado** o solo los de **Hoy**.

## Ver un consentimiento

Pulsa **Ver** en su fila. Verás:

- **Información del visitante**: el **ID visitante**, el **Usuario**, el **Hash IP** y el **User Agent**, que dice qué navegador usaba.
- **Consentimiento**: el método, la versión, la fecha, cuándo **Expira** y las **Preferencias**, con ✓ en las categorías aceptadas y ✗ en las rechazadas.

Los consentimientos no se pueden cambiar ni borrar desde el panel. La dirección IP nunca se guarda: el **Hash IP** es una huella que no permite saber cuál era.

## El resumen del escritorio

En el escritorio, el resumen de consentimientos muestra el **Total consentimientos** y cuántos hay de cada método. Solo lo ve quien puede ver los consentimientos.

> [!IMPORTANT]
> Todavía no se pueden exportar los consentimientos: el permiso **Exportar consentimientos** no tiene ninguna acción en el panel.

[[action:filament.admin.resources.cookie-consents.index|Ver los consentimientos]]
