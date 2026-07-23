<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import Card from "@/Components/Card.vue";
import Icon from "@/Components/Icon.vue";
import InlineDroplet from "@/Components/InlineDroplet.vue";
import { Head, Link } from "@inertiajs/vue3";
import { onUnmounted, ref } from "vue";

const props = defineProps({
    bill: {
        type: Object,
        required: true,
    },
    pendingTransaction: {
        type: Object,
        default: null,
    },
});

// "select" | "mpesa-form" | "polling" | "cash-logged" | "failed" | "completed"
const view = ref(props.pendingTransaction ? (props.pendingTransaction.method === "cash" ? "cash-logged" : "polling") : "select");
const transaction = ref(props.pendingTransaction);
const phone = ref("");
const errorText = ref("");
const sending = ref(false);
let pollTimer = null;

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

onUnmounted(stopPolling);

function startPolling() {
    stopPolling();
    pollTimer = setInterval(async () => {
        if (!transaction.value) return;

        try {
            const response = await window.axios.get(route("customer.payment-transactions.status", transaction.value.id));
            transaction.value = { ...transaction.value, ...response.data };

            if (response.data.status === "completed") {
                stopPolling();
                view.value = "completed";
            } else if (response.data.status === "failed") {
                stopPolling();
                view.value = "failed";
                errorText.value = response.data.failure_reason || "The payment was not completed.";
            }
        } catch (error) {
            // transient network hiccup — keep polling
        }
    }, 3000);
}

async function payWithMpesa() {
    const digits = phone.value.trim();
    if (!digits) return;

    errorText.value = "";
    sending.value = true;

    try {
        const response = await window.axios.post(route("customer.bills.pay.mpesa", props.bill.id), { phone: digits });
        transaction.value = response.data.transaction;

        if (transaction.value.status === "failed") {
            view.value = "failed";
            errorText.value = transaction.value.failure_reason || "M-Pesa declined the request.";
        } else {
            view.value = "polling";
            startPolling();
        }
    } catch (error) {
        errorText.value = error.response?.data?.errors?.phone?.[0] || "Couldn't start the M-Pesa payment — please try again.";
    } finally {
        sending.value = false;
    }
}

async function payWithBank() {
    errorText.value = "";
    sending.value = true;

    try {
        const response = await window.axios.post(route("customer.bills.pay.bank", props.bill.id));

        if (response.data.redirect_url) {
            window.location.href = response.data.redirect_url;
            return;
        }

        view.value = "failed";
        errorText.value = response.data.transaction?.failure_reason || "Couldn't start the bank payment — please try again.";
    } catch (error) {
        errorText.value = "Couldn't start the bank payment — please try again.";
    } finally {
        sending.value = false;
    }
}

async function payWithCash() {
    errorText.value = "";
    sending.value = true;

    try {
        const response = await window.axios.post(route("customer.bills.pay.cash", props.bill.id));
        transaction.value = response.data.transaction;
        view.value = "cash-logged";
        startPolling();
    } catch (error) {
        errorText.value = "Couldn't log your cash payment intent — please try again.";
    } finally {
        sending.value = false;
    }
}

function tryAgain() {
    stopPolling();
    transaction.value = null;
    errorText.value = "";
    phone.value = "";
    view.value = "select";
}

if (view.value === "polling" || view.value === "cash-logged") {
    startPolling();
}
</script>

