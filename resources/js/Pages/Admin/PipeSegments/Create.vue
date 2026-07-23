<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import NetworkMap from "@/Components/NetworkMap.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    zones: {
        type: Array,
        required: true,
    },
    zoneCenters: {
        type: Object,
        required: true,
    },
    serviceAreaBounds: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    name: "",
    zone: props.zones[0] ?? "",
    status: "active",
    points: [],
});

const mapError = ref("");

const submit = () => {
    form.post(route("admin.pipe-segments.store"), {
        onSuccess: () => {
            form.reset();
            form.points = [];
        },
    });
};
</script>

<template>
    <Head title="Add Pipe Segment" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Add Pipe Segment
            </h2>
        </template>

        <div class="max-w-3xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Add Pipe Segment
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6">
                <form @submit.prevent="submit">
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                maxlength="255"
                                required
                                placeholder="e.g. Njoro Main Line C"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="zone" value="Zone" />
                            <select
                                id="zone"
                                v-model="form.zone"
                                required
                                class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                            >
                                <option v-for="z in zones" :key="z" :value="z">{{ z }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.zone" />
                        </div>

                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                            >
                                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <InputLabel value="Trace the pipe" />
                        <p class="text-xs text-ocean-500 dark:text-neutral-400 mb-2">
                            Click along the map to trace the pipe's real path, following the road it runs alongside.
                        </p>
                        <NetworkMap
                            mode="draw"
                            v-model:points="form.points"
                            :zones="zones"
                            :zone-centers="zoneCenters"
                            :service-area-bounds="serviceAreaBounds"
                            @location-error="mapError = $event"
                            height="420px"
                        />
                        <p v-if="mapError" class="mt-1 text-xs text-red-700 dark:text-red-400">{{ mapError }}</p>
                        <InputError class="mt-2" :message="form.errors.points" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Save Segment
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
