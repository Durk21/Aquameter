<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    account: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    phone: props.account.phone,
    alternate_email: props.account.alternate_email,
});

const submit = () => {
    form.patch(route('profile.contact.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-display font-semibold text-ocean-900 dark:text-white">
                Contact Information
            </h2>

            <p class="mt-1 text-sm text-ocean-500 dark:text-neutral-400">
                A phone number and alternate email we can reach you on, separate from your login email.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <InputLabel for="phone" value="Phone Number" />

                <TextInput
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model="form.phone"
                    maxlength="20"
                    autocomplete="tel"
                    placeholder="e.g. 0712345678"
                />

                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div>
                <InputLabel for="alternate_email" value="Alternate Email" />

                <TextInput
                    id="alternate_email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.alternate_email"
                    maxlength="255"
                    autocomplete="email"
                />

                <InputError class="mt-2" :message="form.errors.alternate_email" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-ocean-500 dark:text-neutral-400">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
