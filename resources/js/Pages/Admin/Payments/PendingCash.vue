<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import Card from "@/Components/Card.vue";
import EmptyState from "@/Components/EmptyState.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, useForm } from "@inertiajs/vue3";

defineProps({
    transactions: {
        type: Object,
        required: true,
    },
});

const forms = {};

function confirm(id) {
    if (!forms[id]) {
        forms[id] = useForm({});
    }

    forms[id].patch(route("admin.payment-transactions.confirm", id));
}
</script>

<template>
    <Head title="Pending Cash Payments" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Pending Cash Payments
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Pending Cash Payments
            </h1>

            <Card :padded="false">
                <EmptyState
                    v-if="transactions.data.length === 0"
                    icon="banknote"
                    title="No pending cash payments"
                    description="Cash-payment intents logged by customers will appear here for confirmation."
                />

                <div v-else class="divide-y divide-ocean-100 dark:divide-white/5">
                    <div v-for="t in transactions.data" :key="t.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-medium text-ocean-900 dark:text-white">{{ t.customer_name }}</p>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400 font-mono mt-0.5">
                                {{ t.account_number }} · Bill #{{ t.bill_id }}
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <p class="font-mono font-semibold text-ocean-900 dark:text-white">KES {{ t.amount }}</p>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400">{{ t.created_at }}</p>
                            <button
                                type="button"
                                @click="confirm(t.id)"
                                class="text-sm text-white bg-gradient-ocean px-3 py-1.5 rounded-lg font-medium shadow-soft hover:shadow-glow transition-all"
                            >
                                Confirm Received
                            </button>
                        </div>
                    </div>
                </div>
            </Card>

            <Pagination :paginator="transactions" class="mt-4" />
        </div>
    </AppShell>
</template>
