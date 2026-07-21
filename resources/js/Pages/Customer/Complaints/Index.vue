<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    complaints: {
        type: Array,
        required: true,
    },
});

const statusStyles = {
    submitted: "bg-ocean-100 text-ocean-800",
    under_review: "bg-amber-100 text-amber-800",
    approved: "bg-indigo-100 text-indigo-800",
    resolved: "bg-emerald-100 text-emerald-800",
    rejected: "bg-red-100 text-red-800",
};
</script>

<template>
    <Head title="Complaints" />

    <AppShell>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                    Complaints
                </h2>
                <Link
                    :href="route('customer.complaints.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft hover:shadow-glow px-4 py-2 rounded-lg transition-shadow"
                >
                    Submit a Complaint
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <div class="flex md:hidden items-center justify-between mb-4">
                <h1 class="font-display text-lg font-semibold text-ocean-900 dark:text-white">
                    Complaints
                </h1>
                <Link
                    :href="route('customer.complaints.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft px-3 py-1.5 rounded-lg"
                >
                    Submit
                </Link>
            </div>

            <div v-if="complaints.length === 0" class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6 text-ocean-600 dark:text-neutral-400">
                You haven't submitted any complaints yet.
            </div>

            <div v-else class="space-y-4">
                <div v-for="complaint in complaints" :key="complaint.id" class="bg-white rounded-lg border border-ocean-100 p-5">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h3 class="font-semibold text-ocean-900 dark:text-white">{{ complaint.subject }}</h3>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                            :class="statusStyles[complaint.status] || 'bg-ocean-100 text-ocean-800'"
                        >
                            {{ complaint.status_label }}
                        </span>
                    </div>
                    <p class="text-sm text-ocean-700 dark:text-neutral-300 mb-2">{{ complaint.description }}</p>
                    <p v-if="complaint.bill_amount" class="text-xs text-ocean-500 dark:text-neutral-400 mb-2 font-mono">
                        Related bill: KES {{ complaint.bill_amount }}
                    </p>
                    <div v-if="complaint.resolution_notes" class="mt-3 bg-ocean-50 rounded-md px-3 py-2 text-sm text-ocean-700 dark:text-neutral-300">
                        <span class="font-medium">Resolution:</span> {{ complaint.resolution_notes }}
                    </div>
                    <p class="text-xs text-ocean-400 dark:text-neutral-500 mt-2">Submitted {{ complaint.created_at }}</p>
                </div>
            </div>
        </div>
    </AppShell>
</template>
