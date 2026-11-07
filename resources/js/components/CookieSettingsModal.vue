<script setup lang="ts">
import { ChevronDown, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import HelpLink from '@/components/knowledge/HelpLink.vue';
import type { CookieCategory, BannerConfig } from '../types/cookie-consent';
import { bannerThemeVariables } from '../utils/banner-theme';
import { getStoredConsent, needsConsent } from '../utils/consent-manager';

interface Props {
    categories: CookieCategory[];
    config: BannerConfig;
    /** Shown when the last choice could not be saved. */
    error?: string | null;
    /** While a choice is being saved. */
    saving?: boolean;
}

const props = withDefaults(defineProps<Props>(), { error: null, saving: false });

const emit = defineEmits<{
    close: [];
    save: [preferences: Record<string, boolean>];
}>();

// One entry per current category: required ones always on, the others as last chosen while
// that choice still counts (asked again, nothing may be ticked beforehand) and off otherwise.
// Categories created or removed since then must not break or bloat what is sent.
const storedPreferences = needsConsent(
    props.config.configVersion,
    props.config.validityDays,
    props.config.reconsentOnChange
)
    ? {}
    : (getStoredConsent()?.preferences ?? {});
const preferences = ref<Record<string, boolean>>(
    Object.fromEntries(
        props.categories.map((c) => [c.slug, c.isRequired || storedPreferences[c.slug] === true])
    )
);

// Track which categories have expanded cookie lists
const expandedCategories = ref<Set<string>>(new Set());

const modalClasses = computed(() => {
    const base = 'rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden';

    if (props.config.theme === 'custom') {
        return base;
    }

    return `${base} bg-surface text-base-primary`;
});

const modalStyles = computed(() => {
    if (props.config.theme !== 'custom') return bannerThemeVariables(props.config.theme);

    return {
        backgroundColor: props.config.colors.background,
        color: props.config.colors.text,
    };
});

function toggleCategory(slug: string): void {
    // Don't allow toggling required categories
    const category = props.categories.find((c) => c.slug === slug);
    if (category?.isRequired) return;

    preferences.value[slug] = !preferences.value[slug];
}

function toggleCookieList(slug: string): void {
    if (expandedCategories.value.has(slug)) {
        expandedCategories.value.delete(slug);
    } else {
        expandedCategories.value.add(slug);
    }
}

function acceptAll(): void {
    for (const category of props.categories) {
        preferences.value[category.slug] = true;
    }
}

function rejectAll(): void {
    for (const category of props.categories) {
        preferences.value[category.slug] = category.isRequired;
    }
}

function save(): void {
    if (props.saving) return;

    emit(
        'save',
        Object.fromEntries(
            props.categories.map((c) => [
                c.slug,
                c.isRequired || preferences.value[c.slug] === true,
            ])
        )
    );
}

function close(): void {
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
            @click.self="close"
        >
            <div :class="modalClasses" :style="modalStyles">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-default">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-semibold">
                            {{ config.texts.settings }}
                        </h2>
                        <!-- The layout keeps the banner between pages: close this window, or it would cover the guide -->
                        <span class="inline-flex" @click="close">
                            <HelpLink guide="cookie-consent.cookie-preferences" />
                        </span>
                    </div>
                    <button
                        type="button"
                        class="p-1 rounded-full hover:bg-base-secondary/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 focus-visible:ring-offset-surface"
                        :aria-label="config.texts.close"
                        @click="close"
                    >
                        <X class="w-6 h-6" aria-hidden="true" />
                    </button>
                </div>

                <!-- Content -->
                <div class="p-4 overflow-y-auto max-h-[60vh]">
                    <p class="text-sm opacity-80 mb-4">
                        {{ config.texts.description }}
                    </p>

                    <!-- Quick actions -->
                    <div class="flex gap-2 mb-4">
                        <button
                            type="button"
                            class="text-sm text-primary hover:underline rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 focus-visible:ring-offset-surface"
                            @click="acceptAll"
                        >
                            {{ config.texts.acceptAll }}
                        </button>
                        <span class="text-base-muted">|</span>
                        <button
                            type="button"
                            class="text-sm text-primary hover:underline rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 focus-visible:ring-offset-surface"
                            @click="rejectAll"
                        >
                            {{ config.texts.rejectAll }}
                        </button>
                    </div>

                    <!-- Categories -->
                    <div class="space-y-4">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="border border-default rounded-lg overflow-hidden"
                        >
                            <!-- Category header -->
                            <div class="flex items-center justify-between p-4 bg-base-secondary/10">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-medium">
                                            {{ category.name }}
                                        </h3>
                                        <span
                                            v-if="category.isRequired"
                                            class="text-xs bg-base-secondary/20 px-2 py-0.5 rounded"
                                        >
                                            {{ config.texts.required }}
                                        </span>
                                    </div>
                                    <p class="text-sm opacity-70 mt-1">
                                        {{ category.description }}
                                    </p>
                                </div>

                                <!-- Toggle -->
                                <button
                                    type="button"
                                    :class="[
                                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 focus-visible:ring-offset-surface',
                                        preferences[category.slug]
                                            ? 'bg-primary'
                                            : 'bg-base-secondary/50',
                                        category.isRequired ? 'cursor-not-allowed opacity-70' : '',
                                    ]"
                                    :disabled="category.isRequired"
                                    role="switch"
                                    :aria-checked="preferences[category.slug]"
                                    @click="toggleCategory(category.slug)"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-elevated shadow ring-0 transition duration-200 ease-in-out',
                                            preferences[category.slug]
                                                ? 'translate-x-5'
                                                : 'translate-x-0',
                                        ]"
                                    />
                                </button>
                            </div>

                            <!-- Cookie list (expandable) -->
                            <div v-if="category.cookies.length > 0" class="border-t border-default">
                                <button
                                    type="button"
                                    class="w-full flex items-center justify-between p-3 text-sm hover:bg-base-secondary/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500"
                                    @click="toggleCookieList(category.slug)"
                                >
                                    <span>
                                        {{ config.texts.showCookies }} ({{
                                            category.cookies.length
                                        }})
                                    </span>
                                    <ChevronDown
                                        :class="[
                                            'w-4 h-4 transition-transform',
                                            expandedCategories.has(category.slug)
                                                ? 'rotate-180'
                                                : '',
                                        ]"
                                        aria-hidden="true"
                                    />
                                </button>

                                <Transition
                                    enter-active-class="transition-all duration-200 ease-out"
                                    leave-active-class="transition-all duration-150 ease-in"
                                    enter-from-class="opacity-0 max-h-0"
                                    enter-to-class="opacity-100 max-h-96"
                                    leave-from-class="opacity-100 max-h-96"
                                    leave-to-class="opacity-0 max-h-0"
                                >
                                    <div
                                        v-if="expandedCategories.has(category.slug)"
                                        class="overflow-hidden"
                                    >
                                        <table class="w-full text-xs border-t border-default">
                                            <thead class="bg-base-secondary/10">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-medium">
                                                        {{ config.texts.cookieColumn }}
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-medium">
                                                        {{ config.texts.providerColumn }}
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-medium">
                                                        {{ config.texts.durationColumn }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="cookie in category.cookies"
                                                    :key="cookie.id"
                                                    class="border-t border-default"
                                                >
                                                    <td class="px-3 py-2 align-top">
                                                        <span class="font-mono break-all">
                                                            {{ cookie.name }}
                                                        </span>
                                                        <p
                                                            v-if="cookie.purpose.trim()"
                                                            class="mt-1 text-base-secondary"
                                                        >
                                                            {{ cookie.purpose }}
                                                        </p>
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{ cookie.provider }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{ cookie.duration ?? '-' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <p
                    v-if="error"
                    role="alert"
                    class="mx-4 mt-4 rounded-md border border-error bg-error-light px-3 py-2 text-sm text-error"
                >
                    {{ error }}
                </p>
                <div class="flex items-center justify-between p-4 border-t border-default">
                    <a
                        v-if="config.texts.privacyUrl"
                        :href="config.texts.privacyUrl"
                        class="text-sm text-primary hover:underline"
                    >
                        {{ config.texts.privacyLink }}
                    </a>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="px-4 py-2 text-sm font-medium bg-muted text-base-primary border border-base-muted hover:border-base-secondary rounded-md transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 focus-visible:ring-offset-surface"
                            @click="close"
                        >
                            {{ config.texts.cancel }}
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary hover:opacity-90 rounded-md transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 focus-visible:ring-offset-surface disabled:opacity-60"
                            :disabled="saving"
                            @click="save"
                        >
                            {{ config.texts.save }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
