<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    accounts: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    account_id: "",
    installed_at: new Date().toISOString().split("T")[0],
});

const selectedAccount = computed(() =>
    props.accounts.find((a) => a.id === Number(form.account_id))
);

const submit = () => {
    form.post(route("admin.meters.store"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Install Meter" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Register a Meter Installation
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Register a Meter Installation
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="account_id" value="Customer Account" />
                        <select
                            id="account_id"
                            v-model="form.account_id"
                            required
                            class="mt-1 block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                        >
                            <option value="" disabled>Select an account</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">
                                {{ a.account_number }} — {{ a.customer_name }} ({{ a.zone }}){{ a.has_meter ? " · already has a meter" : "" }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.account_id" />
                    </div>

                    <div v-if="selectedAccount && selectedAccount.has_meter" class="mt-2 text-sm text-amber-700 bg-amber-50 rounded-md px-3 py-2">
                        This account already has a meter registered.
                    </div>

                    <div class="mt-4">
                        <InputLabel for="installed_at" value="Installation Date" />
                        <TextInput
                            id="installed_at"
                            type="date"
                            class="mt-1 block w-full"
                            v-model="form.installed_at"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.installed_at" />
                    </div>

                    <div v-if="$page.props.flash?.status" class="mt-4 text-sm text-ocean-700 dark:text-neutral-300 bg-ocean-50 rounded-md px-3 py-2">
                        {{ $page.props.flash.status }}
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Register Meter
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
