<script setup>
import AppLogo from "@/Components/AppLogo.vue";
import DarkModeToggle from "@/Components/DarkModeToggle.vue";
import Icon from "@/Components/Icon.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
    title: {
        type: String,
        default: "",
    },
    subtitle: {
        type: String,
        default: "",
    },
});

const highlights = [
    { icon: "gauge", text: "Real-time meter readings and billing" },
    { icon: "wrench", text: "Field dispatch for leaks and repairs" },
    { icon: "shield-check", text: "Transparent disconnection safeguards" },
];
</script>

<template>
    <div class="min-h-screen flex bg-ocean-50 dark:bg-neutral-950">
        <!-- Brand panel -->
        <div class="hidden lg:flex lg:w-[45%] xl:w-2/5 relative overflow-hidden bg-gradient-ocean">
            <div class="absolute inset-0 bg-gradient-radial from-white/10 via-transparent to-transparent"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-16 w-96 h-96 rounded-full bg-ocean-900/30 blur-3xl"></div>

            <div class="relative z-10 flex flex-col justify-between p-12 text-white w-full">
                <Link href="/" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-white/15 backdrop-blur flex items-center justify-center">
                        <AppLogo :size="22" class="text-white" />
                    </div>
                    <span class="font-display font-semibold text-lg tracking-tight">Aquameter</span>
                </Link>

                <div>
                    <h2 class="font-display text-3xl xl:text-4xl font-semibold leading-tight mb-4">
                        Water utility operations,<br />clearly in view.
                    </h2>
                    <p class="text-ocean-50/80 text-sm max-w-sm mb-8">
                        Billing, field dispatch, and customer service in one place — built for utilities that want fewer surprises and faster resolutions.
                    </p>
                    <ul class="space-y-3">
                        <li v-for="item in highlights" :key="item.text" class="flex items-center gap-3 text-sm text-ocean-50/90">
                            <span class="w-8 h-8 rounded-lg bg-white/10 backdrop-blur flex items-center justify-center shrink-0">
                                <Icon :name="item.icon" :size="16" />
                            </span>
                            {{ item.text }}
                        </li>
                    </ul>
                </div>

                <p class="text-xs text-ocean-50/50">© {{ new Date().getFullYear() }} Aquameter. All rights reserved.</p>
            </div>
        </div>

        <!-- Form panel -->
        <div class="flex-1 flex flex-col">
            <div class="flex items-center justify-between p-6">
                <Link href="/" class="flex lg:hidden items-center gap-2">
                    <AppLogo :size="26" />
                    <span class="font-display font-semibold text-ocean-900 dark:text-neutral-100">Aquameter</span>
                </Link>
                <span class="hidden lg:block"></span>
                <DarkModeToggle />
            </div>

            <div class="flex-1 flex items-center justify-center px-6 pb-12">
                <div class="w-full max-w-sm">
                    <div v-if="title" class="mb-8">
                        <h1 class="font-display text-2xl font-semibold text-ocean-900 dark:text-neutral-50">{{ title }}</h1>
                        <p v-if="subtitle" class="mt-1.5 text-sm text-ocean-500 dark:text-neutral-400">{{ subtitle }}</p>
                    </div>

                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
