<script setup>
import { router } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";

const visible = ref(false);
const MIN_VISIBLE_MS = 400;
let showStartTime = null;
let hideTimer = null;
let removeStart = null;
let removeFinish = null;

function handleStart() {
    clearTimeout(hideTimer);
    showStartTime = Date.now();
    visible.value = true;
}

function handleFinish() {
    const elapsed = Date.now() - (showStartTime || Date.now());
    const remaining = Math.max(0, MIN_VISIBLE_MS - elapsed);
    hideTimer = setTimeout(() => {
        visible.value = false;
    }, remaining);
}

onMounted(() => {
    removeStart = router.on("start", handleStart);
    removeFinish = router.on("finish", handleFinish);
});

onUnmounted(() => {
    if (removeStart) removeStart();
    if (removeFinish) removeFinish();
    clearTimeout(hideTimer);
});
</script>

<template>
    <Transition name="droplet-fade">
        <div
            v-if="visible"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-ocean-950/40 backdrop-blur-sm"
            role="status"
            aria-live="polite"
            aria-label="Loading"
        >
            <svg width="64" height="80" viewBox="0 0 72 90">
                <defs>
                    <clipPath id="dropletClip">
                        <path d="M36 4 C36 4 8 42 8 60 a28 28 0 0 0 56 0 C64 42 36 4 36 4Z" />
                    </clipPath>
                </defs>
                <path
                    d="M36 4 C36 4 8 42 8 60 a28 28 0 0 0 56 0 C64 42 36 4 36 4Z"
                    fill="none"
                    stroke="#3FA9B8"
                    stroke-width="2.5"
                />
                <g clip-path="url(#dropletClip)">
                    <rect x="0" y="26" width="72" height="90" fill="#1f7e91" class="droplet-fill" />
                </g>
            </svg>
        </div>
    </Transition>
</template>

<style scoped>
.droplet-fill {
    animation: dropletRise 1s ease-in-out infinite alternate;
}

@keyframes dropletRise {
    0% {
        transform: translateY(34px);
    }
    100% {
        transform: translateY(-4px);
    }
}

.droplet-fade-enter-active,
.droplet-fade-leave-active {
    transition: opacity 0.15s ease;
}

.droplet-fade-enter-from,
.droplet-fade-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .droplet-fill {
        animation: none;
        transform: translateY(0);
    }
}
</style>
