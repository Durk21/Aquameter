<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    outages: {
        type: Array,
        required: true,
    },
});

const statusStyles = {
    scheduled: "bg-amber-100 text-amber-800",
    active: "bg-red-100 text-red-800",
    resolved: "bg-emerald-100 text-emerald-800",
};

const open = computed(() => props.outages.filter((o) => o.status !== "resolved"));
const resolved = computed(() => props.outages.filter((o) => o.status === "resolved"));

const resolve = (outage) => {
    router.patch(route("outages.resolve", outage.id));
};
</script>

<template>
    <Head title="Outage Notices" />

    <AppShell>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                    Outage Notices
                </h2>
                <Link
                    :href="route('outages.create')"
                    class="text-sm font-medium bg-ocean-600 hover:bg-ocean-700 text-white px-4 py-2 rounded-md transition-colors"
                >
                    Broadcast Notice
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6 space-y-8">
            <div class="flex md:hidden items-center justify-between">
                <h1 class="text-lg font-semibold text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                    Outage Notices
                </h1>
                <Link
                    :href="route('outages.create')"
                    class="text-sm font-medium bg-ocean-600 hover:bg-ocean-700 text-white px-3 py-1.5 rounded-md"
                >
                    Broadcast
                </Link>
            </div>

            <div v-if="$page.props.flash?.status" class="text-sm text-ocean-700 bg-ocean-50 rounded-md px-3 py-2">
                {{ $page.props.flash.status }}
            </div>

            <section>
                <h3 class="font-semibold text-ocean-900 mb-3">Scheduled &amp; Active</h3>
                <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden">
                    <div v-if="open.length === 0" class="p-6 text-ocean-700 text-sm">
                        No active or scheduled outages.
                    </div>
                    <div v-else class="divide-y divide-ocean-100">
                        <div v-for="o in open" :key="o.id" class="flex items-start justify-between gap-3 p-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="statusStyles[o.status] || 'bg-ocean-100 text-ocean-800'"
                                    >
                                        {{ o.status_label }}
                                    </span>
                                    <p class="font-medium text-ocean-900">{{ o.title }}</p>
                                </div>
                                <p class="text-xs text-ocean-500 mt-1">{{ o.zone || 'All zones' }} · Starts {{ o.starts_at }}<span v-if="o.ends_at"> · Ends {{ o.ends_at }}</span></p>
                                <p class="text-sm text-ocean-700 mt-1">{{ o.description }}</p>
                                <p class="text-xs text-ocean-400 mt-1">Broadcast by {{ o.created_by || 'System' }}</p>
                            </div>
                            <button
                                @click="resolve(o)"
                                class="shrink-0 inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold border border-ocean-300 text-ocean-700 hover:bg-ocean-50"
                            >
                                Mark Resolved
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="resolved.length > 0">
                <h3 class="font-semibold text-ocean-900 mb-3">Resolved</h3>
                <div class="bg-white rounded-lg border border-ocean-100 overflow-hidden divide-y divide-ocean-100">
                    <div v-for="o in resolved" :key="o.id" class="flex items-start justify-between gap-3 p-4">
                        <div>
                            <p class="font-medium text-ocean-900">{{ o.title }}</p>
                            <p class="text-xs text-ocean-500 mt-1">{{ o.zone || 'All zones' }} · Resolved {{ o.resolved_at }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 shrink-0">
                            {{ o.status_label }}
                        </span>
                    </div>
                </div>
            </section>
        </div>
    </AppShell>
</template>
