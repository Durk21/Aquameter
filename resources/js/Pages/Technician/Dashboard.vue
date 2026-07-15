<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, Link } from "@inertiajs/vue3";
</script>

<template>
    <Head title="Technician Dashboard" />

    <AppShell>
        <template #header>
            <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                Dashboard
            </h2>
        </template>

        <div class="max-w-5xl mx-auto px-4 md:px-6 py-6">
            <div class="bg-white rounded-lg border border-ocean-100 p-6">
                <p class="text-ocean-700">
                    Record meter readings and manage your assigned work from here.
                </p>
                <Link
                    :href="route('technician.work-orders.index')"
                    class="inline-flex items-center mt-4 px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                >
                    View Work Orders
                </Link>
            </div>
        </div>
    </AppShell>
</template>
