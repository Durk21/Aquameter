<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
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
});
</script>

<template>
    <Head title="Profile" />

    <AppShell>
        <template #header>
            <h2 class="text-xl font-semibold text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Profile
            </h2>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6 space-y-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-2" style="font-family: 'Space Grotesk', sans-serif;">
                Profile
            </h1>

            <div class="bg-white p-4 sm:p-8 rounded-lg border border-ocean-100">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="bg-white p-4 sm:p-8 rounded-lg border border-ocean-100">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="bg-white p-4 sm:p-8 rounded-lg border border-ocean-100">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AppShell>
</template>
