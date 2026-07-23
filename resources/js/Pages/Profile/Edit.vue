<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import NotificationPreferencesForm from "./Partials/NotificationPreferencesForm.vue";
import UpdateContactInfoForm from "./Partials/UpdateContactInfoForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    account: {
        type: Object,
        default: null,
    },
    notificationCategories: {
        type: Object,
        default: null,
    },
    notificationPreferences: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AppShell>
        <template #header>
            <h2 class="text-xl font-semibold text-ocean-900 dark:text-white font-display">
                Profile
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6 space-y-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 dark:text-white mb-2 font-display">
                Profile
            </h1>

            <div class="bg-white dark:bg-neutral-900 p-4 sm:p-8 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div v-if="account" class="bg-white dark:bg-neutral-900 p-4 sm:p-8 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft">
                <UpdateContactInfoForm :account="account" class="max-w-xl" />
            </div>

            <div v-if="notificationCategories" class="bg-white dark:bg-neutral-900 p-4 sm:p-8 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft">
                <NotificationPreferencesForm
                    :categories="notificationCategories"
                    :preferences="notificationPreferences"
                    class="max-w-xl"
                />
            </div>

            <div class="bg-white dark:bg-neutral-900 p-4 sm:p-8 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="bg-white dark:bg-neutral-900 p-4 sm:p-8 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AppShell>
</template>
