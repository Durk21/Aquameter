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
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Dashboard
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-8">
            <div class="flex flex-wrap gap-2">
                <Link
                    :href="route('admin.work-orders.index')"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                >
                    Work Orders
                </Link>
                <Link
                    :href="route('admin.complaints.index')"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                >
                    Complaints
                </Link>
                <Link
                    :href="route('admin.bills.index')"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                >
                    Bills
                </Link>
                <Link
                    :href="route('admin.technicians.index')"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                >
                    Technicians
                </Link>
                <Link
                    :href="route('admin.meters.create')"
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                >
                    Register Meter
                </Link>
            </div>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Accounts</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile label="Active" :value="stats.accounts.active" accent="emerald" />
                    <StatTile label="Overdue" :value="stats.accounts.overdue" accent="amber" />
                    <StatTile label="Defaulted" :value="stats.accounts.defaulted" accent="red" />
                    <StatTile label="Disconnected" :value="stats.accounts.disconnected" accent="red" />
                </div>
            </section>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Billing & Complaints</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <StatTile label="Outstanding (KES)" :value="stats.outstanding_amount" accent="amber" />
                    <StatTile label="Open Complaints" :value="stats.open_complaints" accent="ocean" />
                    <StatTile label="Awaiting Sign-Off" :value="stats.pending_sign_off" accent="amber" />
                </div>
            </section>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Work Orders</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <StatTile label="Active Pipeline" :value="stats.active_pipeline" accent="indigo" />
                    <StatTile label="Completed This Week" :value="stats.completed_this_week" accent="emerald" />
                    <StatTile label="Technicians (Zoned)" :value="`${stats.technicians_zoned} / ${stats.technicians}`" accent="ocean" />
                </div>
            </section>
        </div>
    </AppShell>
</template>
