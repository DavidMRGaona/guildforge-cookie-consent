# GuildForge - Módulo de consentimiento de cookies

Gestión del consentimiento de cookies conforme al RGPD para GuildForge: banner configurable, categorías de cookies, inyección de scripts según el consentimiento del visitante y soporte para Google Consent Mode v2 y Google Tag Manager.

## Características

- Banner de cookies configurable (posición, diseño, tema, colores y textos) con modal de preferencias por categoría
- Categorías de cookies con el detalle de cada cookie y los scripts que dependen de ella
- Inyección de scripts en las páginas según las categorías aceptadas
- Google Consent Mode v2 (valores por defecto y actualización al consentir) y Google Tag Manager
- Versionado de la configuración: al crear o editar categorías y al crear, editar o eliminar cookies o scripts se vuelve a pedir el consentimiento (si está activado)
- Registro de consentimientos con identificador anónimo de visitante, usuario autenticado si lo hay e IP con hash
- Panel de administración para categorías, cookies, scripts, consulta de consentimientos y ajustes del banner
- Widget de estadísticas de consentimiento en el escritorio del panel

## Requisitos

- GuildForge core `^3.0`
- PHP >= 8.5, Laravel >= 13, Filament 5

El core rechaza el módulo si no se cumplen (`module.json` → `requires`).

## Instalación

1. Descarga `cookie-consent-X.Y.Z.zip` de la última release de `DavidMRGaona/guildforge-cookie-consent` en GitHub.
2. En el panel, activa el modo mantenimiento (**Configuración del sitio** → pestaña **Mantenimiento**).
3. En **Módulos**, pulsa **Instalar / actualizar** y sube el ZIP.
4. Pulsa **Habilitar** en el módulo: la primera vez ejecuta sus migraciones y seeders, que crean las categorías por defecto y los ajustes iniciales del banner.
5. Desactiva el modo mantenimiento.

Las versiones nuevas se aplican desde **Actualizaciones de módulos** (también con el modo mantenimiento activo) o con `php artisan module:update cookie-consent`. Los ajustes del banner se guardan en la base de datos, así que se conservan al actualizar.

### En desarrollo

Con el repositorio clonado en `src/modules/cookie-consent`:

```bash
php artisan module:discover
php artisan module:enable cookie-consent
php artisan module:build cookie-consent   # compila el banner y el modal (--force para recompilar)
```

En las releases los assets los compila el CI y van dentro del ZIP.

## Uso

### Panel de administración

Una vez habilitado, el grupo **Configuración** del panel muestra:

