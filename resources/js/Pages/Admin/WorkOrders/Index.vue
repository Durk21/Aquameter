<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import PhotoGallery from "@/Components/PhotoGallery.vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    defaultedAccounts: {
        type: Array,
        required: true,
    },
    pipeline: {
        type: Array,
        required: true,
    },
    history: {
        type: Array,
        required: true,
    },
    noticeDays: {
        type: Number,
        required: true,
    },
});

const statusStyles = {
    notice_sent: "bg-amber-100 text-amber-800",
    approved: "bg-ocean-100 text-ocean-800",
    disputed: "bg-red-100 text-red-800",
    claimed: "bg-indigo-100 text-indigo-800",
    completed: "bg-emerald-100 text-emerald-800",
    cancelled: "bg-neutral-200 text-neutral-700",
};
</script>

<template>
    <Head title="Work Orders" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Work Orders
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-8">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Work Orders
            </h1>

            <div v-if="$page.props.flash?.status" class="text-sm text-ocean-700 bg-ocean-50 rounded-md px-3 py-2">
                {{ $page.props.flash.status }}
            </div>
            <div v-if="$page.props.errors?.work_order" class="text-sm text-red-700 bg-red-50 rounded-md px-3 py-2">
                {{ $page.props.errors.work_order }}
            </div>

            <!-- Defaulted accounts awaiting a notice -->
            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Defaulted Accounts</h3>
                <p class="text-sm text-ocean-500 mb-3">
                    Disconnection is never automatic. Issuing a notice starts a {{ noticeDays }}-day clock before sign-off is possible.
                </p>
                <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                    <div v-if="defaultedAccounts.length === 0" class="p-6 text-ocean-700 text-sm">
                        No defaulted accounts awaiting a disconnection notice.
                    </div>
                    <div v-else class="divide-y divide-ocean-100">
                        <div v-for="account in defaultedAccounts" :key="account.id" class="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p class="font-medium text-ocean-900">{{ account.customer_name }} · {{ account.account_number }}</p>
                                <p class="text-xs text-ocean-500 mt-0.5">
                                    {{ account.zone }} · Outstanding KES {{ account.outstanding_amount }} · Defaulted {{ account.defaulted_at }}
                                </p>
                            </div>
                            <Link
                                :href="route('admin.work-orders.initiate-disconnection', account.id)"
                                method="post"
                                as="button"
                                class="shrink-0 inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-amber-600 text-white hover:bg-amber-700"
                            >
                                Issue Notice
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Active pipeline -->
            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Active Pipeline</h3>
                <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                    <div v-if="pipeline.length === 0" class="p-6 text-ocean-700 text-sm">
                        No active work orders.
                    </div>
                    <div v-else class="divide-y divide-ocean-100">
                        <div v-for="wo in pipeline" :key="wo.id" class="p-4">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div>
                                    <p class="font-medium text-ocean-900">
                                        {{ wo.type_label }} · {{ wo.customer_name }} · {{ wo.account_number }}
                                    </p>
                                    <p class="text-xs text-ocean-500 mt-0.5">{{ wo.zone }} · Opened {{ wo.created_at }}</p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                                    :class="statusStyles[wo.status] || 'bg-ocean-100 text-ocean-800'"
                                >
                                    {{ wo.status_label }}
                                </span>
                            </div>

                            <p v-if="wo.status === 'notice_sent'" class="text-sm text-ocean-700 mb-2">
                                Notice deadline: {{ wo.notice_deadline }}
                                <span v-if="!wo.notice_elapsed" class="text-ocean-500">(not yet elapsed)</span>
                            </p>
                            <p v-if="wo.status === 'disputed'" class="text-sm text-red-700 mb-2">
                                Dispute reason: {{ wo.dispute_reason }}
                            </p>

                            <div v-if="wo.source" class="text-sm text-ocean-700 bg-ocean-50 rounded-md px-3 py-2 mb-2">
                                <span v-if="wo.source.severity_label" class="font-medium">{{ wo.source.severity_label }} severity — </span>
                                <span v-if="wo.source.request_type" class="font-medium">{{ wo.source.request_type }} — </span>
                                {{ wo.source.description }}
                                <span v-if="wo.source.location_notes" class="block text-xs text-ocean-500 mt-1">{{ wo.source.location_notes }}</span>
                                <PhotoGallery :photos="wo.source.photos" />
                            </div>

                            <div v-if="wo.evidence_photos && wo.evidence_photos.length > 0" class="mb-2">
                                <p class="text-xs font-medium text-ocean-500 mb-1">Repair evidence:</p>
                                <PhotoGallery :photos="wo.evidence_photos" />
                            </div>

                            <div class="flex flex-wrap gap-2 mt-2">
                                <Link
                                    v-if="wo.status === 'notice_sent' && wo.notice_elapsed"
                                    :href="route('admin.work-orders.sign-off', wo.id)"
                                    method="patch"
                                    as="button"
                                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                                >
                                    Sign Off
                                </Link>
                                <Link
                                    v-if="wo.status === 'disputed'"
                                    :href="route('admin.work-orders.resolve-dispute', wo.id)"
                                    method="patch"
                                    :data="{ resolution: 'reinstate' }"
                                    as="button"
                                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                                >
                                    Reinstate
                                </Link>
                                <Link
                                    v-if="wo.status === 'disputed'"
                                    :href="route('admin.work-orders.resolve-dispute', wo.id)"
                                    method="patch"
                                    :data="{ resolution: 'cancel' }"
                                    as="button"
                                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                                >
                                    Cancel Work Order
                                </Link>
                                <Link
                                    v-if="!['disputed'].includes(wo.status)"
                                    :href="route('admin.work-orders.cancel', wo.id)"
                                    method="patch"
                                    as="button"
                                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                                >
                                    Cancel
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- History -->
            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Recent History</h3>
                <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                    <div v-if="history.length === 0" class="p-6 text-ocean-700 text-sm">
                        No closed work orders yet.
                    </div>
                    <div v-else class="divide-y divide-ocean-100">
                        <div v-for="wo in history" :key="wo.id" class="p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-ocean-900">
                                        {{ wo.type_label }} · {{ wo.customer_name }} · {{ wo.account_number }}
                                    </p>
                                    <p class="text-xs text-ocean-500 mt-0.5">{{ wo.zone }}</p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                                    :class="statusStyles[wo.status] || 'bg-ocean-100 text-ocean-800'"
                                >
                                    {{ wo.status_label }}
                                </span>
                            </div>
                            <PhotoGallery v-if="wo.evidence_photos" :photos="wo.evidence_photos" />
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppShell>
</template>
