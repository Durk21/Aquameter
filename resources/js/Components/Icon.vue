<script setup>
const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    size: {
        type: Number,
        default: 20,
    },
});

const paths = {
    home: "M3 11.5 12 4l9 7.5M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9",
    gauge: "M12 4a8 8 0 1 0 8 8M12 4a8 8 0 0 1 6 3.2M12 12l4-4.5",
    wrench: "M14.7 6.3a4 4 0 1 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.8 2.8-2-2 2.8-2.8Z",
    "clipboard-list": "M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1ZM6 6h12v14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V6ZM9 11h6M9 15h6",
    "log-out": "M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3M10 8l-4 4 4 4M6 12h12",
    menu: "M4 7h16M4 12h16M4 17h16",
    "chevron-down": "m6 9 6 6 6-6",
    user: "M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 20a8 8 0 0 1 16 0",
    "alert-triangle": "M12 9v4m0 4h.01M10.3 4.3 2.6 18a1 1 0 0 0 .9 1.5h17a1 1 0 0 0 .9-1.5L13.7 4.3a1 1 0 0 0-1.8 0Z",
  "shield-check": "M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6l-8-3ZM9 12l2 2 4-4",
"bell": "M6 8a6 6 0 1 1 12 0c0 4 1.5 5.5 2 6H4c.5-.5 2-2 2-6ZM10 19a2 2 0 0 0 4 0",
"sun": "M12 4V2M12 22v-2M4 12H2M22 12h-2M5.6 5.6 4.2 4.2M19.8 19.8l-1.4-1.4M5.6 18.4l-1.4 1.4M19.8 4.2l-1.4 1.4M12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z",
    "moon": "M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5Z",
    "droplet": "M12 3s7 7.6 7 12a7 7 0 1 1-14 0c0-4.4 7-12 7-12Z",
    "calendar": "M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1ZM8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01",
    "history": "M3 12a9 9 0 1 0 3-6.7M3 12V6M3 12h6M12 8v4l3 2",
};
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path :d="paths[name] || ''" />
    </svg>
</template>