- **Cookies**: categorías de cookies, con las cookies y los scripts de cada una en su ficha. Las categorías del sistema no se pueden eliminar.
- **Consentimientos**: consulta de los consentimientos registrados (solo lectura), con filtros por método, usuario autenticado y consentimientos de hoy.
- **Banner de cookies**: ajustes del banner (ver [Configuración](#configuración)).

### Widget del escritorio

**Estadísticas de consentimiento** muestra el total de consentimientos y el desglose por método de consentimiento. Solo lo ven los usuarios con el permiso `cookie_consents.view_any`; se activa, desactiva u ordena en **Panel de control**.

### Banner

El banner se inyecta automáticamente al final de cada página (slot `after-footer`) y abre `CookieSettingsModal` para elegir categorías. Se muestra mientras el visitante no haya consentido, cuando el consentimiento caduca o, si `reconsent_on_change` está activo, cuando cambia la versión de la configuración. Las preferencias se guardan en el navegador (`localStorage` y cookie `guildforge_cookie_consent`) y se registran en el servidor.

### Cookies declaradas por otros módulos

Un módulo que usa cookies propias las declara para que aparezcan en la categoría que corresponda del banner. El módulo no tiene por qué requerir `cookie-consent`: la declaración solo se hace si la interfaz existe.

```php
use Modules\CookieConsent\Application\DTOs\DeclaredCookieDTO;
use Modules\CookieConsent\Application\Services\CookieDeclarationRegistryInterface;

if (interface_exists(CookieDeclarationRegistryInterface::class)) {
    $this->callAfterResolving(CookieDeclarationRegistryInterface::class, function (CookieDeclarationRegistryInterface $registry): void {
        $registry->declare(new DeclaredCookieDTO(name: '...', categorySlug: 'preferences', provider: '...', purpose: '...', duration: '90 días'));
    });
}
```

`DeclaredCookieDTO` también acepta `type` (`CookieType`, por defecto `FirstParty`). Declarar dos veces el mismo nombre sustituye la declaración anterior.

- **Cuándo se crean.** `DeclaredCookieSynchronizerInterface::sync()` crea las cookies declaradas que aún no existen. Se ejecuta tras las migraciones y a mano con `php artisan cookie-consent:sync-declared-cookies`.
- **Nunca actualiza filas existentes.** Si ya hay una cookie con ese nombre no se toca, así que los cambios hechos en el panel se conservan. Una cookie declarada en una categoría que no existe se omite y se registra un aviso.
- **Versión de la configuración.** Cada sincronización que crea al menos una cookie sube la versión una sola vez (no una por cookie), de modo que se vuelve a pedir el consentimiento si `reconsent_on_change` está activo. Las sincronizaciones posteriores, sin cookies nuevas, no la modifican.

### Consultar el consentimiento desde el navegador

El banner publica `globalThis.GuildForgeCookieConsent` para que los frontends de otros módulos comprueben el consentimiento por categoría:

```ts
if (globalThis.GuildForgeCookieConsent?.hasConsent('preferences')) {
    // el visitante aceptó las cookies de preferencias
}
```

- `hasConsent(categorySlug)` devuelve `true` para las categorías obligatorias. Para las demás, solo si hay un consentimiento guardado, no ha caducado (`validity_days`), coincide con la versión de la configuración (si `reconsent_on_change` está activo) y la categoría está aceptada.
- Si el objeto global no existe, el módulo no está en la página: trátalo como falta de consentimiento.
- Tras guardar el consentimiento (aceptar todo, rechazar todo o guardar preferencias) el banner dispara el evento `guildforge:cookie-consent-changed` en `window`, sin recargar la página:

```ts
window.addEventListener('guildforge:cookie-consent-changed', () => {
    // vuelve a comprobar hasConsent()
});
```

### Rutas

Rutas web (middleware `web`, con CSRF):

```
GET    /consentimiento-cookies/categorias      # Categorías con sus cookies (JSON)
GET    /consentimiento-cookies/configuracion   # Configuración del banner (JSON)
POST   /consentimiento-cookies/consentir       # Guardar las preferencias de consentimiento
```

El POST requiere:
- `visitor_id` (UUID, obligatorio)
- `preferences` (objeto `slug de categoría => booleano`, obligatorio)
- `config_version` (entero, obligatorio)
- `consent_method` (`banner`, `settings_page` o `api`; opcional, por defecto `banner`)

El banner siempre envía `banner`.

### Middleware

`InjectCookieScripts` se añade al grupo `web` y actúa en las respuestas HTML. Lee la cookie `guildforge_cookie_consent` y la ignora, como el banner, si el consentimiento ha caducado (`consent_validity_days`) o, con `reconsent_on_change` activo, si se dio para otra versión de la configuración. Añade:

- en `<head>`: los valores por defecto de Consent Mode y, si el visitante ya eligió, su actualización con las categorías aceptadas (si Consent Mode está activo), el contenedor de GTM (si está activo y tiene ID) y los scripts de cabecera de las categorías aceptadas;
- al empezar `<body>`: el `noscript` de GTM y los scripts de esa posición de las categorías aceptadas;
- antes de `</body>`: los scripts de esa posición de las categorías aceptadas.

La cookie se escribe en el navegador y el módulo la excluye del cifrado de cookies de Laravel para poder leerla. GTM se carga con independencia del consentimiento; las etiquetas de Google respetan el estado de Consent Mode, que el banner actualiza al consentir y el middleware restaura en cada página.

## Configuración

Los ajustes se editan en **Configuración → Banner de cookies** (permiso `settings.manage`) y se guardan en la tabla `settings` con el prefijo `cookie-consent.`. El seeder crea los valores iniciales al habilitar el módulo; `config/settings.php` solo da el valor que muestra el formulario para un ajuste que nunca se ha guardado.

### Apariencia

| Opción | Descripción | Valor inicial |
|--------|-------------|---------------|
| `banner_position` | Posición del banner | `bottom` |
| `banner_layout` | Diseño del banner | `bar` |
| `banner_theme` | Tema del banner | `light` |
| `primary_color` | Color principal (tema personalizado) | `#D97706` |
| `secondary_color` | Color secundario (tema personalizado) | `#57534E` |
| `background_color` | Color de fondo (tema personalizado) | `#FFFFFF` |
| `text_color` | Color del texto (tema personalizado) | `#1C1917` |

### Textos

| Opción | Descripción | Valor inicial |
|--------|-------------|---------------|
| `banner_title` | Título del banner | `Utilizamos cookies` |
| `banner_description` | Descripción del banner | `Usamos cookies propias y de terceros...` |
| `accept_all_text` | Texto del botón aceptar | `Aceptar todas` |
| `reject_all_text` | Texto del botón rechazar | `Rechazar todas` |
| `settings_text` | Texto del botón configurar | `Configurar` |
| `save_settings_text` | Texto del botón guardar | `Guardar preferencias` |
| `privacy_policy_link_text` | Texto del enlace de privacidad | `Política de privacidad` |
| `privacy_policy_url` | Ruta del sitio (`/...`) o dirección `http(s)` de la política de privacidad | `/politica-de-privacidad` |

### Comportamiento

| Opción | Descripción | Valor inicial |
|--------|-------------|---------------|
| `show_reject_all` | Mostrar el botón de rechazar todas | `true` |
| `show_settings_button` | Mostrar el botón de configurar | `true` |
| `consent_validity_days` | Días de validez del consentimiento (1-365) | `365` |
| `reconsent_on_change` | Volver a pedir el consentimiento cuando cambia la configuración | `true` |

### Integraciones

| Opción | Descripción | Valor inicial |
|--------|-------------|---------------|
| `gtm_enabled` | Habilitar Google Tag Manager | `false` |
| `gtm_container_id` | ID del contenedor GTM | vacío |
| `consent_mode_enabled` | Habilitar Google Consent Mode v2 | `true` |

`config_version` no se edita: sube sola al crear o editar categorías y al crear, editar o eliminar cookies o scripts.

## Enums de referencia

### BannerPosition

| Valor | Descripción |
|-------|-------------|
| `bottom` | Inferior |
| `top` | Superior |
| `bottom_left` | Inferior izquierda |
| `bottom_right` | Inferior derecha |
| `center` | Centro |

### BannerLayout

| Valor | Descripción |
|-------|-------------|
| `bar` | Barra |
| `box` | Caja |
| `modal` | Modal |

### BannerTheme

| Valor | Descripción |
|-------|-------------|
| `light` | Claro |
| `dark` | Oscuro |
| `custom` | Personalizado |

### CookieType

| Valor | Descripción |
|-------|-------------|
| `first_party` | Primera parte |
| `third_party` | Tercera parte |

### ConsentMethod

| Valor | Descripción |
|-------|-------------|
| `banner` | Banner |
| `settings_page` | Página de ajustes |
| `api` | API |

### ConsentModeKey (Google Consent Mode v2)

| Valor | Descripción |
|-------|-------------|
| `ad_storage` | Almacenamiento de anuncios |
| `ad_user_data` | Datos de usuario para anuncios |
| `ad_personalization` | Personalización de anuncios |
| `analytics_storage` | Almacenamiento analítico |
| `functionality_storage` | Almacenamiento funcional |
| `personalization_storage` | Almacenamiento de personalización |
| `security_storage` | Almacenamiento de seguridad (concedido por defecto) |

## Permisos

| Permiso | Descripción | Roles por defecto |
|---------|-------------|-------------------|
| `cookie_categories.view_any` | Ver categorías de cookies | `editor` |
| `cookie_categories.create` | Crear categorías de cookies | — |
| `cookie_categories.update` | Editar categorías de cookies | — |
| `cookie_categories.delete` | Eliminar categorías de cookies | — |
| `cookie_consents.view_any` | Ver consentimientos y el widget de estadísticas | — |
| `cookie_consents.export` | Reservado para exportar consentimientos (sin acción en el panel todavía) | — |

Los administradores tienen todos los permisos. La página **Banner de cookies** usa el permiso del core `settings.manage`.

## Eventos de dominio

| Evento | Cuándo se dispara |
|--------|-------------------|
| `ConsentGranted` | Al registrar el primer consentimiento de un visitante |
| `ConsentUpdated` | Al registrar un nuevo consentimiento de un visitante que ya tenía uno, aunque no cambie sus preferencias (por ejemplo, al rechazar categorías que había aceptado o al volver a consentir tras un cambio de configuración) |
| `CookieConfigurationChanged` | Al crear o editar categorías y al crear, editar o eliminar cookies o scripts (sube la versión de configuración) |

## Categorías por defecto

El seeder crea estas categorías del sistema:

| Categoría | Slug | Obligatoria | Claves de Consent Mode |
|-----------|------|-------------|------------------------|
| Cookies necesarias | `necessary` | Sí | `security_storage`, `functionality_storage` |
| Cookies de preferencias | `preferences` | No | `personalization_storage` |
| Cookies analíticas | `analytics` | No | `analytics_storage` |
| Cookies de marketing | `marketing` | No | `ad_storage`, `ad_user_data`, `ad_personalization` |

## Estructura

```
cookie-consent/
├── config/
│   ├── module.php              # Configuración del módulo
│   └── settings.php            # Valores por defecto del formulario del banner
├── database/
│   ├── migrations/             # Migraciones
│   └── seeders/                # Categorías por defecto y ajustes iniciales
├── lang/
│   └── es/                     # Traducciones
├── resources/
│   ├── css/                    # Estilos del banner
│   └── js/
│       ├── components/         # CookieBanner, CookieSettingsModal
│       ├── types/              # Tipos TypeScript
│       └── utils/              # consent-manager
├── routes/
│   └── web.php                 # Rutas del banner
├── src/
│   ├── Application/            # DTOs e interfaces de servicios
│   ├── Domain/                 # Entidades, enums, eventos, excepciones, repositorios, objetos de valor
│   ├── Filament/               # Recursos, página de ajustes y widget
│   ├── Http/                   # Controlador, middleware y form request
│   ├── Infrastructure/         # Modelos, repositorios Eloquent y servicios
│   ├── Listeners/              # Listeners de eventos
│   └── Policies/               # Políticas de autorización
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Feature/
└── module.json                 # Manifiesto del módulo
```

## Tests

Desde `src/` (o dentro del contenedor `guildforge_app`):

```bash
vendor/bin/phpunit -c modules/cookie-consent/phpunit.xml
vendor/bin/phpunit -c modules/cookie-consent/phpunit.xml --testsuite=Unit
```

`php artisan test` no ejecuta los tests de los módulos.

## Publicar una versión

1. Sube `version` en `module.json`.
2. Crea y empuja la etiqueta `vX.Y.Z` (debe coincidir con `version`).
3. El workflow `release.yml` ejecuta los tests contra el core (`main`, o la variable `GUILDFORGE_HOST_REF`), compila los assets y publica `cookie-consent-X.Y.Z.zip` con su `.sha256` en la release.

## Licencia

Propietario - GuildForge
