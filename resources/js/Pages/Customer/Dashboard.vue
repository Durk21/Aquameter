<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import Card from "@/Components/Card.vue";
import StatTile from "@/Components/StatTile.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    activeWorkOrder: {
        type: Object,
        default: null,
    },
    outages: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: null,
    },
});

const disputing = ref(false);

const form = useForm({
    reason: "",
});

const submitDispute = () => {
    form.post(route("customer.work-orders.dispute", props.activeWorkOrder.id), {
        onSuccess: () => {
            disputing.value = false;
            form.reset();
        },
    });
};

const bannerStyles = {
    notice_sent: "bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20 text-amber-900 dark:text-amber-200",
    approved: "bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-900 dark:text-red-200",
    disputed: "bg-ocean-50 dark:bg-white/5 border-ocean-200 dark:border-white/10 text-ocean-900 dark:text-ocean-100",
};

const accountStatusStyles = {
    active: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    overdue: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
    defaulted: "bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300",
    disconnected: "bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300",
};
</script>

<template>
    <Head title="Customer Dashboard" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Dashboard
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white">
                Dashboard
            </h1>

            <div
                v-for="outage in outages"
                :key="outage.id"
                class="rounded-2xl border p-5"
                :class="outage.status === 'active' ? 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-900 dark:text-red-200' : 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20 text-amber-900 dark:text-amber-200'"
            >
                <p class="font-semibold mb-1">
                    {{ outage.status === 'active' ? 'Ongoing outage' : 'Scheduled outage' }}: {{ outage.title }}
                </p>
                <p class="text-sm">{{ outage.description }}</p>
                <p class="text-xs mt-2 opacity-80">
                    {{ outage.zone || 'All zones' }} · Starts {{ outage.starts_at }}<span v-if="outage.ends_at"> · Ends {{ outage.ends_at }}</span>
                </p>
            </div>

            <div
                v-if="activeWorkOrder"
                class="rounded-2xl border p-5"
                :class="bannerStyles[activeWorkOrder.status] || bannerStyles.disputed"
            >
                <p class="font-semibold mb-1">{{ activeWorkOrder.type_label }} notice: {{ activeWorkOrder.status_label }}</p>

                <p v-if="activeWorkOrder.status === 'notice_sent'" class="text-sm">
                    Your account has a defaulted bill and is scheduled for disconnection unless settled or disputed
                    by <strong>{{ activeWorkOrder.notice_deadline }}</strong>.
                </p>
                <p v-else-if="activeWorkOrder.status === 'approved'" class="text-sm">
                    Your disconnection notice has been reviewed and approved. A technician may be dispatched at any time
                    unless the outstanding balance is settled.
                </p>
                <p v-else-if="activeWorkOrder.status === 'disputed'" class="text-sm">
                    Your dispute has been submitted and is paused pending admin review.
                </p>

                <div v-if="['notice_sent', 'approved'].includes(activeWorkOrder.status)" class="mt-3">
                    <button
                        v-if="!disputing"
                        @click="disputing = true"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-current"
                    >
                        Dispute This Notice
                    </button>

                    <form v-else @submit.prevent="submitDispute" class="space-y-2">
                        <textarea
                            v-model="form.reason"
                            rows="3"
                            maxlength="2000"
                            required
                            placeholder="Explain why this notice should be paused for review"
                            class="block w-full text-sm rounded-lg border-ocean-300 dark:border-white/20 dark:bg-neutral-900 focus:border-ocean-500 focus:ring-ocean-500 shadow-sm"
                        ></textarea>
                        <div class="flex gap-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-gradient-ocean text-white shadow-soft disabled:opacity-50"
                            >
                                Submit Dispute
                            </button>
                            <button
                                type="button"
                                @click="disputing = false"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-current"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <Card v-if="stats">
                <div class="flex items-center justify-between mb-1">
                    <p class="font-display font-semibold text-ocean-900 dark:text-white">{{ stats.account_number }}</p>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="accountStatusStyles[stats.status] || accountStatusStyles.active"
                    >
                        {{ stats.status_label }}
                    </span>
                </div>
                <p class="text-sm text-ocean-500 dark:text-neutral-400">{{ stats.zone }}</p>
            </Card>

            <section v-if="stats">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatTile
                        label="Outstanding (KES)"
                        :value="stats.outstanding_amount"
                        :accent="Number(stats.outstanding_amount) > 0 ? 'amber' : 'emerald'"
                        icon="credit-card"
                    />
                    <StatTile label="Next Due" :value="stats.next_due_date || '—'" accent="ocean" icon="calendar" />
                    <StatTile label="Meters" :value="stats.meter_count" accent="ocean" icon="gauge" />
                    <StatTile label="Open Requests" :value="stats.open_complaints + stats.open_requests" accent="indigo" icon="alert-triangle" />
                </div>
            </section>

            <Card>
                <p class="text-ocean-700 dark:text-neutral-300">
                    Welcome back. Your account, meter readings, and billing appear above.
                </p>
                <div class="flex flex-wrap gap-2 mt-4">
                    <Link
                        :href="route('customer.leak-reports.create')"
                        class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-gradient-ocean text-white shadow-soft hover:shadow-glow transition-shadow"
                    >
                        Report a Leak
                    </Link>
                    <Link
                        :href="route('customer.service-requests.create')"
                        class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold border border-ocean-200 dark:border-white/10 text-ocean-700 dark:text-ocean-100 hover:bg-ocean-50 dark:hover:bg-white/5 transition-colors"
                    >
                        Request Service
                    </Link>
                </div>
            </Card>
        </div>
    </AppShell>
</template>
