<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    categories: {
        type: Object,
        required: true,
    },
    preferences: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    preferences: { ...props.preferences },
});

const submit = () => {
    form.patch(route('profile.notifications.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-display font-semibold text-ocean-900 dark:text-white">
                Email Notifications
            </h2>

            <p class="mt-1 text-sm text-ocean-500 dark:text-neutral-400">
                Choose what we email you about. You'll always see everything in your in-app notification bell either way.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-4">
            <div v-for="(label, category) in categories" :key="category" class="flex items-center gap-2">
                <Checkbox :id="`pref-${category}`" v-model:checked="form.preferences[category]" />
                <label :for="`pref-${category}`" class="text-sm text-ocean-700 dark:text-neutral-300">{{ label }}</label>
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
