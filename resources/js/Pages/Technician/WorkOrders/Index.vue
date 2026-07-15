<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { reactive } from "vue";

const props = defineProps({
    claimable: {
        type: Array,
        required: true,
    },
    myJobs: {
        type: Array,
        required: true,
    },
});

const notes = reactive({});

const complete = (workOrder) => {
    router.patch(route("technician.work-orders.complete", workOrder.id), {
        resolution_notes: notes[workOrder.id] || "",
    });
};

const statusStyles = {
    approved: "bg-ocean-100 text-ocean-800",
    claimed: "bg-indigo-100 text-indigo-800",
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

        <div class="max-w-3xl mx-auto px-4 md:px-6 py-6 space-y-8">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Work Orders
            </h1>

            <div v-if="$page.props.flash?.status" class="text-sm text-ocean-700 bg-ocean-50 rounded-md px-3 py-2">
                {{ $page.props.flash.status }}
            </div>
            <div v-if="$page.props.errors?.work_order" class="text-sm text-red-700 bg-red-50 rounded-md px-3 py-2">
                {{ $page.props.errors.work_order }}
            </div>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">My Jobs</h3>
                <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                    <div v-if="myJobs.length === 0" class="p-6 text-ocean-700 text-sm">
                        You have no claimed jobs in progress.
                    </div>
                    <div v-else class="divide-y divide-ocean-100">
                        <div v-for="wo in myJobs" :key="wo.id" class="p-4">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div>
                                    <p class="font-medium text-ocean-900">{{ wo.type_label }} · {{ wo.account_number }}</p>
                                    <p class="text-xs text-ocean-500 mt-0.5">{{ wo.customer_name }} · {{ wo.zone }}</p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                                    :class="statusStyles[wo.status] || 'bg-ocean-100 text-ocean-800'"
                                >
                                    {{ wo.status_label }}
                                </span>
                            </div>
                            <div v-if="wo.source" class="text-sm text-ocean-700 bg-ocean-50 rounded-md px-3 py-2 mb-2">
                                <span v-if="wo.source.severity_label" class="font-medium">{{ wo.source.severity_label }} severity — </span>
                                <span v-if="wo.source.request_type" class="font-medium">{{ wo.source.request_type }} — </span>
                                {{ wo.source.description }}
                                <span v-if="wo.source.location_notes" class="block text-xs text-ocean-500 mt-1">{{ wo.source.location_notes }}</span>
                            </div>
                            <textarea
                                v-model="notes[wo.id]"
                                rows="2"
                                maxlength="2000"
                                placeholder="Notes on how this job was completed (optional)"
                                class="block w-full text-sm border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm mb-2"
                            ></textarea>
                            <button
                                @click="complete(wo)"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700"
                            >
                                Mark Completed
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Available Jobs</h3>
                <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                    <div v-if="claimable.length === 0" class="p-6 text-ocean-700 text-sm">
                        No jobs are currently ready for dispatch.
                    </div>
                    <div v-else class="divide-y divide-ocean-100">
                        <div v-for="wo in claimable" :key="wo.id" class="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p class="font-medium text-ocean-900">{{ wo.type_label }} · {{ wo.account_number }}</p>
                                <p class="text-xs text-ocean-500 mt-0.5">{{ wo.customer_name }} · {{ wo.zone }}</p>
                                <p v-if="wo.source" class="text-xs text-ocean-600 mt-1">
                                    <span v-if="wo.source.severity_label">{{ wo.source.severity_label }} severity — </span>
                                    <span v-if="wo.source.request_type">{{ wo.source.request_type }} — </span>
                                    {{ wo.source.description }}
                                </p>
                            </div>
                            <Link
                                :href="route('technician.work-orders.claim', wo.id)"
                                method="patch"
                                as="button"
                                class="shrink-0 inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                            >
                                Claim
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppShell>
</template>
