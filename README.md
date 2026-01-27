# GuildForge - Módulo de consentimiento de cookies

Sistema de gestión de consentimiento de cookies compatible con GDPR para la plataforma GuildForge. Incluye soporte para Google Consent Mode v2, banner configurable, inyección dinámica de scripts y panel de administración.

## Características

- Gestión de consentimiento de cookies conforme a GDPR
- Soporte para Google Consent Mode v2 y Google Tag Manager
- Banner de cookies configurable (posición, diseño, tema, colores)
- Gestión de categorías de cookies con detalle de cada cookie
- Inyección dinámica de scripts según el consentimiento del visitante
- Versionado de configuración y re-consentimiento automático ante cambios
- Seguimiento anónimo de visitantes (UUID) con asociación a usuarios autenticados
- Hash de IP para privacidad
- Panel de administración para categorías, cookies, scripts y consulta de consentimientos
- Widget de estadísticas de consentimiento
- Componentes Vue (banner y modal de ajustes)
- API REST para integración

## Requisitos

- PHP >= 8.2
- Laravel >= 12.0
- GuildForge (aplicación principal)

## Instalación

1. Descubre el módulo:

```bash
php artisan module:discover
```

2. Habilita el módulo:

```bash
php artisan module:enable cookie-consent
```

3. Ejecuta las migraciones:

```bash
php artisan module:migrate cookie-consent
```

## Estructura

```
cookie-consent/
├── config/
│   ├── module.php              # Configuración del módulo
│   └── settings.php            # Ajustes por defecto del banner
├── database/
│   ├── migrations/             # Migraciones de base de datos
│   └── seeders/                # Categorías por defecto y ajustes
├── lang/
│   └── es/                     # Traducciones en español
├── resources/js/
│   ├── components/             # Componentes Vue (CookieBanner, CookieSettingsModal)
│   ├── types/                  # Tipos TypeScript
│   └── utils/                  # Utilidades (consent-manager)
├── routes/
│   ├── api.php                 # Rutas API
│   └── web.php                 # Rutas web
├── src/
│   ├── Application/
│   │   ├── DTOs/               # Data Transfer Objects
│   │   └── Services/           # Interfaces de servicios
│   ├── Domain/
│   │   ├── Entities/           # Entidades de dominio
│   │   ├── Enums/              # Enumeraciones
│   │   ├── Events/             # Eventos de dominio
│   │   ├── Exceptions/         # Excepciones de dominio
│   │   ├── Repositories/       # Interfaces de repositorio
│   │   └── ValueObjects/       # Objetos de valor
│   ├── Filament/
│   │   ├── Resources/          # Recursos de Filament
│   │   └── Widgets/            # Widget de estadísticas
│   ├── Http/
│   │   ├── Controllers/        # Controladores API
│   │   ├── Middleware/          # Middleware de inyección de scripts
│   │   └── Requests/           # Form Requests
│   ├── Infrastructure/
│   │   ├── Persistence/        # Modelos y repositorios Eloquent
│   │   └── Services/           # Implementación de servicios
│   ├── Listeners/              # Event listeners
│   └── Policies/               # Políticas de autorización
├── tests/
│   ├── Feature/                # Tests de integración
│   └── Unit/                   # Tests unitarios
└── module.json                 # Manifiesto del módulo
```

## Uso

### Panel de administración

Una vez habilitado, aparecerá un nuevo grupo "Cookies" en el menú lateral del panel de administración con acceso a:

- **Categorías de cookies**: CRUD de categorías con gestión de cookies y scripts asociados (relation managers). Las categorías del sistema no se pueden eliminar.
- **Consentimientos**: Consulta de consentimientos registrados (solo lectura). Incluye filtros por método de consentimiento, usuarios autenticados y fecha.

### Widget del dashboard

- **Estadísticas de consentimiento**: Total de consentimientos y desglose por método (banner, página de ajustes, API).

### API

```
GET    /api/cookie-consent/categories    # Listar categorías con sus cookies
GET    /api/cookie-consent/config        # Obtener configuración del banner
POST   /api/cookie-consent/consent       # Guardar preferencias de consentimiento
```

El endpoint POST requiere:
- `visitor_id` (uuid, obligatorio)
- `preferences` (array, obligatorio)
- `config_version` (integer, obligatorio)
- `consent_method` (string, opcional, por defecto: `banner`)

