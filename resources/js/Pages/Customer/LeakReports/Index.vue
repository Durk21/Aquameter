<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import PhotoGallery from "@/Components/PhotoGallery.vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    leakReports: {
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

const severityStyles = {
    low: "bg-ocean-100 text-ocean-800",
    medium: "bg-amber-100 text-amber-800",
    high: "bg-red-100 text-red-800",
};
</script>

<template>
    <Head title="Leak Reports" />

    <AppShell>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                    Leak Reports
                </h2>
                <Link
                    :href="route('customer.leak-reports.create')"
                    class="text-sm font-medium bg-ocean-600 hover:bg-ocean-700 text-white px-4 py-2 rounded-md transition-colors"
                >
                    Report a Leak
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <div class="flex md:hidden items-center justify-between mb-4">
                <h1 class="text-lg font-semibold text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                    Leak Reports
                </h1>
                <Link
                    :href="route('customer.leak-reports.create')"
                    class="text-sm font-medium bg-ocean-600 hover:bg-ocean-700 text-white px-3 py-1.5 rounded-md"
                >
                    Report
                </Link>
            </div>

            <div v-if="leakReports.length === 0" class="bg-white rounded-lg border border-ocean-100 p-6 text-ocean-700">
                You haven't reported any leaks yet.
            </div>

            <div v-else class="space-y-4">
                <div v-for="leak in leakReports" :key="leak.id" class="bg-white rounded-lg border border-ocean-100 p-5">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="severityStyles[leak.severity] || 'bg-ocean-100 text-ocean-800'"
                            >
                                {{ leak.severity_label }} severity
                            </span>
                            <span class="text-xs text-ocean-500">{{ leak.zone }}</span>
                        </div>
                        <span
                            v-if="leak.status"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                            :class="statusStyles[leak.status] || 'bg-ocean-100 text-ocean-800'"
                        >
                            {{ leak.status_label }}
                        </span>
                    </div>
                    <p class="text-sm text-ocean-700 mb-2">{{ leak.description }}</p>
                    <p v-if="leak.location_notes" class="text-xs text-ocean-500 mb-2">{{ leak.location_notes }}</p>
                    <PhotoGallery :photos="leak.photos" />
                    <p class="text-xs text-ocean-400 mt-2">Reported {{ leak.created_at }}</p>
                </div>
            </div>
        </div>
    </AppShell>
</template>
