<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    activeWorkOrder: {
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
    notice_sent: "bg-amber-50 border-amber-200 text-amber-900",
    approved: "bg-red-50 border-red-200 text-red-900",
    disputed: "bg-ocean-50 border-ocean-200 text-ocean-900",
};
</script>

<template>
    <Head title="Customer Dashboard" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Dashboard
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-6">
            <div
                v-if="activeWorkOrder"
                class="rounded-lg border p-5"
                :class="bannerStyles[activeWorkOrder.status] || 'bg-ocean-50 border-ocean-200 text-ocean-900'"
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
                        class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-current"
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
                            class="block w-full text-sm border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
                        ></textarea>
                        <div class="flex gap-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700 disabled:opacity-50"
                            >
                                Submit Dispute
                            </button>
                            <button
                                type="button"
                                @click="disputing = false"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-current"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-ocean-100 p-6">
                <p class="text-ocean-700">
                    Welcome back. Your account, meter readings, and billing will appear here as Aquameter grows.
                </p>
                <div class="flex flex-wrap gap-2 mt-4">
                    <Link
                        :href="route('customer.leak-reports.create')"
                        class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                    >
                        Report a Leak
                    </Link>
                    <Link
                        :href="route('customer.service-requests.create')"
                        class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                    >
                        Request Service
                    </Link>
                </div>
            </div>
        </div>
    </AppShell>
</template>