### Componentes Vue

```vue
<template>
  <CookieBanner />
  <CookieSettingsModal />
</template>
```

### Middleware

El middleware `InjectCookieScripts` inyecta automáticamente los scripts en las respuestas HTML según el consentimiento del visitante. Lee la cookie `guildforge_cookie_consent` y añade scripts en `<head>`, después de `<body>` y antes de `</body>`.

## Configuración

### Apariencia

| Opción | Descripción | Por defecto |
|--------|-------------|-------------|
| `banner_position` | Posición del banner | `bottom` |
| `banner_layout` | Diseño del banner | `bar` |
| `banner_theme` | Tema del banner | `light` |
| `primary_color` | Color principal | `#10B981` |
| `secondary_color` | Color secundario | `#6B7280` |
| `background_color` | Color de fondo | `#FFFFFF` |
| `text_color` | Color del texto | `#1F2937` |
| `show_logo` | Mostrar logotipo | `true` |

### Textos

| Opción | Descripción | Por defecto |
|--------|-------------|-------------|
| `banner_title` | Título del banner | `Utilizamos cookies` |
| `banner_description` | Descripción del banner | `Usamos cookies propias y de terceros...` |
| `accept_all_text` | Texto del botón aceptar | `Aceptar todas` |
| `reject_all_text` | Texto del botón rechazar | `Rechazar todas` |
| `settings_text` | Texto del botón configurar | `Configurar` |
| `save_settings_text` | Texto del botón guardar | `Guardar preferencias` |
| `privacy_policy_link_text` | Texto del enlace de privacidad | `Política de privacidad` |
| `privacy_policy_url` | URL de la política de privacidad | `/politica-de-privacidad` |

### Comportamiento

| Opción | Descripción | Por defecto |
|--------|-------------|-------------|
| `show_reject_all` | Mostrar botón de rechazar todas | `true` |
| `show_settings_button` | Mostrar botón de configurar | `true` |
| `block_page_until_consent` | Bloquear página hasta consentir | `false` |
| `consent_validity_days` | Días de validez del consentimiento | `365` |
| `reconsent_on_change` | Pedir re-consentimiento ante cambios | `true` |

### Integraciones

| Opción | Descripción | Por defecto |
|--------|-------------|-------------|
| `gtm_enabled` | Habilitar Google Tag Manager | `false` |
| `gtm_container_id` | ID del contenedor GTM | `""` |
| `consent_mode_enabled` | Habilitar Google Consent Mode v2 | `true` |
| `config_version` | Versión de configuración (auto-incrementado) | `1` |

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

| Permiso | Descripción |
|---------|-------------|
| `cookie_categories.view_any` | Ver categorías de cookies |
| `cookie_categories.create` | Crear categorías de cookies |
| `cookie_categories.update` | Editar categorías de cookies |
| `cookie_categories.delete` | Eliminar categorías de cookies |
| `cookie_consents.view_any` | Ver consentimientos |
| `cookie_consents.export` | Exportar consentimientos |

## Eventos de dominio

| Evento | Cuándo se dispara |
|--------|-------------------|
| `ConsentGranted` | Al registrar un nuevo consentimiento |
| `ConsentUpdated` | Al modificar las preferencias de consentimiento |
| `ConsentWithdrawn` | Al retirar el consentimiento |
| `CookieConfigurationChanged` | Al modificar categorías, cookies o scripts (incrementa la versión de configuración) |

## Categorías por defecto

El seeder crea las siguientes categorías del sistema:

| Categoría | Slug | Obligatoria | Claves Consent Mode |
|-----------|------|-------------|---------------------|
| Cookies necesarias | `necessary` | Si | `security_storage`, `functionality_storage` |
| Cookies de preferencias | `preferences` | No | `personalization_storage` |
| Cookies analíticas | `analytics` | No | `analytics_storage` |
| Cookies de marketing | `marketing` | No | `ad_storage`, `ad_user_data`, `ad_personalization` |

## Tests

```bash
# Ejecutar todos los tests del módulo
php artisan test modules/cookie-consent/tests

# Solo tests unitarios
php artisan test modules/cookie-consent/tests/Unit

# Solo tests de integración
php artisan test modules/cookie-consent/tests/Feature
```

## Licencia

Propietario - GuildForge
