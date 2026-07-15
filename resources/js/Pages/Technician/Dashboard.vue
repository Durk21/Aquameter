<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import StatTile from "@/Components/StatTile.vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    stats: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Technician Dashboard" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Dashboard
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-8">
            <div class="flex flex-wrap gap-2">
                <Link
                    :href="route('technician.work-orders.index')"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                >
                    Work Orders
                </Link>
                <Link
                    :href="route('technician.meter-readings.create')"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                >
                    Record Reading
                </Link>
            </div>

            <div v-if="!stats.zone" class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                You don't have a zone assigned yet — ask an admin to set one from the Technicians page so jobs in your area are prioritized for you.
            </div>
            <div v-else class="text-sm text-ocean-700 bg-ocean-50 rounded-md px-3 py-2">
                Your zone: <span class="font-medium">{{ stats.zone }}</span>
            </div>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">My Work</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Active Jobs" :value="stats.my_active_jobs" accent="indigo" />
                    <StatTile label="Available In Zone" :value="stats.available_in_zone" accent="ocean" />
                    <StatTile label="Completed This Week" :value="stats.completed_this_week" accent="emerald" />
                    <StatTile label="Completed Total" :value="stats.completed_total" accent="ocean" />
                </div>
            </section>
        </div>
    </AppShell>
</template>
