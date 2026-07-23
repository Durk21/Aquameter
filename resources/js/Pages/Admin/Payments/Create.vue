<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";

const props = defineProps({
    bill: {
        type: Object,
        required: true,
    },
    paymentMethods: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    amount: props.bill.amount,
    method: "",
    reference: "",
    paid_at: new Date().toISOString().split("T")[0],
});

const submit = () => {
    form.post(route("admin.payments.store", props.bill.id));
};
</script>

<template>
    <Head title="Record Payment" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Record Payment
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Record Payment
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6 mb-6">
                <p class="text-sm text-ocean-500 dark:text-neutral-400 mb-1">
                    {{ bill.customer_name }} · {{ bill.account_number }}<span v-if="bill.phone"> · {{ bill.phone }}</span>
                </p>
                <p class="text-2xl font-semibold text-ocean-900 dark:text-white font-mono">
                    KES {{ bill.amount }}
                </p>
                <p class="text-sm text-ocean-500 dark:text-neutral-400 mt-1">Due {{ bill.due_date }}</p>
            </div>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="amount" value="Amount (KES)" />
                        <TextInput
                            id="amount"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                            v-model="form.amount"
                            required
                        />
                        <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-1">Must match the full bill amount exactly.</p>
                        <InputError class="mt-2" :message="form.errors.amount" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="method" value="Payment Method" />
                        <select
                            id="method"
                            v-model="form.method"
                            required
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                        >
                            <option value="" disabled>Select a method</option>
                            <option v-for="m in paymentMethods" :key="m" :value="m" class="capitalize">
                                {{ m.replace('_', ' ') }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.method" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="reference" value="Reference (optional)" />
                        <TextInput
                            id="reference"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.reference"
                            placeholder="e.g. M-Pesa transaction code"
                        />
                        <InputError class="mt-2" :message="form.errors.reference" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="paid_at" value="Payment Date" />
                        <TextInput
                            id="paid_at"
                            type="date"
                            class="mt-1 block w-full"
                            v-model="form.paid_at"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.paid_at" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Record Payment
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
