<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AppShell>
        <div class="max-w-3xl mx-auto px-4 md:px-6 py-6">
            <p class="text-ocean-700 dark:text-neutral-300">You're logged in!</p>
        </div>
    </AppShell>
</template>
