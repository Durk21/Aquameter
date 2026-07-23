<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import Card from "@/Components/Card.vue";
import Badge from "@/Components/Badge.vue";
import EmptyState from "@/Components/EmptyState.vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    bills: {
        type: Array,
        required: true,
    },
});

const statusTones = {
    pending: "ocean",
    paid: "emerald",
    overdue: "amber",
    defaulted: "red",
};
</script>

<template>
    <Head title="Bills" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Bills
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Bills
            </h1>

            <Card :padded="false">
                <EmptyState
                    v-if="bills.length === 0"
                    icon="clipboard-list"
                    title="No bills yet"
                    description="A bill is generated automatically after your first meter reading is followed by a second one."
                />

                <div v-else class="divide-y divide-ocean-100 dark:divide-white/5">
                    <div v-for="bill in bills" :key="bill.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-mono font-semibold text-ocean-900 dark:text-white">KES {{ bill.amount }}</p>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-0.5">
                                {{ bill.units_consumed }} units · Due {{ bill.due_date }}
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <Badge :tone="statusTones[bill.status] || 'ocean'">{{ bill.status_label }}</Badge>
                            <Link
                                v-if="!bill.payment_id"
                                :href="route('customer.bills.pay', bill.id)"
                                class="text-sm text-white bg-gradient-ocean px-3 py-1.5 rounded-lg font-medium shadow-soft hover:shadow-glow transition-all"
                            >
                                Pay Now
                            </Link>
                            <a
                                :href="route('bills.pdf', bill.id)"
                                class="text-sm text-ocean-600 dark:text-ocean-400 hover:text-ocean-800 dark:hover:text-ocean-300 font-medium"
                            >
                                PDF
                            </a>
                            <a
                                v-if="bill.payment_id"
                                :href="route('payments.receipt', bill.payment_id)"
                                class="text-sm text-ocean-600 dark:text-ocean-400 hover:text-ocean-800 dark:hover:text-ocean-300 font-medium"
                            >
                                Receipt
                            </a>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppShell>
</template>
