<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import PhotoUploadInput from "@/Components/PhotoUploadInput.vue";
import NetworkMap from "@/Components/NetworkMap.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    types: {
        type: Array,
        required: true,
    },
    zones: {
        type: Array,
        required: true,
    },
    defaultZone: {
        type: String,
        required: true,
    },
    pipeSegments: {
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
});

const typeLabels = {
    meter_inspection: "Meter Inspection",
    pipe_maintenance: "Pipe Maintenance",
    meter_relocation: "Meter Relocation",
    other: "Other",
};

const form = useForm({
    type: props.types[0] || "",
    zone: props.defaultZone,
    latitude: "",
    longitude: "",
    description: "",
    photos: [],
});

const locationError = ref("");

const pickedLocation = computed({
    get: () => (form.latitude && form.longitude ? { lat: Number(form.latitude), lng: Number(form.longitude) } : null),
    set: (value) => {
        form.latitude = value ? value.lat.toFixed(7) : "";
        form.longitude = value ? value.lng.toFixed(7) : "";
    },
});

const submit = () => {
    form.post(route("customer.service-requests.store"), {
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Request Service" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Request Service
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Request Service
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="type" value="Request Type" />
                        <select
                            id="type"
                            v-model="form.type"
                            required
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                        >
                            <option v-for="t in types" :key="t" :value="t">{{ typeLabels[t] || t }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                    <div class="mt-4">
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

                    <div class="mt-4">
                        <InputLabel value="Pinpoint the location (optional)" />
                        <p class="text-xs text-ocean-500 dark:text-neutral-400 mb-2">
                            Click the map, or use your current location — either one also auto-selects the zone above.
                        </p>
                        <NetworkMap
                            mode="picker"
                            v-model="pickedLocation"
                            @update:zone="form.zone = $event"
                            :pipe-segments="pipeSegments"
                            :zones="zones"
                            :zone-centers="zoneCenters"
                            :service-area-bounds="serviceAreaBounds"
                            @location-error="locationError = $event"
                            height="280px"
                        />
                        <span v-if="form.latitude" class="mt-1 inline-block text-xs text-emerald-700 dark:text-emerald-400">
                            Location captured ({{ form.latitude }}, {{ form.longitude }})
                        </span>
                        <p v-if="locationError" class="mt-1 text-xs text-red-700 dark:text-red-400">{{ locationError }}</p>
                        <InputError class="mt-1" :message="form.errors.latitude || form.errors.longitude" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            required
                            rows="5"
                            maxlength="2000"
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                            placeholder="Describe what you need done."
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div class="mt-4">
                        <InputLabel value="Photos (optional)" />
                        <PhotoUploadInput
                            v-model="form.photos"
                            :error="form.errors.photos || form.errors['photos.0']"
                        />
                    </div>

                    <div v-if="$page.props.flash?.status" class="mt-4 text-sm text-ocean-700 dark:text-neutral-300 bg-ocean-50 rounded-md px-3 py-2">
                        {{ $page.props.flash.status }}
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Submit Request
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
