<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    workOrderId: {
        type: Number,
        required: true,
    },
});

const rating = ref(0);
const hovered = ref(0);

const form = useForm({
    rating: 0,
    rating_comment: "",
});

const submit = () => {
    form.rating = rating.value;
    form.post(route("customer.work-orders.rate", props.workOrderId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="mt-2 bg-ocean-50 rounded-md px-3 py-2">
        <p class="text-xs font-medium text-ocean-700 mb-1">How did we do?</p>
        <div class="flex items-center gap-1">
            <button
                v-for="n in 5"
                :key="n"
                type="button"
                @click="rating = n"
                @mouseenter="hovered = n"
                @mouseleave="hovered = 0"
                class="text-2xl leading-none"
                :class="(hovered || rating) >= n ? 'text-amber-500' : 'text-ocean-200'"
            >
                ★
            </button>
        </div>
        <textarea
            v-if="rating > 0"
            v-model="form.rating_comment"
            rows="2"
            maxlength="2000"
            placeholder="Anything else you'd like to add? (optional)"
            class="mt-2 block w-full text-sm border-ocean-300 focus:border-ocean-500 focus:ring-ocean-500 rounded-md shadow-sm"
        ></textarea>
        <button
            v-if="rating > 0"
            type="button"
            @click="submit"
            :disabled="form.processing"
            class="mt-2 inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-ocean-600 text-white hover:bg-ocean-700 disabled:opacity-50"
        >
            Submit Rating
        </button>
    </div>
</template>
