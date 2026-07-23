<script setup>
import { Link } from "@inertiajs/vue3";
import Icon from "@/Components/Icon.vue";

const props = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
});

function isPrev(label) {
    return label.includes("Previous");
}

function isNext(label) {
    return label.includes("Next");
}
</script>

<template>
    <div v-if="paginator.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 px-1 py-4">
        <p class="text-sm text-ocean-500 dark:text-neutral-400">
            Showing {{ paginator.from }}–{{ paginator.to }} of {{ paginator.total }}
        </p>
        <nav class="flex items-center gap-1">
            <template v-for="(link, index) in paginator.links" :key="index">
                <Link
                    v-if="link.url && (isPrev(link.label) || isNext(link.label))"
                    :href="link.url"
                    preserve-scroll
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-ocean-600 dark:text-neutral-300 hover:bg-ocean-50 dark:hover:bg-white/5 transition-colors"
                    :aria-label="isPrev(link.label) ? 'Previous page' : 'Next page'"
                >
                    <Icon :name="isPrev(link.label) ? 'chevron-left' : 'chevron-right'" :size="16" />
                </Link>
                <span
                    v-else-if="!link.url && (isPrev(link.label) || isNext(link.label))"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-ocean-300 dark:text-neutral-700"
                >
                    <Icon :name="isPrev(link.label) ? 'chevron-left' : 'chevron-right'" :size="16" />
                </span>
                <span v-else-if="link.label === '...'" class="w-8 h-8 flex items-center justify-center text-sm text-ocean-400 dark:text-neutral-500">
                    …
                </span>
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition-colors"
                    :class="link.active
                        ? 'bg-gradient-ocean text-white shadow-soft'
                        : 'text-ocean-600 dark:text-neutral-300 hover:bg-ocean-50 dark:hover:bg-white/5'"
                >
                    {{ link.label }}
                </Link>
            </template>
        </nav>
    </div>
</template>
