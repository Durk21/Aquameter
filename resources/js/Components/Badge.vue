<script setup>
const props = defineProps({
    tone: {
        type: String,
        default: "ocean",
        validator: (value) => ["ocean", "amber", "red", "emerald", "indigo", "neutral"].includes(value),
    },
    dot: {
        type: Boolean,
        default: false,
    },
});

const toneStyles = {
    ocean: "bg-ocean-50 text-ocean-700 dark:bg-ocean-500/10 dark:text-ocean-300",
    amber: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
    red: "bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300",
    emerald: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    indigo: "bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300",
    neutral: "bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300",
};

const dotStyles = {
    ocean: "bg-ocean-500",
    amber: "bg-amber-500",
    red: "bg-red-500",
    emerald: "bg-emerald-500",
    indigo: "bg-indigo-500",
    neutral: "bg-neutral-400",
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
        :class="toneStyles[props.tone]"
    >
        <span v-if="dot" class="w-1.5 h-1.5 rounded-full" :class="dotStyles[props.tone]" />
        <slot />
    </span>
</template>
