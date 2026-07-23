<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import NetworkMap from "@/Components/NetworkMap.vue";
import Card from "@/Components/Card.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    pipeSegments: {
        type: Array,
        required: true,
    },
    incidents: {
        type: Array,
        required: true,
    },
    outages: {
        type: Array,
        required: true,
    },
    zones: {
        type: Array,
        required: true,
    },
    zoneCenters: {
        type: Object,
        required: true,
    },
    serviceAreaBounds: {
        type: Object,
        required: true,
    },
});

const legend = [
    { label: "Active pipe", swatch: "#249cac" },
    { label: "Under maintenance", swatch: "#d97706" },
    { label: "Damaged", swatch: "#dc2626" },
];

const incidentLegend = [
    { label: "Leak report", swatch: "#dc2626" },
    { label: "Service request", swatch: "#6366f1" },
    { label: "Outage zone", swatch: "#fbbf24" },
];
</script>

<template>
    <Head title="Network Map" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Network Map
            </h2>
        </template>

        <div class="max-w-6xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Network Map
            </h1>

            <p class="text-sm text-ocean-500 dark:text-neutral-400 mb-4">
                The utility's pipe network alongside recent leak reports, service requests, and active outage notices.
            </p>

            <NetworkMap
                :pipe-segments="pipeSegments"
                :incidents="incidents"
                :outages="outages"
                :zones="zones"
                :zone-centers="zoneCenters"
                :service-area-bounds="serviceAreaBounds"
                height="520px"
            />

            <Card class="mt-4 flex flex-wrap gap-x-8 gap-y-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-ocean-500 dark:text-neutral-400 mb-2">Pipe status</p>
                    <div class="flex flex-wrap gap-4">
                        <div v-for="item in legend" :key="item.label" class="flex items-center gap-2 text-sm text-ocean-700 dark:text-neutral-300">
                            <span class="w-3 h-1 rounded-full" :style="{ backgroundColor: item.swatch }"></span>
                            {{ item.label }}
                        </div>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-ocean-500 dark:text-neutral-400 mb-2">Markers</p>
                    <div class="flex flex-wrap gap-4">
                        <div v-for="item in incidentLegend" :key="item.label" class="flex items-center gap-2 text-sm text-ocean-700 dark:text-neutral-300">
                            <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: item.swatch }"></span>
                            {{ item.label }}
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppShell>
</template>
