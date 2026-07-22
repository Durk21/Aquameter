<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import Card from "@/Components/Card.vue";
import InlineDroplet from "@/Components/InlineDroplet.vue";
import Icon from "@/Components/Icon.vue";
import { Head } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";

const props = defineProps({
    messages: {
        type: Array,
        required: true,
    },
});

const thread = ref([...props.messages]);
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

scrollToBottom();

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
    <Head title="Ask Aquameter" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Ask Aquameter
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Ask Aquameter
            </h1>

            <Card :padded="false" class="flex flex-col h-[70vh] overflow-hidden">
                <div ref="scrollArea" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-4">
                    <div v-if="thread.length === 0" class="h-full flex flex-col items-center justify-center text-center gap-3 py-10">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-ocean flex items-center justify-center shadow-soft">
                            <Icon name="sparkles" :size="22" class="text-white" />
                        </div>
                        <p class="text-sm text-ocean-500 dark:text-neutral-400 max-w-xs">
                            Ask about your bills, meter readings, or the status of anything you've reported.
                        </p>
                    </div>

                    <div
                        v-for="m in thread"
                        :key="m.id"
                        class="flex"
                        :class="m.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm"
                            :class="m.role === 'user'
                                ? 'bg-gradient-ocean text-white shadow-soft'
                                : 'bg-ocean-50 dark:bg-white/5 text-ocean-900 dark:text-neutral-100'"
                        >
                            <p class="whitespace-pre-wrap">{{ m.content }}</p>
                            <p
                                class="text-[11px] mt-1"
                                :class="m.role === 'user' ? 'text-white/70' : 'text-ocean-400 dark:text-neutral-500'"
                            >
                                {{ formatTime(m.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="sending" class="flex justify-start">
                        <div class="rounded-2xl px-4 py-2.5 bg-ocean-50 dark:bg-white/5">
                            <InlineDroplet :size="16" class="text-ocean-400 dark:text-neutral-400" />
                        </div>
                    </div>
                </div>

                <form @submit.prevent="send" class="border-t border-ocean-100 dark:border-white/5 p-3 flex items-end gap-2">
                    <textarea
                        v-model="draft"
                        rows="1"
                        maxlength="1000"
                        placeholder="Ask a question about your account…"
                        class="flex-1 resize-none border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm text-sm"
                        @keydown.enter.exact.prevent="send"
                    ></textarea>
                    <button
                        type="submit"
                        :disabled="sending || !draft.trim()"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-ocean text-white shadow-soft hover:shadow-glow transition-all disabled:opacity-50 disabled:pointer-events-none shrink-0"
                        aria-label="Send message"
                    >
                        <Icon name="arrow-right" :size="18" />
                    </button>
                </form>
                <p v-if="errorText" class="px-3 pb-3 text-xs text-red-600 dark:text-red-400">{{ errorText }}</p>
            </Card>
        </div>
    </AppShell>
</template>
