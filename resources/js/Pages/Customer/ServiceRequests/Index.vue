<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import PhotoGallery from "@/Components/PhotoGallery.vue";
import StarRating from "@/Components/StarRating.vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    serviceRequests: {
        type: Array,
        required: true,
    },
});

const statusStyles = {
    approved: "bg-ocean-100 text-ocean-800",
    claimed: "bg-indigo-100 text-indigo-800",
    completed: "bg-emerald-100 text-emerald-800",
    cancelled: "bg-neutral-200 text-neutral-700",
};

const typeLabels = {
    meter_inspection: "Meter Inspection",
    pipe_maintenance: "Pipe Maintenance",
    meter_relocation: "Meter Relocation",
    other: "Other",
};
</script>

<template>
    <Head title="Service Requests" />

    <AppShell>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                    Service Requests
                </h2>
                <Link
                    :href="route('customer.service-requests.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft hover:shadow-glow px-4 py-2 rounded-lg transition-shadow"
                >
                    New Request
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <div class="flex md:hidden items-center justify-between mb-4">
                <h1 class="font-display text-lg font-semibold text-ocean-900 dark:text-white">
                    Service Requests
                </h1>
                <Link
                    :href="route('customer.service-requests.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft px-3 py-1.5 rounded-lg"
                >
                    New
                </Link>
            </div>

            <div v-if="serviceRequests.length === 0" class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6 text-ocean-600 dark:text-neutral-400">
                You haven't submitted any service requests yet.
            </div>

            <div v-else class="space-y-4">
                <div v-for="sr in serviceRequests" :key="sr.id" class="bg-white rounded-lg border border-ocean-100 p-5">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <h3 class="font-semibold text-ocean-900 dark:text-white">{{ typeLabels[sr.type] || sr.type }}</h3>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-0.5">{{ sr.zone }}</p>
                        </div>
                        <span
                            v-if="sr.status"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                            :class="statusStyles[sr.status] || 'bg-ocean-100 text-ocean-800'"
                        >
                            {{ sr.status_label }}
                        </span>
                    </div>
                    <p class="text-sm text-ocean-700 dark:text-neutral-300 mb-2">{{ sr.description }}</p>
                    <PhotoGallery :photos="sr.photos" />
                    <p v-if="sr.rating" class="text-sm text-amber-600 mt-2">
                        {{ '★'.repeat(sr.rating) }}{{ '☆'.repeat(5 - sr.rating) }}
                    </p>
                    <StarRating v-else-if="sr.status === 'completed' && sr.work_order_id" :work-order-id="sr.work_order_id" />
                    <p class="text-xs text-ocean-400 dark:text-neutral-500 mt-2">Submitted {{ sr.created_at }}</p>
                </div>
            </div>
        </div>
    </AppShell>
</template>
