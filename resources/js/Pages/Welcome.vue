<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLogo from "@/Components/AppLogo.vue";
import Icon from "@/Components/Icon.vue";

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
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
</script>

<template>
    <Head title="Aquameter — Water Utility Customer Service" />

    <div class="min-h-screen bg-white" style="font-family: 'Inter', sans-serif;">
        <!-- Nav -->
        <header class="border-b border-ocean-100">
            <div class="max-w-6xl mx-auto px-5 md:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <AppLogo :size="28" />
                    <span class="font-semibold text-ocean-900" style="font-family: 'Space Grotesk', sans-serif;">
                        Aquameter
                    </span>
                </div>
                <nav class="flex items-center gap-5">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="text-sm font-medium text-ocean-700 hover:text-ocean-900"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="text-sm font-medium bg-ocean-600 hover:bg-ocean-700 text-white px-4 py-2 rounded-md transition-colors"
                    >
                        Register
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="max-w-6xl mx-auto px-5 md:px-8 pt-14 pb-16 md:pt-20 md:pb-24 grid md:grid-cols-2 gap-12 items-center">
            <div>
                <p class="text-xs font-semibold tracking-[0.18em] uppercase text-ocean-600 mb-4">
                    Water service, digitized
                </p>
                <h1
                    class="text-4xl md:text-5xl font-bold text-ocean-950 leading-[1.1] mb-5"
                    style="font-family: 'Space Grotesk', sans-serif;"
                >
                    Know what you use.
                    <br />
                    Fix what leaks.
                </h1>
                <p class="text-ocean-700 text-lg mb-8 max-w-md">
                    One account for meter readings, billing, and field service —
                    built so customers, technicians, and management each see exactly what they need.
                </p>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="bg-ocean-600 hover:bg-ocean-700 text-white font-medium px-6 py-3 rounded-md transition-colors"
                    >
                        Create an account
                    </Link>
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="text-ocean-700 font-medium hover:text-ocean-900"
                    >
                        Log in →
                    </Link>
                </div>
            </div>

            <div class="flex justify-center md:justify-end">
                <div class="relative">
                    <svg width="220" height="220" viewBox="0 0 220 220">
                        <circle cx="110" cy="110" r="104" fill="#eefbfc" />
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
                            <line x1="110" y1="110" x2="140" y2="80" stroke="#204654" stroke-width="3" stroke-linecap="round" />
                            <circle cx="110" cy="110" r="6" fill="#204654" />
                        </g>
                        <text x="110" y="155" text-anchor="middle" font-size="20" font-weight="600" fill="#204654" style="font-family: 'JetBrains Mono', monospace;">
                            042.7 m³
                        </text>
                        <text x="110" y="172" text-anchor="middle" font-size="10" letter-spacing="1.5" fill="#5C7688">
                            LATEST READING
                        </text>
                    </svg>
                </div>
            </div>
        </section>

        <!-- Three audiences -->
        <section class="bg-ocean-50 border-y border-ocean-100">
            <div class="max-w-6xl mx-auto px-5 md:px-8 py-16 grid md:grid-cols-3 gap-8">
                <div v-for="audience in audiences" :key="audience.title" class="bg-white rounded-xl border border-ocean-100 p-6">
                    <div class="w-10 h-10 rounded-lg bg-ocean-100 flex items-center justify-center text-ocean-700 mb-4">
                        <Icon :name="audience.icon" :size="20" />
                    </div>
                    <h3 class="font-semibold text-ocean-950 mb-3" style="font-family: 'Space Grotesk', sans-serif;">
                        {{ audience.title }}
                    </h3>
                    <ul class="space-y-2">
                        <li v-for="point in audience.points" :key="point" class="text-sm text-ocean-700 leading-snug">
                            {{ point }}
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Trust strip -->
        <section class="max-w-4xl mx-auto px-5 md:px-8 py-16 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-ocean-100 text-ocean-700 mb-5">
                <Icon name="shield-check" :size="24" />
            </div>
            <h2 class="text-2xl font-bold text-ocean-950 mb-3" style="font-family: 'Space Grotesk', sans-serif;">
                Disconnection is never automatic.
            </h2>
            <p class="text-ocean-700 max-w-lg mx-auto mb-8">
                Losing your water connection is serious. Aquameter is built so it never happens silently.
            </p>
            <div class="grid sm:grid-cols-3 gap-6 text-left">
                <div v-for="point in safeguards" :key="point" class="flex gap-3">
                    <Icon name="shield-check" :size="16" class="text-ocean-500 shrink-0 mt-0.5" />
                    <p class="text-sm text-ocean-700">{{ point }}</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-ocean-100">
            <div class="max-w-6xl mx-auto px-5 md:px-8 py-8 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <AppLogo :size="20" />
                    <span class="text-sm text-ocean-700">Aquameter</span>
                </div>
                <p class="text-xs text-ocean-500">Water utility customer service system</p>
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
