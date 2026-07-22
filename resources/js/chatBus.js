import { reactive } from "vue";

export const chatBus = reactive({
    pendingMessage: null,
});

export function askAquameter(message) {
    chatBus.pendingMessage = message;
}
