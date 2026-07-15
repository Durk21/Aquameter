<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    },
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
    role: props.roles[0] || "",
    zone: "",
});

const submit = () => {
    form.post(route("admin.staff.store"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Add Staff Account" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Add Staff Account
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden text-lg font-semibold text-ocean-900 mb-4" style="font-family: 'Space Grotesk', sans-serif;">
                Add Staff Account
            </h1>

            <div class="bg-white rounded-lg border border-ocean-100 p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="role" value="Role" />
                        <div class="mt-1 flex gap-2">
                            <label
                                v-for="option in roles"
                                :key="option"
                                class="flex-1 text-center px-3 py-2 rounded-md border text-sm font-medium capitalize cursor-pointer"
                                :class="form.role === option
                                    ? 'bg-ocean-600 border-ocean-600 text-white'
                                    : 'border-ocean-300 text-ocean-700 hover:bg-ocean-50'"
                            >
                                <input type="radio" v-model="form.role" :value="option" class="sr-only" />
                                {{ option }}
                            </label>
                        </div>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="name" value="Full Name" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="form.role === 'technician'" class="mt-4">
                        <InputLabel for="zone" value="Zone (optional)" />
                        <select
                            id="zone"
                            v-model="form.zone"
                            class="mt-1 block w-full border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
                        >
                            <option value="">No zone</option>
                            <option v-for="z in zones" :key="z" :value="z">{{ z }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.zone" />
                    </div>

                    <div class="mt-4">
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

                    <div class="mt-4">
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

                    <div v-if="$page.props.flash?.status" class="mt-4 text-sm text-ocean-700 bg-ocean-50 rounded-md px-3 py-2">
                        {{ $page.props.flash.status }}
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton
                            class="bg-ocean-600 hover:bg-ocean-700 focus:bg-ocean-700 active:bg-ocean-800"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Create Account
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
