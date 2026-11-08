---
id: cookie-consent.panel.cookies
title: Añadir las cookies de una categoría
summary: Apunta en cada categoría las cookies que usa la web, para que el visitante sepa qué acepta.
category: site-settings
audience: panel
order: 62
keywords: [cookies, cookie, añadir cookie, crear cookie, editar cookie, borrar cookie, lista de cookies, proveedor, dominio, propósito, finalidad, duración, primera parte, tercera parte, propias, de terceros, activa, ver cookies, cookies de módulos]
related: [cookie-consent.panel.categories, cookie-consent.panel.scripts, cookie-consent.panel.overview]
conditions:
  permissions:
    all: ['cookie-consent:cookie_categories.update']
---
Cada categoría tiene su lista de cookies. El visitante la ve en el aviso, en la ventana de preferencias, al pulsar **Ver cookies**: el nombre de cada cookie, para qué sirve, quién la pone y cuánto dura.

La lista es informativa: no bloquea ni borra cookies. Lo que se carga o no según el consentimiento son los scripts de la categoría (mira la guía de scripts).

## Añadir una cookie

1. En el menú lateral, abre **Configuración** → **Cookies** y pulsa la categoría.
2. Debajo del formulario, en la pestaña **Cookies**, pulsa **Crear cookie**.
3. Rellena los datos:
   - **Nombre**: el nombre exacto de la cookie, como `_ga`. Si cambia el final, termínalo en `*`: `_ga_*` vale para todas las que empiezan por `_ga_`.
   - **Proveedor**: quién la pone, como Google o el propio sitio.
   - **Dominio**: opcional, el dominio que la escribe.
   - **Tipo**: **Primera parte** si la pone esta web, **Tercera parte** si la pone otro servicio.
   - **Propósito**: para qué sirve esta cookie en concreto. Sale debajo de su nombre en el aviso.
   - **Duración**: texto libre, como «Sesión» o «1 año».
   - **Activa**: solo las cookies activas salen en el aviso y se borran cuando el visitante retira su categoría.
4. Pulsa **Crear**.

Para cambiar una cookie, abre el menú **⋯** de su fila y pulsa **Editar**. Para quitarla, pulsa **Borrar** en ese mismo menú y confirma. En una ventana estrecha la tabla oculta **Tipo**, **Proveedor**, **Dominio** y **Duración** para que quepa la fila; se ven al agrandarla.

> [!IMPORTANT]
> Crear, editar o borrar una cookie desde su fila cambia la configuración: si está activado **Volver a solicitar consentimiento al cambiar configuración**, el aviso vuelve a salir a todos los visitantes.

## Qué pasa al retirar el consentimiento

Cuando un visitante rechaza o retira una categoría, se borran de su navegador las cookies declaradas en ella que guarda este sitio, también las que pone un script en tu dominio, como `_ga`. Las de las categorías requeridas nunca se borran. Las que guardan otros sitios no se pueden borrar desde la web.

## Cookies que añaden otros módulos

Algunos módulos instalados usan sus propias cookies y las declaran. Se crean solas en la categoría que les toca: tras instalar o actualizar un módulo y, como mucho, en una hora.

- Si cambias una de estas cookies, tus cambios se quedan: nunca se sobrescriben.
- Si la borras, vuelve a crearse. Para que no salga en el aviso, desactiva **Activa**.
- Si su categoría no existe (por ejemplo, porque le cambiaste el identificador), no se crea.

[[action:filament.admin.resources.cookie-categories.index|Ver las categorías de cookies]]
