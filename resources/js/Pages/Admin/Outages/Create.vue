<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";

defineProps({
    zones: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    zone: "",
    title: "",
    description: "",
    starts_at: "",
    ends_at: "",
});

const submit = () => {
    form.post(route("outages.store"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Broadcast Outage Notice" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Broadcast Outage Notice
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Broadcast Outage Notice
            </h1>

            <div class="bg-white rounded-lg border border-ocean-100 p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="zone" value="Zone" />
                        <select
                            id="zone"
                            v-model="form.zone"
                            class="mt-1 block w-full border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
                        >
                            <option value="">All zones</option>
                            <option v-for="z in zones" :key="z" :value="z">{{ z }}</option>
                        </select>
                        <p class="mt-1 text-xs text-ocean-500">Leave as "All zones" to notify every customer.</p>
                        <InputError class="mt-2" :message="form.errors.zone" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="title" value="Title" />
                        <TextInput
                            id="title"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.title"
                            maxlength="255"
                            required
                            placeholder="e.g. Planned pipe maintenance"
                        />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="description" value="Details" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            required
                            rows="4"
                            maxlength="2000"
                            class="mt-1 block w-full border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
                            placeholder="What's happening, and what affected customers should expect."
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="starts_at" value="Starts" />
                            <TextInput
                                id="starts_at"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                v-model="form.starts_at"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.starts_at" />
                        </div>
                        <div>
                            <InputLabel for="ends_at" value="Expected end (optional)" />
                            <TextInput
                                id="ends_at"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                v-model="form.ends_at"
                            />
                            <InputError class="mt-2" :message="form.errors.ends_at" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton
                            class="bg-ocean-600 hover:bg-ocean-700 focus:bg-ocean-700 active:bg-ocean-800"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Broadcast
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
