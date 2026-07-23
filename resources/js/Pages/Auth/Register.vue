<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    zones: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    address: "",
    zone: "",
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <GuestLayout title="Create your account" subtitle="Set up billing and service for your address">
        <Head title="Register" />

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="address" value="Service Address" />
                <TextInput
                    id="address"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.address"
                    required
                    autocomplete="street-address"
                />
                <InputError class="mt-2" :message="form.errors.address" />
            </div>

            <div>
                <InputLabel for="zone" value="Service Zone" />
                <select
                    id="zone"
                    v-model="form.zone"
                    required
                    class="mt-1 block w-full rounded-lg border-ocean-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-ocean-900 dark:text-neutral-100 focus:border-ocean-500 focus:ring-ocean-500 shadow-sm"
                >
                    <option value="" disabled>Select your zone</option>
                    <option v-for="z in zones" :key="z" :value="z">{{ z }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.zone" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">
                Register
            </PrimaryButton>

            <p class="text-center text-sm text-ocean-500 dark:text-neutral-400">
                Already registered?
                <Link :href="route('login')" class="font-medium text-ocean-700 dark:text-ocean-400 hover:text-ocean-900 dark:hover:text-ocean-300">
                    Log in
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
