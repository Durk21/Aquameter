<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { reactive } from "vue";

const props = defineProps({
    technicians: {
        type: Array,
        required: true,
    },
    zones: {
        type: Array,
        required: true,
    },
});

const zoneSelections = reactive(
    Object.fromEntries(props.technicians.map((t) => [t.id, t.zone || ""])),
);

const forms = reactive({});

const saveZone = (technician) => {
    if (!forms[technician.id]) {
        forms[technician.id] = useForm({ zone: zoneSelections[technician.id] });
    }

    forms[technician.id].zone = zoneSelections[technician.id];
    forms[technician.id].patch(route("admin.technicians.update-zone", technician.id));
};
</script>

<template>
    <Head title="Technicians" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Technicians
            </h2>
        </template>

        <div class="max-w-3xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Technicians
            </h1>

            <p class="text-sm text-ocean-500 mb-4">
                Assigning a technician's zone routes jobs in that zone to the front of their dispatch queue.
                Any technician can still manually claim a job in any zone.
            </p>

            <div v-if="$page.props.flash?.status" class="mb-4 text-sm text-ocean-700 bg-ocean-50 rounded-md px-3 py-2">
                {{ $page.props.flash.status }}
            </div>

            <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                <div v-if="technicians.length === 0" class="p-6 text-ocean-700 text-sm">
                    No technicians yet.
                </div>
                <div v-else class="divide-y divide-ocean-100">
                    <div v-for="technician in technicians" :key="technician.id" class="flex items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-medium text-ocean-900">{{ technician.name }}</p>
                            <p class="text-xs text-ocean-500 mt-0.5">{{ technician.email }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="zoneSelections[technician.id]"
                                class="text-sm border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
                            >
                                <option value="">No zone</option>
                                <option v-for="z in zones" :key="z" :value="z">{{ z }}</option>
                            </select>
                            <button
                                @click="saveZone(technician)"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                            >
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>
