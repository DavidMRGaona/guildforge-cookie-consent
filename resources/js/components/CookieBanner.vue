<script setup lang="ts">
import { Cookie } from '@lucide/vue';
import { computed, ref, onBeforeUnmount, onMounted, watch } from 'vue';
import {
    notifyCookieConsentChanged,
    registerCookieConsentProvider,
} from '@/contracts/cookieConsent';
import { bannerThemeVariables } from '../utils/banner-theme';
import type { CookieCategory, BannerConfig } from '../types/cookie-consent';
import { choiceNeedsReload } from '../utils/choice-effects';
import {
    getStoredConsent,
    hasConsentFor,
    needsConsent,
    saveConsent,
    updateConsentMode,
} from '../utils/consent-manager';
import { consentModeUpdate } from '../utils/consent-mode';
import { deleteCookiesOfRejected } from '../utils/cookie-cleanup';
import { loadGoogleTagManager } from '../utils/google-tag-manager';
import { reloadPage } from '../utils/page';
import CookieSettingsModal from './CookieSettingsModal.vue';
import { useRoutes } from '@/composables/useRoutes';

interface Props {
    cookieConsent?: {
        categories: CookieCategory[];
        config: BannerConfig;
    };
}

const props = withDefaults(defineProps<Props>(), {
    cookieConsent: () => ({
        categories: [],
        config: {
            position: 'bottom',
            layout: 'bar',
            theme: 'light',
            colors: {
                primary: '#10B981',
                secondary: '#6B7280',
                background: '#FFFFFF',
                text: '#1F2937',
            },
            showRejectAll: true,
            showSettingsButton: true,
            showFloatingButton: true,
            validityDays: 365,
            reconsentOnChange: true,
            configVersion: 1,
            preferencesUrl: '/consentimiento-cookies/preferencias',
            texts: {
                title: 'Utilizamos cookies',
                description:
                    'Usamos cookies propias y de terceros para mejorar tu experiencia y mostrar contenido personalizado.',
                acceptAll: 'Aceptar todas',
                rejectAll: 'Rechazar todas',
                settings: 'Configurar',
                save: 'Guardar preferencias',
                privacyLink: 'Política de privacidad',
                privacyUrl: useRoutes().legal.privacy,
                required: 'Obligatoria',
                showCookies: 'Ver cookies',
                cookieColumn: 'Cookie',
                providerColumn: 'Proveedor',
                durationColumn: 'Duración',
                cancel: 'Cancelar',
                close: 'Cerrar',
                preferences: 'Preferencias de cookies',
                saveError:
                    'No hemos podido guardar tu elección. Vuelve a intentarlo y, si sigue fallando, recarga la página.',
            },
            gtmEnabled: false,
            gtmContainerId: '',
            gtmCategory: 'analytics',
            consentModeEnabled: true,
            consentModeDefaults: {},
        },
    }),
});

const isVisible = ref(false);
const showSettingsModal = ref(false);
// Set when the server did not record the last choice, which was then not applied either
const saveError = ref<string | null>(null);
const saving = ref(false);

const categories = computed(() => props.cookieConsent?.categories ?? []);
const config = computed(() => props.cookieConsent?.config);

// Check consent synchronously on setup to prevent flash
const shouldShow =
    config.value &&
    needsConsent(
        config.value.configVersion,
        config.value.validityDays,
        config.value.reconsentOnChange
    );

// Initialize visibility based on stored consent
isVisible.value = shouldShow;

// The choice this page runs with: the server injected the scripts of these categories.
// It changes only when a new choice is applied without reloading.
let pagePreferences: Record<string, boolean> =
    config.value && !shouldShow ? { ...(getStoredConsent()?.preferences ?? {}) } : {};

// The link to the preferences (ConsentApiController::PREFERENCES_ANCHOR) lands on this anchor
const PREFERENCES_ANCHOR = '#preferencias-cookies';

/**
 * A link to the preferences (the footer one, or any other) opens them without leaving
 * the page. Captured before Inertia's links see the click.
 */
function openPreferencesFromLink(event: MouseEvent): void {
    const current = config.value;
    if (!current || event.button !== 0) return;
    // A new tab or window follows the link, and the page it opens shows the preferences
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const link = event.target instanceof Element ? event.target.closest('a') : null;
    if (!link || link.href === '') return;

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || url.pathname !== current.preferencesUrl) return;

    event.preventDefault();
    event.stopPropagation();
    openSettings();
}

