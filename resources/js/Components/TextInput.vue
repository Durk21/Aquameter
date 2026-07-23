<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-lg border-ocean-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-ocean-900 dark:text-neutral-100 shadow-sm transition-colors placeholder:text-ocean-300 dark:placeholder:text-neutral-500 focus:border-ocean-500 focus:ring-ocean-500 dark:focus:ring-ocean-400"
        v-model="model"
        ref="input"
    />
</template>
