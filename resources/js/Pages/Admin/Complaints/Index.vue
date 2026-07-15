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
    resolved: "bg-emerald-100 text-emerald-800",
    rejected: "bg-red-100 text-red-800",
};
</script>

<template>
    <Head title="Complaints" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Complaints
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Complaints
            </h1>

            <div v-if="complaints.length === 0" class="bg-white rounded-lg border border-ocean-100 p-6 text-ocean-700">
                No complaints have been submitted.
            </div>

            <div v-else class="space-y-4">
                <Link
                    v-for="complaint in complaints"
                    :key="complaint.id"
                    :href="route('admin.complaints.show', complaint.id)"
                    class="block bg-white rounded-lg border border-ocean-100 p-5 hover:border-ocean-300 transition-colors"
                >
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <h3 class="font-semibold text-ocean-900">{{ complaint.subject }}</h3>
                            <p class="text-xs text-ocean-500 mt-0.5">
                                {{ complaint.customer_name }} · {{ complaint.account_number }}
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                            :class="statusStyles[complaint.status] || 'bg-ocean-100 text-ocean-800'"
                        >
                            {{ complaint.status_label }}
                        </span>
                    </div>
                    <p class="text-sm text-ocean-700 line-clamp-2">{{ complaint.description }}</p>
                    <p v-if="complaint.bill_amount" class="text-xs text-ocean-500 mt-2" style="font-family: 'JetBrains Mono', monospace;">
                        Related bill: KES {{ complaint.bill_amount }}
                    </p>
                </Link>
            </div>
        </div>
    </AppShell>
</template>
