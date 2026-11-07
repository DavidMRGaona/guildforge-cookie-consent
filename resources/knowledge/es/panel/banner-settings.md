---
id: cookie-consent.panel.banner-settings
title: Ajustes del banner de cookies
summary: Elige dónde y cómo sale el aviso de cookies, sus textos, cuánto dura el consentimiento y la conexión con Google.
category: site-settings
audience: panel
order: 64
keywords: [banner de cookies, aviso de cookies, preferencias de cookies, botón flotante, enlace del pie, retirar consentimiento, ajustes, configuración, posición, diseño, tema, colores, textos, botones, aceptar todas, rechazar todas, configurar, política de privacidad, validez, caducidad, días, volver a preguntar, google tag manager, gtm, consent mode, google analytics]
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
- **Tema del banner**: **Automático** (el valor por defecto) sigue el modo claro u oscuro de la web de cada visitante. **Claro** y **Oscuro** se ven siempre así, sea cual sea el modo de la web. Con **Personalizado** eliges el **Color primario** (botón de aceptar), el **Color secundario** (los demás botones), el **Color de fondo** y el **Color de texto**.

> [!TIP]
> Con el tema **Personalizado**, el texto de los botones siempre es blanco: elige un color primario y uno secundario oscuros para que se lea bien.

## Textos

- **Título del banner** y **Descripción del banner**: lo que lee el visitante.
- **Texto "Aceptar todas"**, **Texto "Rechazar todas"**, **Texto "Configurar"** y **Texto "Guardar preferencias"**: los nombres de los botones. El de **Configurar** es también el título de la ventana de preferencias.
- **Texto del enlace a la política de privacidad** y **URL de la política de privacidad**: una página de este sitio que empiece por `/`, como `/politica-de-privacidad`, o una dirección completa que empiece por `https://`. Si dejas la dirección vacía, no sale el enlace.

El resto de textos de la ventana de preferencias, como **Obligatoria** o **Ver cookies**, no se cambian aquí.

## Comportamiento

- **Mostrar botón "Rechazar todas"** y **Mostrar botón "Configurar"**: si los desactivas, desaparecen del aviso.
- **Mostrar botón flotante de preferencias**: un botón redondo con una galleta, abajo a la izquierda, que sale una vez elegido y abre las preferencias de cookies. Sigue el modo claro u oscuro de la web, sea cual sea el tema (salvo con **Personalizado**, que usa tus colores). Viene activado.
- **Días de validez del consentimiento**: de 1 a 365. Pasado ese plazo, el aviso vuelve a salir.
- **Volver a solicitar consentimiento al cambiar configuración**: con él activado, el aviso vuelve a salir a todos los visitantes cuando se crean o editan categorías, o se crean, editan o borran cookies y scripts.

> [!WARNING]
> Si desactivas los dos botones, el visitante solo puede pulsar **Aceptar todas**.

## Cambiar la elección desde la web

Cada visitante tiene que poder cambiar o retirar su consentimiento tan fácilmente como lo dio. Para eso están el botón flotante y el enlace **Preferencias de cookies**, que abre la ventana de preferencias sin salir de la página.

Para poner el enlace en el pie de página:

1. En el menú lateral, abre **Navegación**.
2. Crea un elemento con **Ubicación** **Pie de página** y, en **Página**, elige **Preferencias de cookies**.
3. Guarda. Si es el primer enlace del pie, sustituye a los que salen por defecto: añade también los que quieras conservar.

> [!WARNING]
> Si desactivas el botón flotante, deja el enlace en el pie de página: sin ninguno de los dos, quien ya eligió no puede cambiar su elección hasta que caduque.

## Integraciones

- **Habilitar Google Tag Manager** y **ID del contenedor GTM** (como `GTM-XXXXXX`): añade tu contenedor de Google Tag Manager a las páginas.
- **Categoría de Google Tag Manager**: la categoría que tiene que aceptar el visitante para que se cargue el contenedor cuando no usas Consent Mode. Solo se ofrecen las que no son requeridas.
- **Habilitar Google Consent Mode v2**: dice a Google qué ha aceptado cada visitante. Hasta que elige, todo queda denegado salvo lo de seguridad. Después, se conceden las **Claves de Consent Mode** de las categorías que aceptó.

Cuándo se carga el contenedor:

- **Con Consent Mode**: en todas las páginas, desde el principio. Tus etiquetas reciben lo que ha aceptado cada visitante y deben respetarlo; configúralas para que tengan en cuenta Consent Mode.
- **Sin Consent Mode**: solo cuando el visitante acepta la **Categoría de Google Tag Manager**. Se carga en ese momento, sin recargar la página.

> [!IMPORTANT]
> La versión del contenedor para navegadores sin JavaScript no recibe lo que ha aceptado el visitante, así que solo se añade si aceptó la **Categoría de Google Tag Manager**, uses o no Consent Mode.

[[action:filament.admin.pages.cookie-consent-settings|Abrir los ajustes del banner]]
