<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    readings: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Meter Reading History" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Meter Reading History
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Meter Reading History
            </h1>

            <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                <div v-if="readings.length === 0" class="p-6 text-ocean-700">
                    No meter readings recorded yet.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ocean-100">
                        <thead class="bg-ocean-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider">
                                    Meter
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider">
                                    Reading
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ocean-100">
                            <tr v-for="reading in readings" :key="reading.id">
                                <td class="px-6 py-4 text-sm text-ocean-900" style="font-family: 'JetBrains Mono', monospace;">
                                    {{ reading.meter_number }}
                                </td>
                                <td class="px-6 py-4 text-sm text-ocean-900" style="font-family: 'JetBrains Mono', monospace;">
                                    {{ reading.reading_value }}
                                </td>
                                <td class="px-6 py-4 text-sm text-ocean-700">
                                    {{ reading.reading_date }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        v-if="reading.is_anomalous"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800"
                                    >
                                        Flagged for review
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-ocean-100 text-ocean-800"
                                    >
                                        Normal
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppShell>
</template>
