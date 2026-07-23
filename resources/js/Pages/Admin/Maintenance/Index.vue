<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    schedules: {
        type: Array,
        required: true,
    },
});

const statusStyles = {
    approved: "bg-ocean-100 text-ocean-800",
    claimed: "bg-indigo-100 text-indigo-800",
    completed: "bg-emerald-100 text-emerald-800",
    cancelled: "bg-neutral-200 text-neutral-700",
};

const today = new Date().toISOString().split("T")[0];
const weekAhead = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split("T")[0];

const overdue = computed(() => props.schedules.filter((s) => s.is_overdue));
const completed = computed(() => props.schedules.filter((s) => s.status === "completed" || s.status === "cancelled"));
const thisWeek = computed(() => props.schedules.filter((s) =>
    !s.is_overdue && s.status !== "completed" && s.status !== "cancelled" && s.scheduled_for >= today && s.scheduled_for <= weekAhead
));
const upcoming = computed(() => props.schedules.filter((s) =>
    !s.is_overdue && s.status !== "completed" && s.status !== "cancelled" && s.scheduled_for > weekAhead
));
</script>

<template>
    <Head title="Maintenance Schedule" />

    <AppShell>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                    Maintenance Schedule
                </h2>
                <Link
                    :href="route('admin.maintenance.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft hover:shadow-glow px-4 py-2 rounded-lg transition-shadow"
                >
                    Schedule Maintenance
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6 space-y-8">
            <div class="flex md:hidden items-center justify-between">
                <h1 class="font-display text-lg font-semibold text-ocean-900 dark:text-white">
                    Maintenance Schedule
                </h1>
                <Link
                    :href="route('admin.maintenance.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft px-3 py-1.5 rounded-lg"
                >
                    Schedule
                </Link>
            </div>

            <section v-if="overdue.length > 0">
                <h3 class="font-semibold text-red-700 mb-3">Overdue</h3>
                <div class="bg-white dark:bg-red-500/5 rounded-2xl border border-red-200 dark:border-red-500/20 shadow-soft overflow-hidden divide-y divide-red-100 dark:divide-red-500/10">
                    <div v-for="s in overdue" :key="s.id" class="flex items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-medium text-ocean-900 dark:text-white">{{ s.meter_number }} · {{ s.customer_name }} ({{ s.account_number }})</p>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-0.5">{{ s.zone }} · Was due {{ s.scheduled_for }}<span v-if="s.phone"> · {{ s.phone }}</span></p>
                            <p class="text-sm text-ocean-700 dark:text-neutral-300 mt-1">{{ s.description }}</p>
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                            :class="statusStyles[s.status] || 'bg-ocean-100 text-ocean-800'"
                        >
                            {{ s.status_label }}
                        </span>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="font-semibold text-ocean-900 dark:text-white mb-3">This Week</h3>
                <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft overflow-hidden">
                    <div v-if="thisWeek.length === 0" class="p-6 text-ocean-600 dark:text-neutral-400 text-sm">
                        Nothing scheduled in the next 7 days.
                    </div>
                    <div v-else class="divide-y divide-ocean-100 dark:divide-white/5">
                        <div v-for="s in thisWeek" :key="s.id" class="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p class="font-medium text-ocean-900 dark:text-white">{{ s.meter_number }} · {{ s.customer_name }} ({{ s.account_number }})</p>
                                <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-0.5">{{ s.zone }} · {{ s.scheduled_for }}<span v-if="s.phone"> · {{ s.phone }}</span></p>
                                <p class="text-sm text-ocean-700 dark:text-neutral-300 mt-1">{{ s.description }}</p>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                                :class="statusStyles[s.status] || 'bg-ocean-100 text-ocean-800'"
                            >
                                {{ s.status_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="font-semibold text-ocean-900 dark:text-white mb-3">Upcoming</h3>
                <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft overflow-hidden">
                    <div v-if="upcoming.length === 0" class="p-6 text-ocean-600 dark:text-neutral-400 text-sm">
                        Nothing scheduled beyond the next 7 days.
                    </div>
                    <div v-else class="divide-y divide-ocean-100 dark:divide-white/5">
                        <div v-for="s in upcoming" :key="s.id" class="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p class="font-medium text-ocean-900 dark:text-white">{{ s.meter_number }} · {{ s.customer_name }} ({{ s.account_number }})</p>
                                <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-0.5">{{ s.zone }} · {{ s.scheduled_for }}<span v-if="s.phone"> · {{ s.phone }}</span></p>
                                <p class="text-sm text-ocean-700 dark:text-neutral-300 mt-1">{{ s.description }}</p>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                                :class="statusStyles[s.status] || 'bg-ocean-100 text-ocean-800'"
                            >
                                {{ s.status_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="completed.length > 0">
                <h3 class="font-semibold text-ocean-900 dark:text-white mb-3">Closed</h3>
                <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft overflow-hidden divide-y divide-ocean-100 dark:divide-white/5">
                    <div v-for="s in completed" :key="s.id" class="flex items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-medium text-ocean-900 dark:text-white">{{ s.meter_number }} · {{ s.customer_name }} ({{ s.account_number }})</p>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-0.5">{{ s.zone }} · was scheduled {{ s.scheduled_for }}</p>
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                            :class="statusStyles[s.status] || 'bg-ocean-100 text-ocean-800'"
                        >
                            {{ s.status_label }}
                        </span>
                    </div>
                </div>
            </section>
        </div>
    </AppShell>
</template>
