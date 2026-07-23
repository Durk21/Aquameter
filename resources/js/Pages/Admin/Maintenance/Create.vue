<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    meters: {
        type: Array,
        required: true,
    },
    zones: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    scope: "meter",
    meter_id: "",
    zone: "",
    scheduled_for: new Date().toISOString().split("T")[0],
    description: "",
});

const meterCountForZone = computed(() => {
    if (!form.zone) return 0;
    return props.meters.filter((m) => m.zone === form.zone).length;
});

const submit = () => {
    form.post(route("admin.maintenance.store"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Schedule Maintenance" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Schedule Maintenance
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Schedule Maintenance
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel value="Scope" />
                        <div class="mt-1 flex gap-2">
                            <label
                                class="flex-1 text-center px-3 py-2 rounded-md border text-sm font-medium cursor-pointer"
                                :class="form.scope === 'meter'
                                    ? 'bg-ocean-600 border-ocean-600 text-white'
                                    : 'border-ocean-300 text-ocean-700 dark:text-neutral-300 hover:bg-ocean-50'"
                            >
                                <input type="radio" v-model="form.scope" value="meter" class="sr-only" />
                                Single Meter
                            </label>
                            <label
                                class="flex-1 text-center px-3 py-2 rounded-md border text-sm font-medium cursor-pointer"
                                :class="form.scope === 'zone'
                                    ? 'bg-ocean-600 border-ocean-600 text-white'
                                    : 'border-ocean-300 text-ocean-700 dark:text-neutral-300 hover:bg-ocean-50'"
                            >
                                <input type="radio" v-model="form.scope" value="zone" class="sr-only" />
                                Entire Zone
                            </label>
                        </div>
                    </div>

                    <div class="mt-4" v-if="form.scope === 'meter'">
                        <InputLabel for="meter_id" value="Meter" />
                        <select
                            id="meter_id"
                            v-model="form.meter_id"
                            required
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                        >
                            <option value="" disabled>Select a meter</option>
                            <option v-for="m in meters" :key="m.id" :value="m.id">
                                {{ m.meter_number }} — {{ m.customer_name }} ({{ m.account_number }}, {{ m.zone }})
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.meter_id" />
                    </div>

                    <div class="mt-4" v-else>
                        <InputLabel for="zone" value="Zone" />
                        <select
                            id="zone"
                            v-model="form.zone"
                            required
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                        >
                            <option value="" disabled>Select a zone</option>
                            <option v-for="z in zones" :key="z" :value="z">{{ z }}</option>
                        </select>
                        <p v-if="form.zone" class="mt-1 text-xs text-ocean-500 dark:text-neutral-400">
                            This will schedule maintenance for {{ meterCountForZone }} meter(s) in {{ form.zone }}.
                        </p>
                        <InputError class="mt-2" :message="form.errors.zone" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="scheduled_for" value="Scheduled Date" />
                        <TextInput
                            id="scheduled_for"
                            type="date"
                            class="mt-1 block w-full"
                            v-model="form.scheduled_for"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.scheduled_for" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="description" value="What needs to be done" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            required
                            rows="4"
                            maxlength="2000"
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                            placeholder="e.g. Routine meter inspection and valve check."
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div v-if="$page.props.flash?.status" class="mt-4 text-sm text-ocean-700 dark:text-neutral-300 bg-ocean-50 rounded-md px-3 py-2">
                        {{ $page.props.flash.status }}
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Schedule
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
