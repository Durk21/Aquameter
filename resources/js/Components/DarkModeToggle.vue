<script setup>
import Icon from "@/Components/Icon.vue";
import { useDarkMode } from "@/composables/useDarkMode";

const { isDark, toggleDarkMode } = useDarkMode();
</script>

<template>
    <button
        @click="toggleDarkMode"
        class="w-9 h-9 flex items-center justify-center rounded-md text-ocean-600 hover:bg-ocean-50 hover:text-ocean-800 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100 transition-colors"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
        <Icon :name="isDark ? 'sun' : 'moon'" :size="20" />
    </button>
</template>
