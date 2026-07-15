<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    accent: {
        type: String,
        default: "ocean",
    },
});

const accentStyles = {
    ocean: "border-ocean-200",
    amber: "border-amber-300",
    red: "border-red-300",
    emerald: "border-emerald-300",
    indigo: "border-indigo-300",
    neutral: "border-neutral-300",
};

const valueStyles = {
    ocean: "text-ocean-900",
    amber: "text-amber-700",
    red: "text-red-700",
    emerald: "text-emerald-700",
    indigo: "text-indigo-700",
    neutral: "text-neutral-700",
};
</script>

<template>
    <div class="bg-white rounded-lg border-l-4 border border-ocean-100 p-4" :class="accentStyles[accent] || accentStyles.ocean">
        <p class="text-xs font-medium text-ocean-500 uppercase tracking-wide">{{ label }}</p>
        <p class="mt-1 text-2xl font-semibold" :class="valueStyles[accent] || valueStyles.ocean" style="font-family: 'Space Grotesk', sans-serif;">
            {{ value }}
        </p>
    </div>
</template>
