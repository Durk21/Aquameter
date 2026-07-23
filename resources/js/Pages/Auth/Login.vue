<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout title="Welcome back" subtitle="Log in to your Aquameter account">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-ocean-600 dark:text-neutral-400">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-ocean-600 dark:text-neutral-400 hover:text-ocean-800 dark:hover:text-neutral-200 focus:outline-none focus:ring-2 focus:ring-ocean-400 rounded"
                >
                    Forgot your password?
                </Link>
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">
                Log in
            </PrimaryButton>

            <p class="text-center text-sm text-ocean-500 dark:text-neutral-400">
                Don't have an account?
                <Link :href="route('register')" class="font-medium text-ocean-700 dark:text-ocean-400 hover:text-ocean-900 dark:hover:text-ocean-300">
                    Register
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
