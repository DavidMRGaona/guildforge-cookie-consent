<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import type { CookieCategory, BannerConfig } from '../types/cookie-consent';
import { needsConsent, saveConsent } from '../utils/consent-manager';
import CookieSettingsModal from './CookieSettingsModal.vue';

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
            showLogo: true,
            blockPageUntilConsent: false,
            validityDays: 365,
            reconsentOnChange: true,
            configVersion: 1,
            texts: {
                title: 'Utilizamos cookies',
                description:
                    'Usamos cookies propias y de terceros para mejorar tu experiencia y mostrar contenido personalizado.',
                acceptAll: 'Aceptar todas',
                rejectAll: 'Rechazar todas',
                settings: 'Configurar',
                save: 'Guardar preferencias',
                privacyLink: 'Política de privacidad',
                privacyUrl: '/politica-de-privacidad',
            },
            gtmEnabled: false,
            gtmContainerId: '',
            consentModeEnabled: true,
        },
    }),
});

const isVisible = ref(false);
const showSettingsModal = ref(false);

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

onMounted(() => {
    // Re-check after mount in case config was not available initially
    if (config.value) {
        isVisible.value = needsConsent(
            config.value.configVersion,
            config.value.validityDays,
            config.value.reconsentOnChange
        );
    }
});

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
    if (!config.value || config.value.theme !== 'custom') return {};

    return {
        backgroundColor: config.value.colors.background,
        color: config.value.colors.text,
    };
});

const primaryButtonClasses = computed(() => {
    if (!config.value) return '';

    if (config.value.theme === 'custom') {
        return 'text-white font-medium py-2 px-4 rounded-md transition-opacity hover:opacity-90';
    }

    return 'bg-primary text-white font-medium py-2 px-4 rounded-md transition-opacity hover:opacity-90';
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
        return 'text-white font-medium py-2 px-4 rounded-md transition-opacity hover:opacity-90';
    }

    return 'bg-stone-200 hover:bg-stone-300 text-stone-900 dark:bg-stone-700 dark:hover:bg-stone-600 dark:text-stone-100 font-medium py-2 px-4 rounded-md transition-colors';
});

const secondaryButtonStyles = computed(() => {
    if (!config.value || config.value.theme !== 'custom') return {};

    return {
        backgroundColor: config.value.colors.secondary,
    };
});

async function acceptAll(): Promise<void> {
    if (!config.value) return;

    const preferences: Record<string, boolean> = {};
    for (const category of categories.value) {
        preferences[category.slug] = true;
    }

    await saveConsent(preferences, config.value.configVersion);
    isVisible.value = false;
}

async function rejectAll(): Promise<void> {
    if (!config.value) return;

    const preferences: Record<string, boolean> = {};
    for (const category of categories.value) {
        // Only accept required categories
        preferences[category.slug] = category.isRequired;
    }

    await saveConsent(preferences, config.value.configVersion);
    isVisible.value = false;
}

function openSettings(): void {
    showSettingsModal.value = true;
}

function closeSettings(): void {
    showSettingsModal.value = false;
}

async function handleSaveSettings(
    preferences: Record<string, boolean>
): Promise<void> {
    if (!config.value) return;

    await saveConsent(preferences, config.value.configVersion);
    showSettingsModal.value = false;
    isVisible.value = false;
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
            <div
                v-if="isVisible && config"
                :class="positionClasses"
            >
                <div
                    :class="bannerClasses"
                    :style="bannerStyles"
                    class="p-4 md:p-6"
                >
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
                            </div>

                            <!-- Buttons -->
                            <div
                                class="flex flex-wrap items-center gap-2 flex-shrink-0"
                            >
                                <button
                                    v-if="
                                        config.showSettingsButton
                                    "
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
                                    @click="rejectAll"
                                >
                                    {{ config.texts.rejectAll }}
                                </button>

                                <button
                                    type="button"
                                    :class="primaryButtonClasses"
                                    :style="primaryButtonStyles"
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

        <!-- Settings Modal -->
        <CookieSettingsModal
            v-if="showSettingsModal && config"
            :categories="categories"
            :config="config"
            @close="closeSettings"
            @save="handleSaveSettings"
        />
    </Teleport>
</template>
