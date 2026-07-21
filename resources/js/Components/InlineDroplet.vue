<script setup>
defineProps({
    size: {
        type: Number,
        default: 14,
    },
});
</script>

<template>
    <svg
        :width="size"
        :height="size * 1.25"
        viewBox="0 0 72 90"
        class="shrink-0"
        role="status"
        aria-label="Loading"
    >
        <defs>
            <clipPath id="inlineDropletClip">
                <path d="M36 4 C36 4 8 42 8 60 a28 28 0 0 0 56 0 C64 42 36 4 36 4Z" />
            </clipPath>
        </defs>
        <path
            d="M36 4 C36 4 8 42 8 60 a28 28 0 0 0 56 0 C64 42 36 4 36 4Z"
            fill="none"
            stroke="currentColor"
            stroke-width="4"
            opacity="0.5"
        />
        <g clip-path="url(#inlineDropletClip)">
            <rect x="0" y="26" width="72" height="90" fill="currentColor" class="inline-droplet-fill" />
        </g>
    </svg>
</template>

<style scoped>
.inline-droplet-fill {
    animation: inlineDropletRise 0.9s ease-in-out infinite alternate;
}

@keyframes inlineDropletRise {
    0% {
        transform: translateY(34px);
    }
    100% {
        transform: translateY(-4px);
    }
}

@media (prefers-reduced-motion: reduce) {
    .inline-droplet-fill {
        animation: none;
        transform: translateY(10px);
    }
}
</style>
