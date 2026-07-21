<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, router } from "@inertiajs/vue3";

defineProps({
    notifications: {
        type: Array,
        required: true,
    },
});

function title(n) {
    return n.data.title ?? n.data.subject ?? "Notification";
}

function message(n) {
    return n.data.message ?? n.data.status_label ?? "";
}

function markRead(notification) {
    if (notification.read_at) return;
    router.patch(route("notifications.read", notification.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Notifications
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Notifications
            </h1>

            <div v-if="notifications.length === 0" class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6 text-ocean-600 dark:text-neutral-400">
                No notifications yet.
            </div>

            <div v-else class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft overflow-hidden">
                <button
                    v-for="n in notifications"
                    :key="n.id"
                    @click="markRead(n)"
                    class="w-full text-left px-5 py-4 border-b border-ocean-50 last:border-none hover:bg-ocean-50 transition-colors flex items-start gap-3"
                    :class="{ 'bg-ocean-50/50': !n.read_at }"
                >
                    <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                    <span v-else class="w-2 h-2 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ocean-900 dark:text-white">{{ title(n) }}</p>
                        <p class="text-sm text-ocean-700 dark:text-neutral-300 mt-0.5">{{ message(n) }}</p>
                        <p v-if="n.data.resolution_notes" class="text-sm text-ocean-600 mt-1">{{ n.data.resolution_notes }}</p>
                        <p class="text-xs text-ocean-400 dark:text-neutral-500 mt-1">{{ n.created_at }}</p>
                    </div>
                </button>
            </div>
        </div>
    </AppShell>
</template>
