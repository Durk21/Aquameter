<script setup>
import { computed } from "vue";
import Icon from "@/Components/Icon.vue";

const props = defineProps({
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
    icon: {
        type: String,
        default: null,
    },
    trend: {
        type: Object,
        default: null,
        // { value: Number, label: String, direction: 'up' | 'down' | 'flat' (optional, inferred from value) }
    },
    sparkline: {
        type: Array,
        default: null,
    },
});

const badgeStyles = {
    ocean: "bg-gradient-to-br from-ocean-400 to-ocean-600 text-white",
    amber: "bg-gradient-to-br from-amber-400 to-amber-600 text-white",
    red: "bg-gradient-to-br from-red-400 to-red-600 text-white",
    emerald: "bg-gradient-to-br from-emerald-400 to-emerald-600 text-white",
    indigo: "bg-gradient-to-br from-indigo-400 to-indigo-600 text-white",
    neutral: "bg-gradient-to-br from-neutral-400 to-neutral-600 text-white",
};

const sparklineStroke = {
    ocean: "#249cac",
    amber: "#d97706",
    red: "#dc2626",
    emerald: "#059669",
    indigo: "#4f46e5",
    neutral: "#737373",
};

const trendDirection = computed(() => {
    if (!props.trend) return null;
    if (props.trend.direction) return props.trend.direction;
    if (props.trend.value > 0) return "up";
    if (props.trend.value < 0) return "down";
    return "flat";
});

const trendStyles = {
    up: "text-emerald-600 dark:text-emerald-400",
    down: "text-red-600 dark:text-red-400",
    flat: "text-ocean-400 dark:text-neutral-400",
};

const sparklinePoints = computed(() => {
    if (!props.sparkline || props.sparkline.length < 2) return null;

    const values = props.sparkline;
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const width = 64;
    const height = 24;

    return values
        .map((v, i) => {
            const x = (i / (values.length - 1)) * width;
            const y = height - ((v - min) / range) * height;
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(" ");
});
</script>

<template>
    <div class="group relative bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-neutral-800 p-4 shadow-soft transition-all duration-200 hover:shadow-elevated hover:-translate-y-0.5 overflow-hidden">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium text-ocean-500 dark:text-neutral-400 uppercase tracking-wide truncate">{{ label }}</p>
                <p class="mt-1.5 text-2xl font-display font-semibold text-ocean-900 dark:text-neutral-50 tabular-nums">
                    {{ value }}
                </p>
                <p v-if="trend" class="mt-1 flex items-center gap-1 text-xs font-medium" :class="trendStyles[trendDirection]">
                    <Icon
                        :name="trendDirection === 'up' ? 'trending-up' : trendDirection === 'down' ? 'trending-down' : 'minus'"
                        :size="13"
                    />
                    {{ trend.label ?? trend.value }}
                </p>
            </div>
            <div
                v-if="icon"
                class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center shadow-soft"
                :class="badgeStyles[accent] || badgeStyles.ocean"
            >
                <Icon :name="icon" :size="18" />
            </div>
        </div>

        <svg
            v-if="sparklinePoints"
            class="absolute bottom-0 right-0 w-16 h-6 opacity-40 group-hover:opacity-70 transition-opacity"
            viewBox="0 0 64 24"
            preserveAspectRatio="none"
        >
            <polyline
                :points="sparklinePoints"
                fill="none"
                :stroke="sparklineStroke[accent] || sparklineStroke.ocean"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    </div>
</template>
