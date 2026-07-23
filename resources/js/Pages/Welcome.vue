<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLogo from "@/Components/AppLogo.vue";
import Icon from "@/Components/Icon.vue";
import Card from "@/Components/Card.vue";
import StatTile from "@/Components/StatTile.vue";
import DarkModeToggle from "@/Components/DarkModeToggle.vue";
import NetworkMap from "@/Components/NetworkMap.vue";
import { useScrollReveal } from "@/composables/useScrollReveal";
import { useDarkMode } from "@/composables/useDarkMode";

const { isDark } = useDarkMode();

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    pipeSegments: {
        type: Array,
        required: true,
    },
    zones: {
        type: Array,
        required: true,
    },
    zoneCenters: {
        type: Object,
        required: true,
    },
    serviceAreaBounds: {
        type: Object,
        required: true,
    },
});

const audiences = [
    {
        icon: "gauge",
        title: "For customers",
        points: [
            "See every meter reading as it's recorded",
            "Get flagged early if usage looks unusual",
            "One account for your address, meter, and history",
        ],
    },
    {
        icon: "wrench",
        title: "For field technicians",
        points: [
            "Assigned readings and jobs, nothing to hunt for",
            "Record a reading in under a minute, from any device",
            "Readings are locked once submitted — no disputes over what was entered",
        ],
    },
    {
        icon: "clipboard-list",
        title: "For management",
        points: [
            "Every account, meter, and reading in one place",
            "Role-based access — nobody sees more than their job needs",
            "Built to extend into billing, complaints, and outage control",
        ],
    },
];

const safeguards = [
    "You're notified before anything happens to your connection",
    "A human signs off before any disconnection is dispatched",
    "Raise a dispute and the process pauses, automatically",
];

const previewStats = [
    { label: "Active Accounts", value: "1,284", icon: "users", accent: "ocean", trend: { value: 4.2, label: "+4.2% this month" } },
    { label: "Avg. Response Time", value: "6.1h", icon: "zap", accent: "indigo" },
    { label: "Resolved On Time", value: "97%", icon: "shield-check", accent: "emerald" },
];

const { target: heroTarget, revealed: heroRevealed } = useScrollReveal();
const { target: audiencesTarget, revealed: audiencesRevealed } = useScrollReveal();
const { target: trustTarget, revealed: trustRevealed } = useScrollReveal();
const { target: mapTarget, revealed: mapRevealed } = useScrollReveal();
</script>

