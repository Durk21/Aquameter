<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { askAquameter } from "@/chatBus";
import { Head } from "@inertiajs/vue3";

defineProps({
    readings: {
        type: Array,
        required: true,
    },
});

function explain(reading) {
    askAquameter(`Why was my meter reading of ${reading.reading_value} on ${reading.reading_date} flagged as unusual?`);
}
</script>

<template>
    <Head title="Meter Reading History" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Meter Reading History
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Meter Reading History
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft overflow-hidden">
                <div v-if="readings.length === 0" class="p-6 text-ocean-700 dark:text-neutral-300">
                    No meter readings recorded yet.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ocean-100">
                        <thead class="bg-ocean-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 dark:text-neutral-300 uppercase tracking-wider">
                                    Meter
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 dark:text-neutral-300 uppercase tracking-wider">
                                    Reading
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 dark:text-neutral-300 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-ocean-700 dark:text-neutral-300 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ocean-100 dark:divide-white/5">
                            <tr v-for="reading in readings" :key="reading.id">
                                <td class="px-6 py-4 text-sm text-ocean-900 dark:text-white font-mono">
                                    {{ reading.meter_number }}
                                </td>
                                <td class="px-6 py-4 text-sm text-ocean-900 dark:text-white font-mono">
                                    {{ reading.reading_value }}
                                </td>
                                <td class="px-6 py-4 text-sm text-ocean-700 dark:text-neutral-300">
                                    {{ reading.reading_date }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div v-if="reading.is_anomalous" class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            Flagged for review
                                        </span>
                                        <button
                                            type="button"
                                            @click="explain(reading)"
                                            class="text-xs font-medium text-ocean-600 hover:text-ocean-700 dark:text-ocean-300 dark:hover:text-white underline underline-offset-2"
                                        >
                                            Ask Aquameter why
                                        </button>
                                    </div>
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
