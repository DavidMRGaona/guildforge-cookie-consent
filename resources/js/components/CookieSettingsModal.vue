<script setup lang="ts">
import { computed, ref } from 'vue';
import type { CookieCategory, BannerConfig } from '../types/cookie-consent';
import { getStoredConsent } from '../utils/consent-manager';

interface Props {
    categories: CookieCategory[];
    config: BannerConfig;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    save: [preferences: Record<string, boolean>];
}>();

// Initialize preferences from stored consent or default to required only
const storedConsent = getStoredConsent();
const preferences = ref<Record<string, boolean>>(
    storedConsent?.preferences ??
        Object.fromEntries(props.categories.map((c) => [c.slug, c.isRequired]))
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
    if (props.config.theme !== 'custom') return {};

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
    emit('save', { ...preferences.value });
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
            <div
                :class="modalClasses"
                :style="modalStyles"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between p-4 border-b border-default"
                >
                    <h2 class="text-xl font-semibold">
                        {{ config.texts.settings }}
                    </h2>
                    <button
                        type="button"
                        class="p-1 rounded-full hover:bg-muted transition-colors"
                        @click="close"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
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
                            class="text-sm text-primary hover:underline"
                            @click="acceptAll"
                        >
                            {{ config.texts.acceptAll }}
                        </button>
                        <span class="text-base-muted">|</span>
                        <button
                            type="button"
                            class="text-sm text-primary hover:underline"
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
                            <div
                                class="flex items-center justify-between p-4 bg-muted"
                            >
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-medium">
                                            {{ category.name }}
                                        </h3>
                                        <span
                                            v-if="category.isRequired"
                                            class="text-xs bg-muted px-2 py-0.5 rounded"
                                        >
                                            Obligatoria
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
                                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                                        preferences[category.slug]
                                            ? 'bg-primary'
                                            : 'bg-neutral-200 dark:bg-neutral-600',
                                        category.isRequired
                                            ? 'cursor-not-allowed opacity-70'
                                            : '',
                                    ]"
                                    :disabled="category.isRequired"
                                    role="switch"
                                    :aria-checked="preferences[category.slug]"
                                    @click="toggleCategory(category.slug)"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            preferences[category.slug]
                                                ? 'translate-x-5'
                                                : 'translate-x-0',
                                        ]"
                                    />
                                </button>
                            </div>

                            <!-- Cookie list (expandable) -->
                            <div
                                v-if="category.cookies.length > 0"
                                class="border-t border-default"
                            >
                                <button
                                    type="button"
                                    class="w-full flex items-center justify-between p-3 text-sm hover:bg-muted transition-colors"
                                    @click="toggleCookieList(category.slug)"
                                >
                                    <span>
                                        Ver cookies ({{
                                            category.cookies.length
                                        }})
                                    </span>
                                    <svg
                                        :class="[
                                            'w-4 h-4 transition-transform',
                                            expandedCategories.has(
                                                category.slug
                                            )
                                                ? 'rotate-180'
                                                : '',
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
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
                                        v-if="
                                            expandedCategories.has(
                                                category.slug
                                            )
                                        "
                                        class="overflow-hidden"
                                    >
                                        <table
                                            class="w-full text-xs border-t border-default"
                                        >
                                            <thead
                                                class="bg-muted"
                                            >
                                                <tr>
                                                    <th
                                                        class="px-3 py-2 text-left font-medium"
                                                    >
                                                        Cookie
                                                    </th>
                                                    <th
                                                        class="px-3 py-2 text-left font-medium"
                                                    >
                                                        Proveedor
                                                    </th>
                                                    <th
                                                        class="px-3 py-2 text-left font-medium"
                                                    >
                                                        Duración
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="cookie in category.cookies"
                                                    :key="cookie.id"
                                                    class="border-t border-default"
                                                >
                                                    <td
                                                        class="px-3 py-2 font-mono"
                                                    >
                                                        {{ cookie.name }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{ cookie.provider }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{
                                                            cookie.duration ??
                                                            '-'
                                                        }}
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
                <div
                    class="flex items-center justify-between p-4 border-t border-default"
                >
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
                            class="px-4 py-2 text-sm font-medium text-base-secondary hover:bg-muted rounded-md transition-colors"
                            @click="close"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary hover:opacity-90 rounded-md transition-colors"
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