<template>
    <Head title="Aquameter — Water Utility Customer Service" />

    <div class="min-h-screen bg-white dark:bg-ocean-950 text-ocean-900 dark:text-white">
        <!-- Nav -->
        <header class="sticky top-0 z-30 border-b border-ocean-100 dark:border-white/5 bg-white/80 dark:bg-ocean-950/80 backdrop-blur-xl">
            <div class="max-w-6xl mx-auto px-5 md:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-ocean flex items-center justify-center shadow-soft">
                        <AppLogo :size="20" mono class="text-white" />
                    </div>
                    <span class="font-display font-semibold text-lg text-ocean-950 dark:text-white">
                        Aquameter
                    </span>
                </div>
                <nav class="flex items-center gap-1 sm:gap-4">
                    <DarkModeToggle />
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="whitespace-nowrap text-sm font-medium text-ocean-700 dark:text-neutral-300 hover:text-ocean-900 dark:hover:text-white px-2 sm:px-3 py-2"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="whitespace-nowrap inline-flex items-center gap-1.5 text-sm font-semibold bg-gradient-ocean text-white px-3 sm:px-4 py-2.5 rounded-lg shadow-soft hover:shadow-glow hover:-translate-y-0.5 transition-all duration-200"
                    >
                        Register
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-radial pointer-events-none opacity-70 dark:opacity-30"></div>
            <div
                ref="heroTarget"
                class="relative max-w-6xl mx-auto px-5 md:px-8 pt-14 pb-16 md:pt-20 md:pb-24 grid md:grid-cols-2 gap-12 items-center transition-all duration-700 ease-out"
                :class="heroRevealed ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            >
                <div>
                    <p class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-[0.14em] uppercase text-ocean-700 dark:text-ocean-300 bg-ocean-100 dark:bg-white/5 px-3 py-1.5 rounded-full mb-5">
                        <Icon name="sparkles" :size="13" />
                        Water service, digitized
                    </p>
                    <h1 class="text-4xl md:text-5xl font-display font-bold text-ocean-950 dark:text-white leading-[1.1] mb-5">
                        Know what you use.
                        <br />
                        <span class="bg-gradient-ocean bg-clip-text text-transparent">Fix what leaks.</span>
                    </h1>
                    <p class="text-ocean-700 dark:text-neutral-300 text-lg mb-8 max-w-md">
                        One account for meter readings, billing, and field service —
                        built so customers, technicians, and management each see exactly what they need.
                    </p>
                    <div class="flex items-center gap-5">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 bg-gradient-ocean text-white font-semibold px-6 py-3 rounded-lg shadow-soft hover:shadow-glow hover:-translate-y-0.5 transition-all duration-200"
                        >
                            Create an account
                            <Icon name="arrow-right" :size="16" />
                        </Link>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="text-ocean-700 dark:text-neutral-300 font-medium hover:text-ocean-900 dark:hover:text-white"
                        >
                            Log in →
                        </Link>
                    </div>
                </div>

                <div class="flex justify-center md:justify-end">
                    <div class="w-full max-w-sm space-y-4">
                        <Card class="flex justify-center py-6">
                            <svg width="200" height="200" viewBox="0 0 220 220">
                                <circle cx="110" cy="110" r="104" fill="#eefbfc" class="dark:opacity-10" />
                                <circle cx="110" cy="110" r="86" stroke="#d5f3f6" stroke-width="2" fill="none" />
                                <path
                                    d="M 44 132 A 66 66 0 1 1 176 132"
                                    fill="none"
                                    stroke="#aee6ec"
                                    stroke-width="10"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M 44 132 A 66 66 0 0 1 110 44"
                                    fill="none"
                                    stroke="#1f7e91"
                                    stroke-width="10"
                                    stroke-linecap="round"
                                />
                                <g class="hero-needle">
                                    <line x1="110" y1="110" x2="140" y2="80" :stroke="isDark ? '#e7f8fa' : '#204654'" stroke-width="3" stroke-linecap="round" />
                                    <circle cx="110" cy="110" r="6" :fill="isDark ? '#e7f8fa' : '#204654'" />
                                </g>
                                <text x="110" y="155" text-anchor="middle" font-size="20" font-weight="600" :fill="isDark ? '#e7f8fa' : '#204654'" class="font-mono">
                                    042.7 m³
                                </text>
                                <text x="110" y="172" text-anchor="middle" font-size="10" letter-spacing="1.5" :fill="isDark ? '#9fb8c4' : '#5C7688'">
                                    LATEST READING
                                </text>
                            </svg>
                        </Card>
                        <div class="grid grid-cols-1 gap-3">
                            <StatTile
                                v-for="stat in previewStats"
                                :key="stat.label"
                                :label="stat.label"
                                :value="stat.value"
                                :icon="stat.icon"
                                :accent="stat.accent"
                                :trend="stat.trend"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Three audiences -->
        <section class="bg-ocean-50 dark:bg-white/[0.03] border-y border-ocean-100 dark:border-white/5">
            <div
                ref="audiencesTarget"
                class="max-w-6xl mx-auto px-5 md:px-8 py-16 grid md:grid-cols-3 gap-6 transition-all duration-700 ease-out"
                :class="audiencesRevealed ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            >
                <Card v-for="audience in audiences" :key="audience.title" hover>
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-ocean-400 to-ocean-600 text-white flex items-center justify-center shadow-soft mb-4">
                        <Icon :name="audience.icon" :size="20" />
                    </div>
                    <h3 class="font-display font-semibold text-ocean-950 dark:text-white mb-3">
                        {{ audience.title }}
                    </h3>
                    <ul class="space-y-2">
                        <li v-for="point in audience.points" :key="point" class="text-sm text-ocean-700 dark:text-neutral-300 leading-snug">
                            {{ point }}
                        </li>
                    </ul>
                </Card>
            </div>
        </section>

        <!-- Trust strip -->
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-ocean-50/60 dark:from-white/[0.03] to-transparent pointer-events-none"></div>
            <div
                ref="trustTarget"
                class="relative max-w-4xl mx-auto px-5 md:px-8 py-16 text-center transition-all duration-700 ease-out"
                :class="trustRevealed ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            >
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-ocean-400 to-ocean-600 text-white shadow-soft mb-5">
                    <Icon name="shield-check" :size="24" />
                </div>
                <h2 class="text-2xl font-display font-bold text-ocean-950 dark:text-white mb-3">
                    Disconnection is never automatic.
                </h2>
                <p class="text-ocean-700 dark:text-neutral-300 max-w-lg mx-auto mb-8">
                    Losing your water connection is serious. Aquameter is built so it never happens silently.
                </p>
                <div class="grid sm:grid-cols-3 gap-4 text-left">
                    <Card v-for="point in safeguards" :key="point" :padded="true" class="flex gap-3 items-start">
                        <Icon name="shield-check" :size="16" class="text-ocean-500 dark:text-ocean-400 shrink-0 mt-0.5" />
                        <p class="text-sm text-ocean-700 dark:text-neutral-300">{{ point }}</p>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Network map -->
        <section class="bg-ocean-50 dark:bg-white/[0.03] border-y border-ocean-100 dark:border-white/5">
            <div
                ref="mapTarget"
                class="max-w-6xl mx-auto px-5 md:px-8 py-16 transition-all duration-700 ease-out"
                :class="mapRevealed ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            >
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-display font-bold text-ocean-950 dark:text-white mb-3">
                        See the network we maintain.
                    </h2>
                    <p class="text-ocean-700 dark:text-neutral-300 max-w-lg mx-auto">
                        Every mapped pipe segment across our coverage zones — sign in to see live incident reports on top of it.
                    </p>
                </div>
                <NetworkMap
                    read-only
                    :pipe-segments="pipeSegments"
                    :zones="zones"
                    :zone-centers="zoneCenters"
                    :service-area-bounds="serviceAreaBounds"
                    height="420px"
                />
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-ocean-100 dark:border-white/5">
            <div class="max-w-6xl mx-auto px-5 md:px-8 py-8 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <AppLogo :size="20" />
                    <span class="text-sm text-ocean-700 dark:text-neutral-300">Aquameter</span>
                </div>
                <p class="text-xs text-ocean-500 dark:text-neutral-500">Water utility customer service system</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.hero-needle {
    transform-origin: 110px 110px;
    animation: needleSweep 1.4s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes needleSweep {
    from {
        transform: rotate(-70deg);
    }
    to {
        transform: rotate(0deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .hero-needle {
        animation: none;
    }
}
</style>
