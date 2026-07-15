<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import PhotoGallery from "@/Components/PhotoGallery.vue";
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
                <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                    Service Requests
                </h2>
                <Link
                    :href="route('customer.service-requests.create')"
                    class="text-sm font-medium bg-ocean-600 hover:bg-ocean-700 text-white px-4 py-2 rounded-md transition-colors"
                >
                    New Request
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <div class="flex md:hidden items-center justify-between mb-4">
                <h1 class="text-lg font-semibold text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                    Service Requests
                </h1>
                <Link
                    :href="route('customer.service-requests.create')"
                    class="text-sm font-medium bg-ocean-600 hover:bg-ocean-700 text-white px-3 py-1.5 rounded-md"
                >
                    New
                </Link>
            </div>

            <div v-if="serviceRequests.length === 0" class="bg-white rounded-lg border border-ocean-100 p-6 text-ocean-700">
                You haven't submitted any service requests yet.
            </div>

            <div v-else class="space-y-4">
                <div v-for="sr in serviceRequests" :key="sr.id" class="bg-white rounded-lg border border-ocean-100 p-5">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <h3 class="font-semibold text-ocean-900">{{ typeLabels[sr.type] || sr.type }}</h3>
                            <p class="text-xs text-ocean-500 mt-0.5">{{ sr.zone }}</p>
                        </div>
                        <span
                            v-if="sr.status"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                            :class="statusStyles[sr.status] || 'bg-ocean-100 text-ocean-800'"
                        >
                            {{ sr.status_label }}
                        </span>
                    </div>
                    <p class="text-sm text-ocean-700 mb-2">{{ sr.description }}</p>
                    <PhotoGallery :photos="sr.photos" />
                    <p class="text-xs text-ocean-400 mt-2">Submitted {{ sr.created_at }}</p>
                </div>
            </div>
        </div>
    </AppShell>
</template>
