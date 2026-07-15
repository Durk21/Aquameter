<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: {
        type: Array,
        required: true,
    },
    maxFiles: {
        type: Number,
        default: 3,
    },
    error: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue"]);

const previews = computed(() => props.modelValue.map((file) => ({
    file,
    url: URL.createObjectURL(file),
})));

const onFileChange = (event) => {
    const selected = Array.from(event.target.files || []).slice(0, props.maxFiles);
    emit("update:modelValue", selected);
    event.target.value = "";
};

const removeAt = (index) => {
    const updated = [...props.modelValue];
    updated.splice(index, 1);
    emit("update:modelValue", updated);
};
</script>

<template>
    <div>
        <input
            type="file"
            accept="image/*"
            multiple
            :disabled="modelValue.length >= maxFiles"
            @change="onFileChange"
            class="block w-full text-sm text-ocean-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border file:border-ocean-300 file:text-xs file:font-semibold file:bg-white file:text-ocean-700 hover:file:bg-ocean-50"
        />
        <p class="mt-1 text-xs text-ocean-500">Up to {{ maxFiles }} photo(s).</p>
        <p v-if="error" class="mt-1 text-xs text-red-700">{{ error }}</p>

        <div v-if="previews.length > 0" class="flex flex-wrap gap-2 mt-2">
            <div v-for="(preview, index) in previews" :key="index" class="relative">
                <img :src="preview.url" class="w-16 h-16 object-cover rounded-md border border-ocean-200" />
                <button
                    type="button"
                    @click="removeAt(index)"
                    class="absolute -top-1.5 -right-1.5 w-5 h-5 flex items-center justify-center rounded-full bg-red-600 text-white text-xs leading-none"
                >
                    ×
                </button>
            </div>
        </div>
    </div>
</template>
