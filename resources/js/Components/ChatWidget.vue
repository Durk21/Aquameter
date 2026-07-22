<script setup>
import Icon from "@/Components/Icon.vue";
import InlineDroplet from "@/Components/InlineDroplet.vue";
import { nextTick, ref } from "vue";

const open = ref(false);
const loaded = ref(false);
const loadingHistory = ref(false);
const thread = ref([]);
const draft = ref("");
const sending = ref(false);
const errorText = ref("");
const scrollArea = ref(null);

function scrollToBottom() {
    nextTick(() => {
        if (scrollArea.value) {
            scrollArea.value.scrollTop = scrollArea.value.scrollHeight;
        }
    });
}

async function toggle() {
    open.value = !open.value;
    if (!open.value) return;

    scrollToBottom();
    if (loaded.value) return;

    loadingHistory.value = true;
    try {
        const response = await window.axios.get(route("customer.assistant.messages"));
        thread.value = response.data.messages;
        loaded.value = true;
    } catch (error) {
        errorText.value = "Couldn't load your conversation history.";
    } finally {
        loadingHistory.value = false;
        scrollToBottom();
    }
}

async function send() {
    const message = draft.value.trim();
    if (!message || sending.value) return;

    errorText.value = "";
    thread.value.push({
        id: `pending-${Date.now()}`,
        role: "user",
        content: message,
        created_at: new Date().toISOString(),
    });
    draft.value = "";
    sending.value = true;
    scrollToBottom();

    try {
        const response = await window.axios.post(route("customer.assistant.store"), { message });
        thread.value.push(response.data.reply);
    } catch (error) {
        errorText.value = error.response?.data?.errors?.message?.[0] || "Couldn't send that — please try again.";
    } finally {
        sending.value = false;
        scrollToBottom();
    }
}

function formatTime(iso) {
    return new Date(iso).toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
}
</script>

<template>
    <div class="fixed bottom-20 left-4 md:bottom-6 md:left-6 z-[950]">
        <button
            v-if="!open"
            @click="toggle"
            class="w-14 h-14 rounded-full bg-gradient-ocean text-white shadow-glow flex items-center justify-center hover:scale-105 active:scale-95 transition-transform"
            aria-label="Open Ask Aquameter chat"
        >
            <Icon name="message-circle" :size="24" />
        </button>

        <div
            v-else
            class="w-[92vw] max-w-sm h-[70vh] max-h-[560px] flex flex-col bg-white dark:bg-ocean-900 rounded-2xl shadow-elevated border border-ocean-100 dark:border-white/10 overflow-hidden"
        >
            <div class="flex items-center justify-between gap-2 px-4 h-14 border-b border-ocean-100 dark:border-white/5 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-gradient-ocean flex items-center justify-center shrink-0">
                        <Icon name="sparkles" :size="16" class="text-white" />
                    </div>
                    <span class="font-display font-semibold text-sm text-ocean-900 dark:text-white truncate">
                        Ask Aquameter
                    </span>
                </div>
                <button
                    @click="open = false"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-ocean-400 hover:bg-ocean-50 hover:text-ocean-600 dark:text-ocean-300/60 dark:hover:bg-white/5 dark:hover:text-white transition-colors shrink-0"
                    aria-label="Close chat"
                >
                    <Icon name="x" :size="18" />
                </button>
            </div>

            <div ref="scrollArea" class="flex-1 overflow-y-auto p-4 space-y-3">
                <div v-if="loadingHistory" class="h-full flex items-center justify-center">
                    <InlineDroplet :size="20" class="text-ocean-400 dark:text-neutral-400" />
                </div>

                <div v-else-if="thread.length === 0" class="h-full flex flex-col items-center justify-center text-center gap-3 py-6">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-ocean flex items-center justify-center shadow-soft">
                        <Icon name="sparkles" :size="18" class="text-white" />
                    </div>
                    <p class="text-xs text-ocean-500 dark:text-neutral-400 max-w-[220px]">
                        Ask about your bills, meter readings, or the status of anything you've reported.
                    </p>
                </div>

                <template v-else>
                    <div
                        v-for="m in thread"
                        :key="m.id"
                        class="flex"
                        :class="m.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[85%] rounded-2xl px-3.5 py-2 text-sm"
                            :class="m.role === 'user'
                                ? 'bg-gradient-ocean text-white shadow-soft'
                                : 'bg-ocean-50 dark:bg-white/5 text-ocean-900 dark:text-neutral-100'"
                        >
                            <p class="whitespace-pre-wrap">{{ m.content }}</p>
                            <p
                                class="text-[10px] mt-1"
                                :class="m.role === 'user' ? 'text-white/70' : 'text-ocean-400 dark:text-neutral-500'"
                            >
                                {{ formatTime(m.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="sending" class="flex justify-start">
                        <div class="rounded-2xl px-3.5 py-2 bg-ocean-50 dark:bg-white/5">
                            <InlineDroplet :size="14" class="text-ocean-400 dark:text-neutral-400" />
                        </div>
                    </div>
                </template>
            </div>

            <form @submit.prevent="send" class="border-t border-ocean-100 dark:border-white/5 p-2.5 flex items-end gap-2 shrink-0">
                <textarea
                    v-model="draft"
                    rows="1"
                    maxlength="1000"
                    placeholder="Ask a question…"
                    class="flex-1 resize-none border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm text-sm"
                    @keydown.enter.exact.prevent="send"
                ></textarea>
                <button
                    type="submit"
                    :disabled="sending || !draft.trim()"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-ocean text-white shadow-soft hover:shadow-glow transition-all disabled:opacity-50 disabled:pointer-events-none shrink-0"
                    aria-label="Send message"
                >
                    <Icon name="arrow-right" :size="16" />
                </button>
            </form>
            <p v-if="errorText" class="px-2.5 pb-2.5 text-xs text-red-600 dark:text-red-400">{{ errorText }}</p>
        </div>
    </div>
</template>
