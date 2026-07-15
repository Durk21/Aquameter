<script setup>
defineProps({
    photos: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="photos.length > 0" class="flex flex-wrap gap-2 mt-2">
        <a v-for="photo in photos" :key="photo.id" :href="photo.url" target="_blank" rel="noopener">
            <img :src="photo.url" class="w-16 h-16 object-cover rounded-md border border-ocean-200 hover:opacity-80" />
        </a>
    </div>
</template>
