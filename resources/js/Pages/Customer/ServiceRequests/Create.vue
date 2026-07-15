<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import PhotoUploadInput from "@/Components/PhotoUploadInput.vue";
import { Head, useForm } from "@inertiajs/vue3";

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
    description: "",
    photos: [],
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
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Request Service
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Request Service
            </h1>

            <div class="bg-white rounded-lg border border-ocean-100 p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="type" value="Request Type" />
                        <select
                            id="type"
                            v-model="form.type"
                            required
                            class="mt-1 block w-full border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
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
                            class="mt-1 block w-full border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
                        >
                            <option v-for="z in zones" :key="z" :value="z">{{ z }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.zone" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            required
                            rows="5"
                            maxlength="2000"
                            class="mt-1 block w-full border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
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

                    <div v-if="$page.props.flash?.status" class="mt-4 text-sm text-ocean-700 bg-ocean-50 rounded-md px-3 py-2">
                        {{ $page.props.flash.status }}
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton
                            class="bg-ocean-600 hover:bg-ocean-700 focus:bg-ocean-700 active:bg-ocean-800"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Submit Request
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
