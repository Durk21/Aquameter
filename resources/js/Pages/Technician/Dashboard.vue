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
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Dashboard
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-8">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white">
                Dashboard
            </h1>

            <div class="flex flex-wrap gap-2">
                <Link
                    :href="route('technician.work-orders.index')"
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-gradient-ocean text-white shadow-soft hover:shadow-glow transition-shadow"
                >
                    Work Orders
                </Link>
                <Link
                    :href="route('technician.meter-readings.create')"
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold border border-ocean-200 dark:border-white/10 text-ocean-700 dark:text-ocean-100 hover:bg-ocean-50 dark:hover:bg-white/5 transition-colors"
                >
                    Record Reading
                </Link>
            </div>

            <div v-if="!stats.zone" class="text-sm text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl px-4 py-3">
                You don't have a zone assigned yet — ask an admin to set one from the Technicians page so jobs in your area are prioritized for you.
            </div>
            <div v-else class="text-sm text-ocean-700 dark:text-ocean-100 bg-ocean-50 dark:bg-white/5 rounded-xl px-4 py-3">
                Your zone: <span class="font-medium">{{ stats.zone }}</span>
            </div>

            <section>
                <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">My Work</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Active Jobs" :value="stats.my_active_jobs" accent="indigo" icon="zap" />
                    <StatTile label="Available In Zone" :value="stats.available_in_zone" accent="ocean" icon="map-pin" />
                    <StatTile label="Completed This Week" :value="stats.completed_this_week" accent="emerald" icon="check" />
                    <StatTile label="Completed Total" :value="stats.completed_total" accent="ocean" icon="clipboard-list" />
                </div>
            </section>
        </div>
    </AppShell>
</template>
