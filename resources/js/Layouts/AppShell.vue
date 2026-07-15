<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLogo from "@/Components/AppLogo.vue";
import Icon from "@/Components/Icon.vue";
import NotificationBell from "@/Components/NotificationBell.vue";
import DarkModeToggle from "@/Components/DarkModeToggle.vue";

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => page.props.auth.roles || []);
const userMenuOpen = ref(false);

const navByRole = {
    customer: [
        { label: "Dashboard", route: "customer.dashboard", icon: "home" },
        { label: "Meter Readings", route: "customer.meter-readings.index", icon: "gauge" },
        { label: "Bills", route: "customer.bills.index", icon: "clipboard-list" },
        { label: "Complaints", route: "customer.complaints.index", icon: "alert-triangle" },
        { label: "Leak Reports", route: "customer.leak-reports.index", icon: "droplet" },
        { label: "Service Requests", route: "customer.service-requests.index", icon: "wrench" },
    ],
    technician: [
        { label: "Dashboard", route: "technician.dashboard", icon: "home" },
        { label: "Record Reading", route: "technician.meter-readings.create", icon: "gauge" },
        { label: "Work Orders", route: "technician.work-orders.index", icon: "shield-check" },
    ],
    admin: [
        { label: "Dashboard", route: "admin.dashboard", icon: "home" },
        { label: "Register Meter", route: "admin.meters.create", icon: "wrench" },
        { label: "Bills", route: "admin.bills.index", icon: "clipboard-list" },
        { label: "Complaints", route: "admin.complaints.index", icon: "alert-triangle" },
        { label: "Work Orders", route: "admin.work-orders.index", icon: "shield-check" },
        { label: "Staff", route: "admin.staff.index", icon: "user" },
        { label: "Maintenance", route: "admin.maintenance.index", icon: "calendar" },
    ],
    management: [
        { label: "Dashboard", route: "management.dashboard", icon: "home" },
    ],
};

const navItems = computed(() => {
    for (const role of roles.value) {
        if (navByRole[role]) return navByRole[role];
    }
    return [];
});

const isActive = (routeName) => route().current(routeName);
</script>

<template>
    <div class="min-h-screen bg-ocean-50 dark:bg-neutral-950">
        <!-- Desktop sidebar -->
        <aside class="hidden md:flex md:flex-col md:fixed md:inset-y-0 md:w-60 bg-white dark:bg-neutral-900 border-r border-ocean-100 dark:border-neutral-800">
            <div class="flex items-center gap-2 px-6 h-16 border-b border-ocean-100 dark:border-neutral-800">
                <div class="flex items-center gap-2">
                    <AppLogo :size="28" />
                    <span class="font-semibold text-ocean-900 dark:text-neutral-100 tracking-tight" style="font-family: 'Space Grotesk', sans-serif;">
                        Aquameter
                    </span>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                    :class="isActive(item.route)
                        ? 'bg-ocean-50 text-ocean-700 dark:bg-neutral-800 dark:text-neutral-100'
                        : 'text-ocean-600 hover:bg-ocean-50 hover:text-ocean-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100'"
                >
                    <Icon :name="item.icon" :size="18" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="border-t border-ocean-100 dark:border-neutral-800 p-3 relative">
                <button
                    @click="userMenuOpen = !userMenuOpen"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-sm text-ocean-700 hover:bg-ocean-50 dark:text-neutral-300 dark:hover:bg-neutral-800"
                >
                    <Icon name="user" :size="18" />
                    <span class="flex-1 text-left truncate">{{ user?.name }}</span>
                    <Icon name="chevron-down" :size="16" />
                </button>

                <div
                    v-if="userMenuOpen"
                    class="absolute bottom-full left-3 right-3 mb-1 bg-white dark:bg-neutral-900 border border-ocean-100 dark:border-neutral-800 rounded-md shadow-lg overflow-hidden"
                >
                    <Link
                        :href="route('profile.edit')"
                        class="block px-3 py-2 text-sm text-ocean-700 hover:bg-ocean-50 dark:text-neutral-300 dark:hover:bg-neutral-800"
                    >
                        Profile
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-full text-left px-3 py-2 text-sm text-ocean-700 hover:bg-ocean-50 dark:text-neutral-300 dark:hover:bg-neutral-800"
                    >
                        Log out
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Mobile top bar -->
        <header class="md:hidden sticky top-0 z-20 flex items-center justify-between h-14 px-4 bg-white dark:bg-neutral-900 border-b border-ocean-100 dark:border-neutral-800">
            <div class="flex items-center gap-2">
                <AppLogo :size="24" />
                <span class="font-semibold text-ocean-900 dark:text-neutral-100" style="font-family: 'Space Grotesk', sans-serif;">
                    Aquameter
                </span>
            </div>
            <div class="flex items-center gap-4">
                <DarkModeToggle />
                <NotificationBell />
                <Link :href="route('profile.edit')" class="text-ocean-600 dark:text-neutral-400">
                    <Icon name="user" :size="20" />
                </Link>
            </div>
        </header>

        <!-- Main content -->
        <div class="md:pl-60">
            <div class="hidden md:flex items-center justify-end gap-4 h-16 px-6 bg-white dark:bg-neutral-900 border-b border-ocean-100 dark:border-neutral-800">
                <DarkModeToggle />
                <NotificationBell />
            </div>

            <header v-if="$slots.header" class="hidden md:block bg-white dark:bg-neutral-900 border-b border-ocean-100 dark:border-neutral-800">
                <div class="max-w-5xl mx-auto px-6 py-4">
                    <slot name="header" />
                </div>
            </header>

            <main class="pb-20 md:pb-6">
                <slot />
            </main>
        </div>

        <!-- Mobile bottom tab bar -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-20 bg-white dark:bg-neutral-900 border-t border-ocean-100 dark:border-neutral-800 flex">
            <Link
                v-for="item in navItems"
                :key="item.route"
                :href="route(item.route)"
                class="flex-1 flex flex-col items-center gap-0.5 py-2.5 text-xs font-medium"
                :class="isActive(item.route) ? 'text-ocean-700 dark:text-neutral-100' : 'text-ocean-400 dark:text-neutral-500'"
            >
                <Icon :name="item.icon" :size="20" />
                {{ item.label }}
            </Link>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="flex-1 flex flex-col items-center gap-0.5 py-2.5 text-xs font-medium text-ocean-400 dark:text-neutral-500"
            >
                <Icon name="log-out" :size="20" />
                Log out
            </Link>
        </nav>
    </div>
</template>
