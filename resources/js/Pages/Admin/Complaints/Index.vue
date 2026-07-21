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
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Complaints
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Complaints
            </h1>

            <div v-if="complaints.length === 0" class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6 text-ocean-600 dark:text-neutral-400">
                No complaints have been submitted.
            </div>

            <div v-else class="space-y-4">
                <Link
                    v-for="complaint in complaints"
                    :key="complaint.id"
                    :href="route('admin.complaints.show', complaint.id)"
                    class="block bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft hover:shadow-elevated hover:border-ocean-300 dark:hover:border-white/20 hover:-translate-y-0.5 transition-all duration-200 p-5"
                >
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <h3 class="font-semibold text-ocean-900 dark:text-white">{{ complaint.subject }}</h3>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-0.5">
                                {{ complaint.customer_name }} · {{ complaint.account_number }}<span v-if="complaint.phone"> · {{ complaint.phone }}</span>
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                            :class="statusStyles[complaint.status] || 'bg-ocean-100 text-ocean-800'"
                        >
                            {{ complaint.status_label }}
                        </span>
                    </div>
                    <p class="text-sm text-ocean-700 dark:text-neutral-300 line-clamp-2">{{ complaint.description }}</p>
                    <p v-if="complaint.bill_amount" class="text-xs text-ocean-500 dark:text-neutral-400 mt-2 font-mono">
                        Related bill: KES {{ complaint.bill_amount }}
                    </p>
                </Link>
            </div>
        </div>
    </AppShell>
</template>
