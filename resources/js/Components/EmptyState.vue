<script setup>
import Icon from "@/Components/Icon.vue";

defineProps({
    icon: {
        type: String,
        default: "droplets",
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center text-center py-14 px-6">
        <div class="w-14 h-14 rounded-2xl bg-gradient-ocean flex items-center justify-center text-white shadow-glow mb-4">
            <Icon :name="icon" :size="26" />
        </div>
        <p class="font-display font-semibold text-ocean-900 dark:text-neutral-100">{{ title }}</p>
        <p v-if="description" class="text-sm text-ocean-500 dark:text-neutral-400 mt-1 max-w-sm">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
