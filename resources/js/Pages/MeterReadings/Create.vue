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
});

const form = useForm({
    meter_id: "",
    reading_value: "",
    reading_date: new Date().toISOString().split("T")[0],
});

const selectedMeter = computed(() =>
    props.meters.find((m) => m.id === Number(form.meter_id))
);

const submit = () => {
    form.post(route("technician.meter-readings.store"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Record Meter Reading" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Record a Meter Reading
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Record a Meter Reading
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="meter_id" value="Meter" />
                        <select
                            id="meter_id"
                            v-model="form.meter_id"
                            required
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                        >
                            <option value="" disabled>Select a meter</option>
                            <option v-for="m in meters" :key="m.id" :value="m.id">
                                {{ m.meter_number }} — {{ m.account_number }} ({{ m.zone }})
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.meter_id" />
                    </div>

                    <div v-if="selectedMeter" class="mt-2 text-sm text-ocean-700 dark:text-neutral-300 bg-ocean-50 rounded-md px-3 py-2">
                        Account: {{ selectedMeter.account_number }} · Zone: {{ selectedMeter.zone }}
                    </div>

                    <div
                        v-if="selectedMeter && selectedMeter.has_unpaid_bill"
                        class="mt-2 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2"
                    >
                        ⚠ This account has an outstanding balance of KES {{ selectedMeter.outstanding_amount }}.
                        Any disconnection requires admin sign-off — do not act on this alone.
                    </div>

                    <div
                        v-else-if="selectedMeter"
                        class="mt-2 text-sm text-emerald-700 bg-emerald-50 rounded-md px-3 py-2"
                    >
                        ✓ Account is fully paid up.
                    </div>

                    <div class="mt-4">
                        <InputLabel for="reading_value" value="Reading Value" />
                        <TextInput
                            id="reading_value"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full"
                            v-model="form.reading_value"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.reading_value" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="reading_date" value="Reading Date" />
                        <TextInput
                            id="reading_date"
                            type="date"
                            class="mt-1 block w-full"
                            v-model="form.reading_date"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.reading_date" />
                    </div>

                    <div v-if="$page.props.flash?.status" class="mt-4 text-sm text-ocean-700 dark:text-neutral-300 bg-ocean-50 rounded-md px-3 py-2">
                        {{ $page.props.flash.status }}
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Submit Reading
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
