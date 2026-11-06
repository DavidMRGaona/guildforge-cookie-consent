---
id: cookie-consent.panel.banner-settings
title: Ajustes del banner de cookies
summary: Elige dónde y cómo sale el aviso de cookies, sus textos, cuánto dura el consentimiento y la conexión con Google.
category: site-settings
audience: panel
order: 64
keywords: [banner de cookies, aviso de cookies, ajustes, configuración, posición, diseño, tema, colores, textos, botones, aceptar todas, rechazar todas, configurar, política de privacidad, validez, caducidad, días, volver a preguntar, google tag manager, gtm, consent mode, google analytics]
related: [cookie-consent.panel.overview, cookie-consent.panel.categories, cookie-consent.panel.scripts, core.panel.legal-pages]
conditions:
  permissions:
    all: [settings.manage]
---
Los ajustes deciden cómo se ve el aviso de cookies, qué dice y cómo se comporta.

## Abrir los ajustes

En el menú lateral, abre **Configuración** y elige **Banner de cookies**. La página se llama **Ajustes del banner de cookies**. Al terminar, pulsa **Guardar**.

Los cambios se ven en la siguiente página que cargue cada visitante. Guardar los ajustes no vuelve a mostrar el aviso a quien ya eligió, salvo que acortes los **Días de validez del consentimiento** y su elección quede fuera del plazo.

## Apariencia

- **Posición del banner**: **Inferior**, **Superior**, **Inferior izquierda**, **Inferior derecha** o **Centro**, que lo pone abajo y centrado.
- **Diseño del banner**: **Barra**, **Caja** (con las esquinas redondeadas) o **Modal**, que lo muestra en el centro y oscurece la página. Con **Modal** no cuenta la posición.
- **Tema del banner**: **Claro** y **Oscuro** usan los colores de la web y siguen el modo claro u oscuro de cada visitante, así que ahora mismo se ven igual. Con **Personalizado** eliges el **Color primario** (botón de aceptar), el **Color secundario** (los demás botones), el **Color de fondo** y el **Color de texto**.

> [!TIP]
> Con el tema **Personalizado**, el texto de los botones siempre es blanco: elige un color primario y uno secundario oscuros para que se lea bien.

## Textos

- **Título del banner** y **Descripción del banner**: lo que lee el visitante.
- **Texto "Aceptar todas"**, **Texto "Rechazar todas"**, **Texto "Configurar"** y **Texto "Guardar preferencias"**: los nombres de los botones. El de **Configurar** es también el título de la ventana de preferencias.
- **Texto del enlace a la política de privacidad** y **URL de la política de privacidad**: una página de este sitio que empiece por `/`, como `/politica-de-privacidad`, o una dirección completa que empiece por `https://`. Si dejas la dirección vacía, no sale el enlace.

El resto de textos de la ventana de preferencias, como **Obligatoria** o **Ver cookies**, no se cambian aquí.

## Comportamiento

- **Mostrar botón "Rechazar todas"** y **Mostrar botón "Configurar"**: si los desactivas, desaparecen del aviso.
- **Días de validez del consentimiento**: de 1 a 365. Pasado ese plazo, el aviso vuelve a salir.
- **Volver a solicitar consentimiento al cambiar configuración**: con él activado, el aviso vuelve a salir a todos los visitantes cuando se crean o editan categorías, o se crean, editan o borran cookies y scripts.

> [!WARNING]
> Si desactivas los dos botones, el visitante solo puede pulsar **Aceptar todas**.

## Integraciones

- **Habilitar Google Tag Manager** y **ID del contenedor GTM** (como `GTM-XXXXXX`): carga tu contenedor de Google Tag Manager en todas las páginas.
- **Habilitar Google Consent Mode v2**: dice a Google qué ha aceptado cada visitante. Hasta que elige, todo queda denegado salvo lo de seguridad. Después, se conceden las **Claves de Consent Mode** de las categorías que aceptó.

> [!IMPORTANT]
> Google Tag Manager se carga siempre, haya aceptado el visitante o no. Si lo usas, deja activado **Habilitar Google Consent Mode v2** y configura tus etiquetas para que respeten Consent Mode.

[[action:filament.admin.pages.cookie-consent-settings|Abrir los ajustes del banner]]
