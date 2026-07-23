<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import Card from "@/Components/Card.vue";
import Icon from "@/Components/Icon.vue";
import { Head, Link } from "@inertiajs/vue3";
import { onUnmounted, ref } from "vue";

const props = defineProps({
    transaction: {
        type: Object,
        required: true,
    },
});

const status = ref(props.transaction.status);
let pollTimer = null;

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

onUnmounted(stopPolling);

async function poll() {
    try {
        const response = await window.axios.get(route("customer.payment-transactions.status", props.transaction.id));
        status.value = response.data.status;

        if (status.value !== "pending") {
            stopPolling();
        }
    } catch (error) {
        // transient network hiccup — keep polling
    }
}

if (status.value === "pending") {
    poll();
    pollTimer = setInterval(poll, 3000);
}
</script>

<template>
    <Head title="Confirming Payment" />

    <AppShell>
        <div class="max-w-md mx-auto px-4 md:px-6 py-6">
            <Card class="text-center">
                <template v-if="status === 'pending'">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-gradient-ocean flex items-center justify-center shadow-soft mb-4">
                        <Icon name="loader" :size="22" class="text-white animate-spin" />
                    </div>
                    <p class="font-medium text-ocean-900 dark:text-white">Confirming your payment</p>
                    <p class="text-sm text-ocean-500 dark:text-neutral-400 mt-1">This only takes a moment.</p>
                </template>

                <template v-else-if="status === 'completed'">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-500 flex items-center justify-center shadow-soft mb-4">
                        <Icon name="check" :size="22" class="text-white" />
                    </div>
                    <p class="font-medium text-ocean-900 dark:text-white">Payment received</p>
                    <p class="text-sm text-ocean-500 dark:text-neutral-400 mt-1">Thank you — your bill is now marked as paid.</p>
                </template>

                <template v-else>
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-red-500 flex items-center justify-center shadow-soft mb-4">
                        <Icon name="x" :size="22" class="text-white" />
                    </div>
                    <p class="font-medium text-ocean-900 dark:text-white">Payment not completed</p>
                    <p class="text-sm text-ocean-500 dark:text-neutral-400 mt-1">You can try again from the bill.</p>
                </template>

                <Link
                    :href="route('customer.bills.index')"
                    class="inline-block mt-4 px-4 py-2 rounded-lg text-sm font-medium bg-gradient-ocean text-white shadow-soft hover:shadow-glow"
                >
                    Back to Bills
                </Link>
            </Card>
        </div>
    </AppShell>
</template>
