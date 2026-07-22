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
        { label: "Ask Aquameter", route: "customer.assistant.index", icon: "sparkles" },
        { label: "Meter Readings", route: "customer.meter-readings.index", icon: "gauge" },
        { label: "Bills", route: "customer.bills.index", icon: "clipboard-list" },
        { label: "Complaints", route: "customer.complaints.index", icon: "alert-triangle" },
        { label: "Leak Reports", route: "customer.leak-reports.index", icon: "droplet" },
        { label: "Service Requests", route: "customer.service-requests.index", icon: "wrench" },
        { label: "Outages", route: "customer.outages.index", icon: "megaphone" },
        { label: "Network Map", route: "map.index", icon: "map-pin" },
    ],
    technician: [
        { label: "Dashboard", route: "technician.dashboard", icon: "home" },
        { label: "Record Reading", route: "technician.meter-readings.create", icon: "gauge" },
        { label: "Work Orders", route: "technician.work-orders.index", icon: "shield-check" },
        { label: "Network Map", route: "map.index", icon: "map-pin" },
    ],
    admin: [
        { label: "Dashboard", route: "admin.dashboard", icon: "home" },
        { label: "Register Meter", route: "admin.meters.create", icon: "wrench" },
        { label: "Bills", route: "admin.bills.index", icon: "clipboard-list" },
        { label: "Complaints", route: "admin.complaints.index", icon: "alert-triangle" },
        { label: "Work Orders", route: "admin.work-orders.index", icon: "shield-check" },
        { label: "Staff", route: "admin.staff.index", icon: "user" },
        { label: "Maintenance", route: "admin.maintenance.index", icon: "calendar" },
        { label: "Outages", route: "outages.index", icon: "megaphone" },
        { label: "Network Map", route: "map.index", icon: "map-pin" },
        { label: "Pipe Network", route: "admin.pipe-segments.index", icon: "droplets" },
        { label: "Activity Log", route: "activity.index", icon: "history" },
    ],
    management: [
        { label: "Dashboard", route: "management.dashboard", icon: "home" },
        { label: "Outages", route: "outages.index", icon: "megaphone" },
        { label: "Network Map", route: "map.index", icon: "map-pin" },
        { label: "Activity Log", route: "activity.index", icon: "history" },
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
    <div class="min-h-screen bg-ocean-50 dark:bg-ocean-950">
        <!-- Desktop sidebar -->
        <aside class="hidden md:flex md:flex-col md:fixed md:inset-y-0 md:w-64 bg-white dark:bg-ocean-900/40 dark:backdrop-blur-xl border-r border-ocean-100 dark:border-white/5">
            <div class="flex items-center gap-2.5 px-6 h-16 border-b border-ocean-100 dark:border-white/5">
                <div class="w-8 h-8 rounded-lg bg-gradient-ocean flex items-center justify-center shadow-soft">
                    <AppLogo :size="18" mono class="text-white" />
                </div>
                <span class="font-display font-semibold text-ocean-900 dark:text-white tracking-tight">
                    Aquameter
                </span>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="relative flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150"
                    :class="isActive(item.route)
                        ? 'bg-gradient-to-r from-ocean-50 to-transparent dark:from-white/10 dark:to-transparent text-ocean-700 dark:text-white'
                        : 'text-ocean-600 dark:text-ocean-200/70 hover:bg-ocean-50 dark:hover:bg-white/5 hover:text-ocean-700 dark:hover:text-white'"
                >
                    <span
                        v-if="isActive(item.route)"
                        class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-1 rounded-full bg-gradient-ocean"
                    />
                    <Icon :name="item.icon" :size="18" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="border-t border-ocean-100 dark:border-white/5 p-3 relative">
                <button
                    @click="userMenuOpen = !userMenuOpen"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-ocean-700 hover:bg-ocean-50 dark:text-ocean-100 dark:hover:bg-white/5 transition-colors"
                >
                    <span class="w-7 h-7 rounded-full bg-gradient-ocean flex items-center justify-center text-white text-xs font-semibold shrink-0">
                        {{ user?.name?.charAt(0)?.toUpperCase() }}
                    </span>
                    <span class="flex-1 text-left truncate">{{ user?.name }}</span>
                    <Icon name="chevron-down" :size="16" />
                </button>

                <div
                    v-if="userMenuOpen"
                    class="absolute bottom-full left-3 right-3 mb-1 bg-white dark:bg-ocean-900 border border-ocean-100 dark:border-white/10 rounded-xl shadow-elevated overflow-hidden"
                >
                    <Link
                        :href="route('profile.edit')"
                        class="block px-3 py-2.5 text-sm text-ocean-700 hover:bg-ocean-50 dark:text-ocean-100 dark:hover:bg-white/5"
                    >
                        Profile
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-full text-left px-3 py-2.5 text-sm text-ocean-700 hover:bg-ocean-50 dark:text-ocean-100 dark:hover:bg-white/5"
                    >
                        Log out
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Mobile top bar -->
        <header class="md:hidden sticky top-0 z-20 flex items-center justify-between h-14 px-4 bg-white/90 dark:bg-ocean-900/70 backdrop-blur-xl border-b border-ocean-100 dark:border-white/5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-ocean flex items-center justify-center">
                    <AppLogo :size="16" mono class="text-white" />
                </div>
                <span class="font-display font-semibold text-ocean-900 dark:text-white">
                    Aquameter
                </span>
            </div>
            <div class="flex items-center gap-3">
                <DarkModeToggle />
                <NotificationBell />
                <Link :href="route('profile.edit')" class="text-ocean-600 dark:text-ocean-200">
                    <Icon name="user" :size="20" />
                </Link>
            </div>
        </header>

        <!-- Main content -->
        <div class="md:pl-64">
            <div class="hidden md:flex items-center justify-end gap-4 h-16 px-6 bg-white/80 dark:bg-ocean-900/30 dark:backdrop-blur-xl border-b border-ocean-100 dark:border-white/5">
                <DarkModeToggle />
                <NotificationBell />
            </div>

            <header v-if="$slots.header" class="hidden md:block bg-white/80 dark:bg-ocean-900/30 dark:backdrop-blur-xl border-b border-ocean-100 dark:border-white/5">
                <div class="max-w-5xl mx-auto px-6 py-4">
                    <slot name="header" />
                </div>
            </header>

            <main class="pb-20 md:pb-6">
                <slot />
            </main>
        </div>

        <!-- Mobile bottom tab bar -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-20 bg-white/90 dark:bg-ocean-900/80 backdrop-blur-xl border-t border-ocean-100 dark:border-white/5 shadow-elevated overflow-x-auto">
            <div class="flex min-w-max sm:min-w-full">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex-1 flex flex-col items-center gap-0.5 py-2.5 px-3.5 text-[11px] font-medium transition-colors whitespace-nowrap"
                    :class="isActive(item.route) ? 'text-ocean-700 dark:text-white' : 'text-ocean-400 dark:text-ocean-300/50'"
                >
                    <Icon :name="item.icon" :size="20" />
                    {{ item.label }}
                </Link>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex-1 flex flex-col items-center gap-0.5 py-2.5 px-3.5 text-[11px] font-medium text-ocean-400 dark:text-ocean-300/50 whitespace-nowrap"
                >
                    <Icon name="log-out" :size="20" />
                    Log out
                </Link>
            </div>
        </nav>
    </div>
</template>
