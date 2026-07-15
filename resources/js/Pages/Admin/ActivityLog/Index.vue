<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    logs: {
        type: Array,
        required: true,
    },
});

const subjectStyles = {
    Bill: "bg-amber-100 text-amber-800",
    Complaint: "bg-ocean-100 text-ocean-800",
    WorkOrder: "bg-indigo-100 text-indigo-800",
};
</script>

<template>
    <Head title="Activity Log" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Activity Log
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Activity Log
            </h1>

            <p class="text-sm text-ocean-500 mb-4">
                Every status transition on bills, complaints, and work orders — most recent 100 entries.
            </p>

            <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                <div v-if="logs.length === 0" class="p-6 text-ocean-700 text-sm">
                    No activity recorded yet.
                </div>
                <div v-else class="divide-y divide-ocean-100">
                    <div v-for="log in logs" :key="log.id" class="flex items-start justify-between gap-3 p-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="subjectStyles[log.subject_type] || 'bg-ocean-100 text-ocean-800'"
                                >
                                    {{ log.subject_type }} #{{ log.subject_id }}
                                </span>
                                <span class="text-sm text-ocean-700">
                                    {{ log.from_status || '—' }} → {{ log.to_status || '—' }}
                                </span>
                            </div>
                            <p class="text-xs text-ocean-500 mt-1">By {{ log.caused_by }}</p>
                            <p v-if="log.notes" class="text-xs text-ocean-500 mt-1">{{ log.notes }}</p>
                        </div>
                        <p class="text-xs text-ocean-400 shrink-0">{{ log.created_at }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>
