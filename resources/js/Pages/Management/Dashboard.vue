<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import StatTile from "@/Components/StatTile.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
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
</script>

<template>
    <Head title="Management Dashboard" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Dashboard
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-8">
            <div v-if="stats.stalled_work_orders > 0 || stats.stalled_complaints > 0" class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                {{ stats.stalled_work_orders }} work order(s) and {{ stats.stalled_complaints }} complaint(s) have gone
                {{ stallThresholdDays }}+ days without action.
            </div>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Work Orders</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Active Pipeline" :value="stats.active_pipeline" accent="indigo" />
                    <StatTile label="Completed This Month" :value="stats.completed_this_month" accent="emerald" />
                    <StatTile label="Avg Resolution Time" :value="formatDays(stats.avg_resolution_days)" accent="ocean" />
                    <StatTile
                        label="Completion Rate"
                        :value="stats.completion_rate === null ? '—' : `${stats.completion_rate}%`"
                        accent="emerald"
                    />
                </div>
            </section>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Disconnection & Reconnection</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Currently Disconnected" :value="stats.currently_disconnected" accent="red" />
                    <StatTile label="Disconnections This Month" :value="stats.disconnections_this_month" accent="amber" />
                    <StatTile label="Reconnections This Month" :value="stats.reconnections_this_month" accent="emerald" />
                    <StatTile label="Avg Days Disconnected" :value="formatDays(stats.avg_days_disconnected)" accent="ocean" />
                </div>
            </section>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Complaints & Outages</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Open Complaints" :value="stats.open_complaints" accent="amber" />
                    <StatTile label="Resolved This Month" :value="stats.resolved_complaints_this_month" accent="emerald" />
                    <StatTile label="Avg Resolution Time" :value="formatDays(stats.avg_complaint_resolution_days)" accent="ocean" />
                    <StatTile label="Leak Reports This Month" :value="stats.leak_reports_this_month" accent="indigo" />
                </div>
            </section>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Customer Satisfaction</h3>
                <div class="grid grid-cols-2 gap-3">
                    <StatTile
                        label="Avg Satisfaction"
                        :value="stats.avg_rating === null ? '—' : `${stats.avg_rating} / 5`"
                        accent="amber"
                    />
                    <StatTile label="Rated Jobs" :value="stats.rated_count" accent="ocean" />
                </div>
            </section>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Escalations</h3>
                <p class="text-sm text-ocean-500 mb-3">
                    Flagged when a work order sits unclaimed, or a complaint sits unreviewed, for {{ stallThresholdDays }}+ days.
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <StatTile label="Stalled Work Orders" :value="stats.stalled_work_orders" :accent="stats.stalled_work_orders > 0 ? 'red' : 'emerald'" />
                    <StatTile label="Stalled Complaints" :value="stats.stalled_complaints" :accent="stats.stalled_complaints > 0 ? 'red' : 'emerald'" />
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <section>
                    <h3 class="font-semibold text-ocean-900 mb-3">By Type</h3>
                    <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                        <div v-if="byType.length === 0" class="p-6 text-ocean-700 text-sm">
                            No work orders yet.
                        </div>
                        <table v-else class="min-w-full divide-y divide-ocean-100">
                            <tbody class="divide-y divide-ocean-100">
                                <tr v-for="row in byType" :key="row.type">
                                    <td class="px-4 py-2 text-sm text-ocean-700">{{ row.label }}</td>
                                    <td class="px-4 py-2 text-sm text-ocean-900 text-right font-medium" style="font-family: 'JetBrains Mono', monospace;">
                                        {{ row.count }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section>
                    <h3 class="font-semibold text-ocean-900 mb-3">By Zone</h3>
                    <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                        <div v-if="byZone.length === 0" class="p-6 text-ocean-700 text-sm">
                            No work orders yet.
                        </div>
                        <table v-else class="min-w-full divide-y divide-ocean-100">
                            <tbody class="divide-y divide-ocean-100">
                                <tr v-for="row in byZone" :key="row.zone">
                                    <td class="px-4 py-2 text-sm text-ocean-700">{{ row.zone }}</td>
                                    <td class="px-4 py-2 text-sm text-ocean-900 text-right font-medium" style="font-family: 'JetBrains Mono', monospace;">
                                        {{ row.count }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AppShell>
</template>
