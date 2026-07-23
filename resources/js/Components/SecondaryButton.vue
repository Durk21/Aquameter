<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-ocean-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-5 py-2.5 text-sm font-semibold text-ocean-700 dark:text-neutral-200 shadow-soft transition-all duration-200 ease-out hover:bg-ocean-50 dark:hover:bg-neutral-700 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-ocean-400 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 active:translate-y-0 disabled:opacity-50 disabled:pointer-events-none disabled:translate-y-0"
    >
        <slot />
    </button>
</template>
