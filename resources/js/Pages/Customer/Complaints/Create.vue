<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";

const props = defineProps({
    bills: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    bill_id: "",
    subject: "",
    description: "",
});

const submit = () => {
    form.post(route("customer.complaints.store"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Submit a Complaint" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Submit a Complaint
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Submit a Complaint
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="bill_id" value="Related Bill (optional)" />
                        <select
                            id="bill_id"
                            v-model="form.bill_id"
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                        >
                            <option value="">Not related to a specific bill</option>
                            <option v-for="b in bills" :key="b.id" :value="b.id">
                                KES {{ b.amount }} — due {{ b.due_date }} ({{ b.status }})
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.bill_id" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="subject" value="Subject" />
                        <TextInput
                            id="subject"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.subject"
                            required
                            maxlength="255"
                        />
                        <InputError class="mt-2" :message="form.errors.subject" />
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
                            placeholder="Explain what looks wrong and why."
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div v-if="$page.props.flash?.status" class="mt-4 text-sm text-ocean-700 dark:text-neutral-300 bg-ocean-50 rounded-md px-3 py-2">
                        {{ $page.props.flash.status }}
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Submit Complaint
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
