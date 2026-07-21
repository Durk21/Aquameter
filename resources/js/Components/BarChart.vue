<script setup>
import { computed } from "vue";
import { Bar } from "vue-chartjs";
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Tooltip,
} from "chart.js";
import { useDarkMode } from "@/composables/useDarkMode";

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip);

const props = defineProps({
    labels: {
        type: Array,
        required: true,
    },
    values: {
        type: Array,
        required: true,
    },
    color: {
        type: String,
        default: "#249cac",
    },
    horizontal: {
        type: Boolean,
        default: false,
    },
});

const { isDark } = useDarkMode();

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.values,
            backgroundColor: props.color,
            borderRadius: 6,
            barThickness: 18,
            maxBarThickness: 22,
        },
    ],
}));

const chartOptions = computed(() => {
    const tickColor = isDark.value ? "#a8c4cc" : "#5C7688";
    const gridColor = isDark.value ? "rgba(255,255,255,0.06)" : "rgba(31,102,118,0.06)";

    return {
        indexAxis: props.horizontal ? "y" : "x",
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: isDark.value ? "#102c38" : "#1f6676",
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
            },
        },
        scales: {
            x: {
                ticks: { color: tickColor, font: { size: 11 } },
                grid: { color: props.horizontal ? gridColor : "transparent" },
                border: { display: false },
                beginAtZero: true,
            },
            y: {
                ticks: { color: tickColor, font: { size: 11 } },
                grid: { color: props.horizontal ? "transparent" : gridColor },
                border: { display: false },
                beginAtZero: true,
            },
        },
    };
});
</script>

<template>
    <div class="h-56">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
