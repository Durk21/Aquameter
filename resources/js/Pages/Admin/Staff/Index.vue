<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { reactive } from "vue";

const props = defineProps({
    staff: {
        type: Array,
        required: true,
    },
    zones: {
        type: Array,
        required: true,
    },
});

const zoneSelections = reactive(
    Object.fromEntries(props.staff.filter((s) => s.role === "technician").map((s) => [s.id, s.zone || ""])),
);

const forms = reactive({});

const saveZone = (member) => {
    if (!forms[member.id]) {
        forms[member.id] = useForm({ zone: zoneSelections[member.id] });
    }

    forms[member.id].zone = zoneSelections[member.id];
    forms[member.id].patch(route("admin.staff.update-zone", member.id));
};

const roleStyles = {
    admin: "bg-indigo-100 text-indigo-800",
    technician: "bg-ocean-100 text-ocean-800",
    management: "bg-emerald-100 text-emerald-800",
};
</script>

<template>
    <Head title="Staff" />

    <AppShell>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                    Staff
                </h2>
                <Link
                    :href="route('admin.staff.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft hover:shadow-glow px-4 py-2 rounded-lg transition-shadow"
                >
                    Add Staff Account
                </Link>
            </div>
        </template>

        <div class="max-w-3xl mx-auto px-4 md:px-6 py-6">
            <div class="flex md:hidden items-center justify-between mb-4">
                <h1 class="font-display text-lg font-semibold text-ocean-900 dark:text-white">
                    Staff
                </h1>
                <Link
                    :href="route('admin.staff.create')"
                    class="text-sm font-medium bg-gradient-ocean text-white shadow-soft px-3 py-1.5 rounded-lg"
                >
                    Add
                </Link>
            </div>

            <p class="text-sm text-ocean-500 dark:text-neutral-400 mb-4">
                Admin, technician, and management accounts are provisioned here — they don't self-register.
                Assigning a technician's zone routes jobs in that zone to the front of their dispatch queue;
                any technician can still manually claim a job in any zone.
            </p>

            <div v-if="$page.props.flash?.status" class="mb-4 text-sm text-ocean-700 dark:text-neutral-300 bg-ocean-50 rounded-md px-3 py-2">
                {{ $page.props.flash.status }}
            </div>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft overflow-hidden">
                <div v-if="staff.length === 0" class="p-6 text-ocean-600 dark:text-neutral-400 text-sm">
                    No staff accounts yet.
                </div>
                <div v-else class="divide-y divide-ocean-100 dark:divide-white/5">
                    <div v-for="member in staff" :key="member.id" class="flex items-center justify-between gap-3 p-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-ocean-900 dark:text-white">{{ member.name }}</p>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="roleStyles[member.role] || 'bg-ocean-100 text-ocean-800'"
                                >
                                    {{ member.role_label }}
                                </span>
                            </div>
                            <p class="text-xs text-ocean-500 dark:text-neutral-400 mt-0.5">{{ member.email }}</p>
                        </div>
                        <div v-if="member.role === 'technician'" class="flex items-center gap-2">
                            <select
                                v-model="zoneSelections[member.id]"
                                class="text-sm border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                            >
                                <option value="">No zone</option>
                                <option v-for="z in zones" :key="z" :value="z">{{ z }}</option>
                            </select>
                            <button
                                @click="saveZone(member)"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700"
                            >
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>