onMounted(() => {
    // Re-check after mount in case config was not available initially
    if (config.value) {
        isVisible.value = needsConsent(
            config.value.configVersion,
            config.value.validityDays,
            config.value.reconsentOnChange
        );
    }

    document.addEventListener('click', openPreferencesFromLink, true);

    if (window.location.hash === PREFERENCES_ANCHOR) {
        // Drop the anchor, so reloading or sharing the address does not open them again
        window.history.replaceState(
            window.history.state,
            '',
            window.location.pathname + window.location.search
        );
        openSettings();
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', openPreferencesFromLink, true);
});

// Answer the core's cookie consent contract, which the other modules ask per category
registerCookieConsentProvider({
    hasConsent: (categorySlug: string): boolean => {
        const current = config.value;
        if (!current) return false;

        // A category the banner does not offer was never asked about: an older choice no longer counts
        if (!categories.value.some((category) => category.slug === categorySlug)) return false;

        return hasConsentFor(
            categorySlug,
            current.configVersion,
            current.validityDays,
            current.reconsentOnChange,
            categories.value
                .filter((category) => category.isRequired)
                .map((category) => category.slug)
        );
    },
});
// The answer depends on the configuration and the categories too
watch([config, categories], notifyCookieConsentChanged);

const positionClasses = computed(() => {
    if (!config.value) return '';

    const { position, layout } = config.value;

    if (layout === 'modal') {
        return 'fixed inset-0 z-50 flex items-center justify-center bg-black/50';
    }

    const base = 'fixed z-50';

    switch (position) {
        case 'top':
            return `${base} top-0 left-0 right-0`;
        case 'bottom_left':
            return `${base} bottom-4 left-4 max-w-md`;
        case 'bottom_right':
            return `${base} bottom-4 right-4 max-w-md`;
        case 'center':
            return `${base} bottom-4 left-1/2 -translate-x-1/2 max-w-lg`;
        case 'bottom':
        default:
            return `${base} bottom-0 left-0 right-0`;
    }
});

const bannerClasses = computed(() => {
    if (!config.value) return '';

    const { theme, layout } = config.value;
    const base = 'shadow-lg';

    const themeClasses = theme === 'custom' ? '' : 'bg-surface text-base-primary';

    const layoutClasses = layout === 'box' ? 'rounded-lg' : '';

    return `${base} ${themeClasses} ${layoutClasses}`;
});

const bannerStyles = computed(() => {
    if (!config.value) return {};

    // «Claro» and «Oscuro» set their own colour tokens, so they look the same in any site mode
    if (config.value.theme !== 'custom') return bannerThemeVariables(config.value.theme);

    return {
        backgroundColor: config.value.colors.background,
        color: config.value.colors.text,
    };
});

// Every theme, custom included: keyboard users must see which button is selected
const focusRingClasses =
    'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 focus-visible:ring-offset-surface';

// It stays on the page over any content, so it follows the site mode (never the fixed colours of
// «Claro» or «Oscuro») and stands out from the page with an elevated surface and a border that
// reaches 3:1 on the page in both modes (`border-strong` is a 1.4:1 hairline in light mode).
// Only «Personalizado» keeps the panel's colours.
const floatingButtonClasses = computed(() => {
    if (!config.value) return '';

    const themeClasses =
        config.value.theme === 'custom'
            ? ''
            : 'bg-elevated text-base-primary border border-base-muted';

    return `fixed bottom-4 left-4 z-40 rounded-full p-3 shadow-lg transition-opacity hover:opacity-90 ${themeClasses} ${focusRingClasses}`;
});

const floatingButtonStyles = computed(() =>
    config.value?.theme === 'custom' ? bannerStyles.value : {}
);

const primaryButtonClasses = computed(() => {
    if (!config.value) return '';

    if (config.value.theme === 'custom') {
        return `text-white font-medium py-2 px-4 rounded-md transition-opacity hover:opacity-90 ${focusRingClasses}`;
    }

    return `bg-primary text-white font-medium py-2 px-4 rounded-md transition-opacity hover:opacity-90 ${focusRingClasses}`;
});

const primaryButtonStyles = computed(() => {
    if (!config.value || config.value.theme !== 'custom') return {};

    return {
        backgroundColor: config.value.colors.primary,
    };
});

const secondaryButtonClasses = computed(() => {
    if (!config.value) return '';

    if (config.value.theme === 'custom') {
        return `text-white font-medium py-2 px-4 rounded-md transition-opacity hover:opacity-90 ${focusRingClasses}`;
    }

    // Hover darkens the border, not the background: the neutral scale does not
    // follow dark mode, so a neutral hover background would hide the light text
    return `bg-muted text-base-primary border border-base-muted hover:border-base-secondary font-medium py-2 px-4 rounded-md transition-colors ${focusRingClasses}`;
});

const secondaryButtonStyles = computed(() => {
    if (!config.value || config.value.theme !== 'custom') return {};

    return {
        backgroundColor: config.value.colors.secondary,
    };
});

/**
 * Store the visitor's choice and apply it to this page. Returns whether it was saved: when
 * it was not, nothing changes and the visitor is told so.
 */
async function applyChoice(preferences: Record<string, boolean>): Promise<boolean> {
    const current = config.value;
    if (!current || saving.value) return false;

    saving.value = true;
    try {
        await saveConsent(preferences, current.configVersion);
    } catch {
        saveError.value = current.texts.saveError;

        return false;
    } finally {
        saving.value = false;
    }

    saveError.value = null;
    isVisible.value = false;

    // What was stored with a consent that no longer stands goes with it
    deleteCookiesOfRejected(categories.value, preferences);

    // From each category's own keys, the same mapping the server uses on the next page
    if (current.consentModeEnabled) {
        updateConsentMode(
            consentModeUpdate(categories.value, preferences, current.consentModeDefaults)
        );
    }
    notifyCookieConsentChanged();

    if (choiceNeedsReload(categories.value, pagePreferences, preferences, current)) {
        reloadPage();

        return true;
    }
    pagePreferences = { ...preferences };

    // Without Consent Mode the server leaves the container out until its category is accepted
    if (
        current.gtmEnabled &&
        !current.consentModeEnabled &&
        preferences[current.gtmCategory] === true
    ) {
        loadGoogleTagManager(current.gtmContainerId);
    }

    return true;
}

async function acceptAll(): Promise<void> {
    const preferences: Record<string, boolean> = {};
    for (const category of categories.value) {
        preferences[category.slug] = true;
    }

    await applyChoice(preferences);
}

async function rejectAll(): Promise<void> {
    const preferences: Record<string, boolean> = {};
    for (const category of categories.value) {
        // Only accept required categories
        preferences[category.slug] = category.isRequired;
    }

    await applyChoice(preferences);
}

function openSettings(): void {
    saveError.value = null;
    showSettingsModal.value = true;
}

function closeSettings(): void {
    saveError.value = null;
    showSettingsModal.value = false;
}

async function handleSaveSettings(preferences: Record<string, boolean>): Promise<void> {
    // Not saved: the window stays open with the message, so the visitor can try again
    if (await applyChoice(preferences)) {
        showSettingsModal.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            leave-active-class="transition-all duration-200 ease-in"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="isVisible && config" :class="positionClasses">
                <div :class="bannerClasses" :style="bannerStyles" class="p-4 md:p-6">
                    <div class="max-w-7xl mx-auto">
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                        >
                            <!-- Content -->
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold mb-1">
                                    {{ config.texts.title }}
                                </h3>
                                <p class="text-sm opacity-80">
                                    {{ config.texts.description }}
                                    <a
                                        v-if="config.texts.privacyUrl"
                                        :href="config.texts.privacyUrl"
                                        class="underline hover:no-underline"
                                    >
                                        {{ config.texts.privacyLink }}
                                    </a>
                                </p>
                                <!-- With the preferences open, the message is shown there -->
                                <p
                                    v-if="saveError && !showSettingsModal"
                                    role="alert"
                                    class="mt-2 rounded-md border border-error bg-error-light px-3 py-2 text-sm text-error"
                                >
                                    {{ saveError }}
                                </p>
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                                <button
                                    v-if="config.showSettingsButton"
                                    type="button"
                                    :class="secondaryButtonClasses"
                                    :style="secondaryButtonStyles"
                                    @click="openSettings"
                                >
                                    {{ config.texts.settings }}
                                </button>

                                <button
                                    v-if="config.showRejectAll"
                                    type="button"
                                    :class="secondaryButtonClasses"
                                    :style="secondaryButtonStyles"
                                    :disabled="saving"
                                    @click="rejectAll"
                                >
                                    {{ config.texts.rejectAll }}
                                </button>

                                <button
                                    type="button"
                                    :class="primaryButtonClasses"
                                    :style="primaryButtonStyles"
                                    :disabled="saving"
                                    @click="acceptAll"
                                >
                                    {{ config.texts.acceptAll }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Reopens the preferences once the visitor has chosen -->
        <button
            v-if="config && config.showFloatingButton && !isVisible && !showSettingsModal"
            type="button"
            :class="floatingButtonClasses"
            :style="floatingButtonStyles"
            :aria-label="config.texts.preferences"
            :title="config.texts.preferences"
            @click="openSettings"
        >
            <Cookie class="h-5 w-5" aria-hidden="true" />
        </button>

        <!-- Settings Modal -->
        <CookieSettingsModal
            v-if="showSettingsModal && config"
            :categories="categories"
            :config="config"
            :error="saveError"
            :saving="saving"
            @close="closeSettings"
            @save="handleSaveSettings"
        />
    </Teleport>
</template>
