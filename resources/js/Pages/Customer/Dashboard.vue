<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <Head title="Customer Dashboard" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Dashboard
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6">
            <div class="bg-white rounded-lg border border-ocean-100 p-6">
                <p class="text-ocean-700">
                    Welcome back. Your account, meter readings, and billing will appear here as Aquameter grows.
                </p>
            </div>
        </div>
    </AppShell>
</template>
