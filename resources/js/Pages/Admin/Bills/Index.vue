<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import Card from "@/Components/Card.vue";
import Badge from "@/Components/Badge.vue";
import EmptyState from "@/Components/EmptyState.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

defineProps({
    bills: {
        type: Object,
        required: true,
    },
});

const isAdmin = computed(() => (usePage().props.auth.roles || []).includes("admin"));

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
                <EmptyState v-if="bills.data.length === 0" icon="clipboard-list" title="No bills yet" />

                <div v-else class="divide-y divide-ocean-100 dark:divide-white/5">
                    <div v-for="bill in bills.data" :key="bill.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-medium text-ocean-900 dark:text-white">{{ bill.customer_name }}</p>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400 font-mono mt-0.5">
                                {{ bill.account_number }}<span v-if="bill.phone"> · {{ bill.phone }}</span>
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="font-mono font-semibold text-ocean-900 dark:text-white">KES {{ bill.amount }}</p>
                                <p class="text-xs text-ocean-500 dark:text-neutral-400">Due {{ bill.due_date }}</p>
                            </div>
                            <Badge :tone="statusTones[bill.status] || 'ocean'">{{ bill.status_label }}</Badge>
                        </div>

                        <div class="w-full flex flex-wrap items-center justify-end gap-4 text-sm">
                            <Link
                                v-if="!bill.is_paid && isAdmin"
                                :href="route('admin.payments.create', bill.id)"
                                class="text-ocean-600 dark:text-ocean-400 hover:text-ocean-800 dark:hover:text-ocean-300 font-medium"
                            >
                                Record Payment
                            </Link>
                            <span v-else-if="!bill.is_paid" class="text-amber-600 dark:text-amber-400 font-medium">Unpaid</span>
                            <span v-else class="text-emerald-600 dark:text-emerald-400 font-medium">Paid</span>
                            <a
                                :href="route('bills.pdf', bill.id)"
                                class="text-ocean-600 dark:text-ocean-400 hover:text-ocean-800 dark:hover:text-ocean-300 font-medium"
                            >
                                Download PDF
                            </a>
                            <a
                                v-if="bill.payment_id"
                                :href="route('payments.receipt', bill.payment_id)"
                                class="text-ocean-600 dark:text-ocean-400 hover:text-ocean-800 dark:hover:text-ocean-300 font-medium"
                            >
                                Receipt
                            </a>
                        </div>
                    </div>
                </div>
            </Card>

            <Pagination :paginator="bills" />
        </div>
    </AppShell>
</template>
