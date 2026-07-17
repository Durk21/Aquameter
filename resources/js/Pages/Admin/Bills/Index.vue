<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    bills: {
        type: Array,
        required: true,
    },
});

const statusStyles = {
    pending: "bg-ocean-100 text-ocean-800",
    paid: "bg-emerald-100 text-emerald-800",
    overdue: "bg-amber-100 text-amber-800",
    defaulted: "bg-red-100 text-red-800",
};
</script>

<template>
    <Head title="Bills" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Bills
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Bills
            </h1>

            <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                <div v-if="bills.length === 0" class="p-6 text-ocean-700">
                    No bills yet.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ocean-100">
                        <thead class="bg-ocean-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider">Amount (KES)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider"></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ocean-100">
                            <tr v-for="bill in bills" :key="bill.id">
                                <td class="px-6 py-4 text-sm text-ocean-900">
                                    {{ bill.customer_name }}
                                    <span class="block text-xs text-ocean-500" style="font-family: 'JetBrains Mono', monospace;">
                                        {{ bill.account_number }}<span v-if="bill.phone"> · {{ bill.phone }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-ocean-900" style="font-family: 'JetBrains Mono', monospace;">
                                    {{ bill.amount }}
                                </td>
                                <td class="px-6 py-4 text-sm text-ocean-700">{{ bill.due_date }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="statusStyles[bill.status] || 'bg-ocean-100 text-ocean-800'"
                                    >
                                        {{ bill.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <Link
                                        v-if="!bill.is_paid"
                                        :href="route('admin.payments.create', bill.id)"
                                        class="text-ocean-600 hover:text-ocean-800 font-medium text-sm"
                                    >
                                        Record Payment
                                    </Link>
                                    <span v-else class="text-emerald-600 text-sm font-medium">Paid</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <a
                                        :href="route('bills.pdf', bill.id)"
                                        class="text-ocean-600 hover:text-ocean-800 font-medium"
                                    >
                                        Download PDF
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppShell>
</template>
