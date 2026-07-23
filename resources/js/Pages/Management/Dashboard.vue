<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import Card from "@/Components/Card.vue";
import BarChart from "@/Components/BarChart.vue";
import EmptyState from "@/Components/EmptyState.vue";
import StatTile from "@/Components/StatTile.vue";
import { Head } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    byType: {
        type: Array,
        required: true,
    },
    byZone: {
        type: Array,
        required: true,
    },
    stallThresholdDays: {
        type: Number,
        required: true,
    },
});

const formatDays = (value) => (value === null ? "—" : `${value}d`);

const byTypeLabels = computed(() => props.byType.map((row) => row.label));
const byTypeValues = computed(() => props.byType.map((row) => row.count));
const byZoneLabels = computed(() => props.byZone.map((row) => row.zone));
const byZoneValues = computed(() => props.byZone.map((row) => row.count));
</script>

<template>
    <Head title="Management Dashboard" />

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

            <div v-if="stats.stalled_work_orders > 0 || stats.stalled_complaints > 0" class="text-sm text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl px-4 py-3">
                {{ stats.stalled_work_orders }} work order(s) and {{ stats.stalled_complaints }} complaint(s) have gone
                {{ stallThresholdDays }}+ days without action.
            </div>

            <section>
                <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">Work Orders</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Active Pipeline" :value="stats.active_pipeline" accent="indigo" icon="zap" />
                    <StatTile label="Completed This Month" :value="stats.completed_this_month" accent="emerald" icon="check" />
                    <StatTile label="Avg Resolution Time" :value="formatDays(stats.avg_resolution_days)" accent="ocean" icon="history" />
                    <StatTile
                        label="Completion Rate"
                        :value="stats.completion_rate === null ? '—' : `${stats.completion_rate}%`"
                        accent="emerald"
                        icon="trending-up"
                    />
                </div>
            </section>

            <section>
                <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">Disconnection & Reconnection</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Currently Disconnected" :value="stats.currently_disconnected" accent="red" icon="zap" />
                    <StatTile label="Disconnections This Month" :value="stats.disconnections_this_month" accent="amber" icon="trending-down" />
                    <StatTile label="Reconnections This Month" :value="stats.reconnections_this_month" accent="emerald" icon="trending-up" />
                    <StatTile label="Avg Days Disconnected" :value="formatDays(stats.avg_days_disconnected)" accent="ocean" icon="history" />
                </div>
            </section>

            <section>
                <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">Complaints & Outages</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Open Complaints" :value="stats.open_complaints" accent="amber" icon="alert-triangle" />
                    <StatTile label="Resolved This Month" :value="stats.resolved_complaints_this_month" accent="emerald" icon="check" />
                    <StatTile label="Avg Resolution Time" :value="formatDays(stats.avg_complaint_resolution_days)" accent="ocean" icon="history" />
                    <StatTile label="Leak Reports This Month" :value="stats.leak_reports_this_month" accent="indigo" icon="droplet" />
                </div>
            </section>

            <section>
                <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">Customer Satisfaction</h3>
                <div class="grid grid-cols-2 gap-3">
                    <StatTile
                        label="Avg Satisfaction"
                        :value="stats.avg_rating === null ? '—' : `${stats.avg_rating} / 5`"
                        accent="amber"
                        icon="star"
                    />
                    <StatTile label="Rated Jobs" :value="stats.rated_count" accent="ocean" icon="check" />
                </div>
            </section>

            <section>
                <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">Escalations</h3>
                <p class="text-sm text-ocean-500 dark:text-neutral-400 mb-3">
                    Flagged when a work order sits unclaimed, or a complaint sits unreviewed, for {{ stallThresholdDays }}+ days.
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <StatTile label="Stalled Work Orders" :value="stats.stalled_work_orders" :accent="stats.stalled_work_orders > 0 ? 'red' : 'emerald'" icon="alert-triangle" />
                    <StatTile label="Stalled Complaints" :value="stats.stalled_complaints" :accent="stats.stalled_complaints > 0 ? 'red' : 'emerald'" icon="alert-triangle" />
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <section>
                    <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">By Type</h3>
                    <Card>
                        <EmptyState v-if="byType.length === 0" icon="clipboard-list" title="No work orders yet" />
                        <BarChart v-else :labels="byTypeLabels" :values="byTypeValues" color="#249cac" horizontal />
                    </Card>
                </section>

                <section>
                    <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">By Zone</h3>
                    <Card>
                        <EmptyState v-if="byZone.length === 0" icon="map-pin" title="No work orders yet" />
                        <BarChart v-else :labels="byZoneLabels" :values="byZoneValues" color="#4f46e5" horizontal />
                    </Card>
                </section>
            </div>
        </div>
    </AppShell>
</template>
