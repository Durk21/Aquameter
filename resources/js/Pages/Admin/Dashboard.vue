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
    <Head title="Admin Dashboard" />

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
                    :href="route('admin.work-orders.index')"
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-gradient-ocean text-white shadow-soft hover:shadow-glow transition-shadow"
                >
                    Work Orders
                </Link>
                <Link
                    :href="route('admin.complaints.index')"
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold border border-ocean-200 dark:border-white/10 text-ocean-700 dark:text-ocean-100 hover:bg-ocean-50 dark:hover:bg-white/5 transition-colors"
                >
                    Complaints
                </Link>
                <Link
                    :href="route('admin.bills.index')"
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold border border-ocean-200 dark:border-white/10 text-ocean-700 dark:text-ocean-100 hover:bg-ocean-50 dark:hover:bg-white/5 transition-colors"
                >
                    Bills
                </Link>
                <Link
                    :href="route('admin.staff.index')"
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold border border-ocean-200 dark:border-white/10 text-ocean-700 dark:text-ocean-100 hover:bg-ocean-50 dark:hover:bg-white/5 transition-colors"
                >
                    Staff
                </Link>
                <Link
                    :href="route('admin.meters.create')"
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold border border-ocean-200 dark:border-white/10 text-ocean-700 dark:text-ocean-100 hover:bg-ocean-50 dark:hover:bg-white/5 transition-colors"
                >
                    Register Meter
                </Link>
            </div>

            <section>
                <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">Accounts</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Active" :value="stats.accounts.active" accent="emerald" icon="users" />
                    <StatTile label="Overdue" :value="stats.accounts.overdue" accent="amber" icon="alert-triangle" />
                    <StatTile label="Defaulted" :value="stats.accounts.defaulted" accent="red" icon="x" />
                    <StatTile label="Disconnected" :value="stats.accounts.disconnected" accent="red" icon="zap" />
                </div>
            </section>

            <section>
                <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">Billing & Complaints</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <StatTile label="Outstanding (KES)" :value="stats.outstanding_amount" accent="amber" icon="credit-card" />
                    <StatTile label="Open Complaints" :value="stats.open_complaints" accent="ocean" icon="alert-triangle" />
                    <StatTile label="Awaiting Sign-Off" :value="stats.pending_sign_off" accent="amber" icon="clipboard-list" />
                </div>
            </section>

            <section>
                <h3 class="font-display font-semibold text-ocean-900 dark:text-white mb-3">Work Orders</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <StatTile label="Active Pipeline" :value="stats.active_pipeline" accent="indigo" icon="zap" />
                    <StatTile label="Completed This Week" :value="stats.completed_this_week" accent="emerald" icon="check" />
                    <StatTile label="Technicians (Zoned)" :value="`${stats.technicians_zoned} / ${stats.technicians}`" accent="ocean" icon="map-pin" />
                </div>
            </section>
        </div>
    </AppShell>
</template>