<template>
    <Head title="Pay Bill" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Pay Bill
            </h2>
        </template>

        <div class="max-w-md mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Pay Bill
            </h1>

            <Card class="text-center mb-6">
                <p class="text-xs text-ocean-500 dark:text-neutral-400 uppercase tracking-wider">Amount Due</p>
                <p class="font-mono font-bold text-3xl text-ocean-900 dark:text-white mt-1">KES {{ bill.amount }}</p>
                <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-1">Due {{ bill.due_date }}</p>
            </Card>

            <!-- Method selection -->
            <div v-if="view === 'select'" class="space-y-3">
                <button
                    type="button"
                    @click="view = 'mpesa-form'"
                    class="w-full flex items-center gap-3 p-4 rounded-2xl border border-ocean-100 dark:border-white/10 bg-white dark:bg-neutral-900 hover:border-ocean-300 dark:hover:border-white/20 hover:shadow-soft transition-all text-left"
                >
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center shrink-0">
                        <Icon name="phone" :size="18" class="text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-ocean-900 dark:text-white">M-Pesa</p>
                        <p class="text-xs text-ocean-500 dark:text-neutral-400">Pay instantly with an STK push to your phone</p>
                    </div>
                    <Icon name="arrow-right" :size="18" class="text-ocean-300 dark:text-neutral-600" />
                </button>

                <button
                    type="button"
                    @click="payWithBank"
                    :disabled="sending"
                    class="w-full flex items-center gap-3 p-4 rounded-2xl border border-ocean-100 dark:border-white/10 bg-white dark:bg-neutral-900 hover:border-ocean-300 dark:hover:border-white/20 hover:shadow-soft transition-all text-left disabled:opacity-50"
                >
                    <div class="w-10 h-10 rounded-xl bg-ocean-100 dark:bg-ocean-500/10 flex items-center justify-center shrink-0">
                        <Icon name="landmark" :size="18" class="text-ocean-600 dark:text-ocean-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-ocean-900 dark:text-white">Bank / Card</p>
                        <p class="text-xs text-ocean-500 dark:text-neutral-400">Pay via bank transfer or card through Pesapal</p>
                    </div>
                    <InlineDroplet v-if="sending" :size="16" class="text-ocean-400" />
                    <Icon v-else name="arrow-right" :size="18" class="text-ocean-300 dark:text-neutral-600" />
                </button>

                <button
                    type="button"
                    @click="payWithCash"
                    :disabled="sending"
                    class="w-full flex items-center gap-3 p-4 rounded-2xl border border-ocean-100 dark:border-white/10 bg-white dark:bg-neutral-900 hover:border-ocean-300 dark:hover:border-white/20 hover:shadow-soft transition-all text-left disabled:opacity-50"
                >
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center shrink-0">
                        <Icon name="banknote" :size="18" class="text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-ocean-900 dark:text-white">Cash</p>
                        <p class="text-xs text-ocean-500 dark:text-neutral-400">Log your intent, then pay in person at any office</p>
                    </div>
                    <InlineDroplet v-if="sending" :size="16" class="text-ocean-400" />
                    <Icon v-else name="arrow-right" :size="18" class="text-ocean-300 dark:text-neutral-600" />
                </button>

                <p v-if="errorText" class="text-xs text-red-600 dark:text-red-400 text-center pt-1">{{ errorText }}</p>
            </div>

            <!-- M-Pesa phone entry -->
            <Card v-else-if="view === 'mpesa-form'">
                <label class="block text-sm font-medium text-ocean-700 dark:text-neutral-300 mb-2">
                    M-Pesa phone number
                </label>
                <input
                    v-model="phone"
                    type="tel"
                    placeholder="07XXXXXXXX"
                    class="w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm text-sm"
                    @keydown.enter.prevent="payWithMpesa"
                />
                <p v-if="errorText" class="text-xs text-red-600 dark:text-red-400 mt-2">{{ errorText }}</p>
                <div class="flex gap-2 mt-4">
                    <button
                        type="button"
                        @click="view = 'select'"
                        class="flex-1 px-4 py-2 rounded-lg text-sm font-medium text-ocean-600 dark:text-neutral-300 border border-ocean-200 dark:border-white/10 hover:bg-ocean-50 dark:hover:bg-white/5"
                    >
                        Back
                    </button>
                    <button
                        type="button"
                        @click="payWithMpesa"
                        :disabled="sending || !phone.trim()"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-gradient-ocean text-white shadow-soft hover:shadow-glow disabled:opacity-50"
                    >
                        <InlineDroplet v-if="sending" :size="14" />
                        Send STK Push
                    </button>
                </div>
            </Card>

            <!-- Polling: waiting on M-Pesa / Pesapal confirmation -->
            <Card v-else-if="view === 'polling'" class="text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-gradient-ocean flex items-center justify-center shadow-soft mb-4">
                    <Icon name="loader" :size="22" class="text-white animate-spin" />
                </div>
                <p class="font-medium text-ocean-900 dark:text-white">Check your phone</p>
                <p class="text-sm text-ocean-500 dark:text-neutral-400 mt-1">
                    Enter your M-Pesa PIN when prompted to complete the payment.
                </p>
            </Card>

            <!-- Cash intent logged -->
            <Card v-else-if="view === 'cash-logged'" class="text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-amber-500 flex items-center justify-center shadow-soft mb-4">
                    <Icon name="banknote" :size="22" class="text-white" />
                </div>
                <p class="font-medium text-ocean-900 dark:text-white">Cash payment logged</p>
                <p class="text-sm text-ocean-500 dark:text-neutral-400 mt-1">
                    Please pay at any Aquameter office. Your bill will be marked paid once staff confirm receipt.
                </p>
            </Card>

            <!-- Failed -->
            <Card v-else-if="view === 'failed'" class="text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-red-500 flex items-center justify-center shadow-soft mb-4">
                    <Icon name="x" :size="22" class="text-white" />
                </div>
                <p class="font-medium text-ocean-900 dark:text-white">Payment not completed</p>
                <p class="text-sm text-ocean-500 dark:text-neutral-400 mt-1">{{ errorText }}</p>
                <button
                    type="button"
                    @click="tryAgain"
                    class="mt-4 px-4 py-2 rounded-lg text-sm font-medium bg-gradient-ocean text-white shadow-soft hover:shadow-glow"
                >
                    Try Again
                </button>
            </Card>

            <!-- Completed -->
            <Card v-else-if="view === 'completed'" class="text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-500 flex items-center justify-center shadow-soft mb-4">
                    <Icon name="check" :size="22" class="text-white" />
                </div>
                <p class="font-medium text-ocean-900 dark:text-white">Payment received</p>
                <p class="text-sm text-ocean-500 dark:text-neutral-400 mt-1">Thank you — your bill is now marked as paid.</p>
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
