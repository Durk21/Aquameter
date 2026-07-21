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
    meters: {
        type: Array,
        required: true,
    },
    zones: {
        type: Array,
        required: true,
    },
    severities: {
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

const form = useForm({
    meter_id: "",
    severity: "medium",
    zone: props.defaultZone,
    location_notes: "",
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
    form.post(route("customer.leak-reports.store"), {
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Report a Leak" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Report a Leak
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Report a Leak
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="severity" value="Severity" />
                        <div class="mt-1 flex gap-2">
                            <label
                                v-for="option in severities"
                                :key="option.value"
                                class="flex-1 text-center px-3 py-2 rounded-md border text-sm font-medium cursor-pointer"
                                :class="form.severity === option.value
                                    ? 'bg-ocean-600 border-ocean-600 text-white'
                                    : 'border-ocean-300 text-ocean-700 dark:text-neutral-300 hover:bg-ocean-50'"
                            >
                                <input type="radio" v-model="form.severity" :value="option.value" class="sr-only" />
                                {{ option.label }}
                            </label>
                        </div>
                        <InputError class="mt-2" :message="form.errors.severity" />
                    </div>

                    <div class="mt-4" v-if="meters.length > 0">
                        <InputLabel for="meter_id" value="Related Meter (optional)" />
                        <select
                            id="meter_id"
                            v-model="form.meter_id"
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                        >
                            <option value="">Not at a specific meter</option>
                            <option v-for="m in meters" :key="m.id" :value="m.id">{{ m.meter_number }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.meter_id" />
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
                        <InputLabel for="location_notes" value="Location Notes (optional)" />
                        <input
                            id="location_notes"
                            type="text"
                            v-model="form.location_notes"
                            maxlength="255"
                            placeholder="e.g. near the roundabout, outside the blue gate"
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                        />
                        <InputError class="mt-2" :message="form.errors.location_notes" />
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
                            rows="4"
                            maxlength="2000"
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                            placeholder="What does the leak look like? How much water, how long has it been going?"
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
                            Report Leak
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
