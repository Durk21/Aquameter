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
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Record Payment
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Record Payment
            </h1>

            <div class="bg-white rounded-lg border border-ocean-100 p-6 mb-6">
                <p class="text-sm text-ocean-500 mb-1">{{ bill.customer_name }} · {{ bill.account_number }}</p>
                <p class="text-2xl font-semibold text-ocean-900" style="font-family: 'JetBrains Mono', monospace;">
                    KES {{ bill.amount }}
                </p>
                <p class="text-sm text-ocean-500 mt-1">Due {{ bill.due_date }}</p>
            </div>

            <div class="bg-white rounded-lg border border-ocean-100 p-6">
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
                        <p class="text-xs text-ocean-500 mt-1">Must match the full bill amount exactly.</p>
                        <InputError class="mt-2" :message="form.errors.amount" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="method" value="Payment Method" />
                        <select
                            id="method"
                            v-model="form.method"
                            required
                            class="mt-1 block w-full border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
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
                        <PrimaryButton
                            class="bg-ocean-600 hover:bg-ocean-700 focus:bg-ocean-700 active:bg-ocean-800"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Record Payment
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
