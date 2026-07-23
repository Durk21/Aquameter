<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout title="Verify your email" subtitle="One more step before you can get started">
        <Head title="Email Verification" />

        <div class="mb-4 text-sm text-ocean-600 dark:text-neutral-400">
            Thanks for signing up! Before getting started, could you verify your
            email address by clicking on the link we just emailed to you? If you
            didn't receive the email, we will gladly send you another.
        </div>

        <div
            class="mb-4 text-sm font-medium text-emerald-600 dark:text-emerald-400"
            v-if="verificationLinkSent"
        >
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <form @submit.prevent="submit" class="flex items-center justify-between gap-4">
            <PrimaryButton :disabled="form.processing">
                Resend Verification Email
            </PrimaryButton>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="text-sm text-ocean-500 dark:text-neutral-400 hover:text-ocean-700 dark:hover:text-neutral-200 focus:outline-none focus:ring-2 focus:ring-ocean-400 rounded"
                >Log Out</Link
            >
        </form>
    </GuestLayout>
</template>
