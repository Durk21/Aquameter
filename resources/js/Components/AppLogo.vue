<script setup>
defineProps({
    size: {
        type: Number,
        default: 32,
    },
    mono: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="20" cy="20" r="19" stroke="currentColor" stroke-width="2" :class="mono ? '' : 'text-ocean-600'" :opacity="mono ? 1 : undefined" />
        <path
            d="M 8 24 A 14 14 0 1 1 32 24"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
            fill="none"
            :class="mono ? '' : 'text-ocean-200'"
            :opacity="mono ? 0.5 : undefined"
        />
        <path
            d="M 8 24 A 14 14 0 0 1 20 8"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
            fill="none"
            :class="mono ? '' : 'text-ocean-500'"
            :opacity="mono ? 0.85 : undefined"
        />
        <line x1="20" y1="20" x2="26" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round" :class="mono ? '' : 'text-ocean-900'" />
        <circle cx="20" cy="20" r="2.2" fill="currentColor" :class="mono ? '' : 'text-ocean-900'" />
    </svg>
</template>
