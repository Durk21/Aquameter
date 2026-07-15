<script setup>
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, ref, nextTick } from "vue";
import Icon from "@/Components/Icon.vue";

const page = usePage();
const open = ref(false);
const buttonRef = ref(null);
const position = ref({ top: 0, right: 0 });

const unreadCount = computed(() => page.props.notifications?.unread_count || 0);
const recent = computed(() => page.props.notifications?.recent || []);

function title(n) {
    return n.data.title ?? n.data.subject ?? "Notification";
}

function subtitle(n) {
    return n.data.message ?? n.data.status_label ?? "";
}

function updatePosition() {
    nextTick(() => {
        const rect = buttonRef.value?.getBoundingClientRect();
        if (!rect) return;
        position.value = {
            top: rect.bottom + 8,
            right: Math.max(8, window.innerWidth - rect.right),
        };
    });
}

function toggle() {
    if (!open.value) updatePosition();
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function markRead(notification) {
    if (notification.read_at) return;
    router.patch(route("notifications.read", notification.id), {}, { preserveScroll: true });
}

function markAllRead() {
    router.patch(route("notifications.read-all"), {}, { preserveScroll: true });
}
</script>

<template>
    <div class="relative">
        <button
            ref="buttonRef"
            @click="toggle"
            class="relative w-9 h-9 flex items-center justify-center rounded-md text-ocean-600 hover:bg-ocean-50 hover:text-ocean-800 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100 transition-colors"
            aria-label="Notifications"
        >
            <Icon name="bell" :size="20" />
            <span
                v-if="unreadCount > 0"
                class="absolute top-1 right-1 min-w-[16px] h-4 px-1 rounded-full bg-amber-500 text-white text-[10px] font-semibold flex items-center justify-center"
            >
                {{ unreadCount > 9 ? "9+" : unreadCount }}
            </span>
        </button>

        <Teleport to="body">
            <div v-if="open" class="fixed inset-0 z-40" @click="close" />

            <div
                v-if="open"
                class="fixed z-50 w-80 max-h-96 overflow-y-auto bg-white dark:bg-neutral-900 border border-ocean-100 dark:border-neutral-800 rounded-lg shadow-lg"
                :style="{ top: position.top + 'px', right: position.right + 'px' }"
            >
                <div class="flex items-center justify-between px-4 py-3 border-b border-ocean-100 dark:border-neutral-800">
                    <span class="text-sm font-semibold text-ocean-900 dark:text-neutral-100">Notifications</span>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-xs text-ocean-600 hover:text-ocean-800 dark:text-neutral-400 dark:hover:text-neutral-100"
                    >
                        Mark all read
                    </button>
                </div>

                <div v-if="recent.length === 0" class="px-4 py-6 text-sm text-ocean-500 dark:text-neutral-500 text-center">
                    No notifications yet.
                </div>

                <button
                    v-for="n in recent"
                    :key="n.id"
                    @click="markRead(n)"
                    class="w-full text-left px-4 py-3 border-b border-ocean-50 dark:border-neutral-800 last:border-none hover:bg-ocean-50 dark:hover:bg-neutral-800 transition-colors"
                    :class="{ 'bg-ocean-50/60 dark:bg-neutral-800/60': !n.read_at }"
                >
                    <div class="flex items-start gap-2">
                        <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                        <div class="min-w-0">
                            <p class="text-sm text-ocean-900 dark:text-neutral-100 truncate">{{ title(n) }}</p>
                            <p class="text-xs text-ocean-500 dark:text-neutral-500 truncate">{{ subtitle(n) }} · {{ n.created_at }}</p>
                        </div>
                    </div>
                </button>

                <Link
                    :href="route('notifications.index')"
                    class="block text-center text-xs font-medium text-ocean-600 hover:text-ocean-800 dark:text-neutral-400 dark:hover:text-neutral-100 px-4 py-3 border-t border-ocean-100 dark:border-neutral-800"
                    @click="close"
                >
                    View all
                </Link>
            </div>
        </Teleport>
    </div>
</template>
