<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    outages: {
        type: Array,
        required: true,
    },
});

const statusStyles = {
    scheduled: "bg-amber-100 text-amber-800",
    active: "bg-red-100 text-red-800",
    resolved: "bg-emerald-100 text-emerald-800",
};
</script>

<template>
    <Head title="Outages" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Outages
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Outages
            </h1>

            <p class="text-sm text-ocean-500 mb-4">
                Service notices affecting your zone, most recent first.
            </p>

            <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                <div v-if="outages.length === 0" class="p-6 text-ocean-700 text-sm">
                    No outage notices for your zone.
                </div>
                <div v-else class="divide-y divide-ocean-100">
                    <div v-for="o in outages" :key="o.id" class="p-4">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="statusStyles[o.status] || 'bg-ocean-100 text-ocean-800'"
                            >
                                {{ o.status_label }}
                            </span>
                            <p class="font-medium text-ocean-900">{{ o.title }}</p>
                        </div>
                        <p class="text-xs text-ocean-500 mt-1">
                            {{ o.zone || 'All zones' }} · Starts {{ o.starts_at }}<span v-if="o.ends_at"> · Ends {{ o.ends_at }}</span>
                        </p>
                        <p class="text-sm text-ocean-700 mt-1">{{ o.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>
