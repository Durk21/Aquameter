<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import Card from "@/Components/Card.vue";
import Badge from "@/Components/Badge.vue";
import EmptyState from "@/Components/EmptyState.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { reactive } from "vue";

const props = defineProps({
    pipeSegments: {
        type: Array,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
});

const statusTones = {
    active: "ocean",
    maintenance: "amber",
    damaged: "red",
};

const statusSelections = reactive(Object.fromEntries(props.pipeSegments.map((s) => [s.id, s.status])));
const forms = reactive({});

const saveStatus = (segment) => {
    if (!forms[segment.id]) {
        forms[segment.id] = useForm({ status: statusSelections[segment.id] });
    }

    forms[segment.id].status = statusSelections[segment.id];
    forms[segment.id].patch(route("admin.pipe-segments.update-status", segment.id));
};

const destroy = (segment) => {
    if (confirm(`Remove "${segment.name}"? This can't be undone.`)) {
        router.delete(route("admin.pipe-segments.destroy", segment.id));
    }
};
</script>

<template>
    <Head title="Pipe Network" />

    <AppShell>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                    Pipe Network
                </h2>
                <Link
                    :href="route('admin.pipe-segments.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft hover:shadow-glow px-4 py-2 rounded-lg transition-shadow"
                >
                    Add Segment
                </Link>
            </div>
        </template>

        <div class="max-w-3xl mx-auto px-4 md:px-6 py-6">
            <div class="flex md:hidden items-center justify-between mb-4">
                <h1 class="font-display text-lg font-semibold text-ocean-900 dark:text-white">
                    Pipe Network
                </h1>
                <Link
                    :href="route('admin.pipe-segments.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft px-3 py-1.5 rounded-lg"
                >
                    Add
                </Link>
            </div>

            <p class="text-sm text-ocean-500 dark:text-neutral-400 mb-4">
                Pipe segments drawn here appear on the shared Network Map for every role, and on the public landing page.
            </p>

            <div v-if="$page.props.flash?.status" class="mb-4 text-sm text-ocean-700 dark:text-neutral-300 bg-ocean-50 rounded-md px-3 py-2">
                {{ $page.props.flash.status }}
            </div>

            <Card :padded="false">
                <EmptyState
                    v-if="pipeSegments.length === 0"
                    icon="droplets"
                    title="No pipe segments yet"
                    description="Add the first section of the network by tracing it on a map."
                />

                <div v-else class="divide-y divide-ocean-100 dark:divide-white/5">
                    <div v-for="segment in pipeSegments" :key="segment.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-ocean-900 dark:text-white">{{ segment.name }}</p>
                                <Badge :tone="statusTones[segment.status] || 'ocean'">{{ segment.status_label }}</Badge>
                            </div>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-0.5">
                                {{ segment.zone }} · {{ segment.points.length }} points · added {{ segment.created_at }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="statusSelections[segment.id]"
                                class="text-sm border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                            >
                                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                            <button
                                @click="saveStatus(segment)"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                            >
                                Save
                            </button>
                            <button
                                @click="destroy(segment)"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppShell>
</template>
