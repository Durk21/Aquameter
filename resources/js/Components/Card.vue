<script setup>
defineProps({
    glass: {
        type: Boolean,
        default: false,
    },
    padded: {
        type: Boolean,
        default: true,
    },
    hover: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="rounded-2xl border transition-all duration-200"
        :class="[
            padded ? 'p-5 sm:p-6' : '',
            glass
                ? 'bg-white/70 dark:bg-neutral-900/60 backdrop-blur-xl border-white/60 dark:border-white/10 shadow-soft'
                : 'bg-white dark:bg-neutral-900 border-ocean-100 dark:border-neutral-800 shadow-soft',
            hover ? 'hover:shadow-elevated hover:-translate-y-0.5 hover:border-ocean-200 dark:hover:border-neutral-700' : '',
        ]"
    >
        <slot />
    </div>
</template>
