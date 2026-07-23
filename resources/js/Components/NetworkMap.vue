<script setup>
import { onMounted, onUnmounted, ref, watch } from "vue";
import L from "leaflet";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import { nearestZone, withinBounds } from "@/utils/geo";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const props = defineProps({
    pipeSegments: {
        type: Array,
        default: () => [],
    },
    incidents: {
        type: Array,
        default: () => [],
    },
    outages: {
        type: Array,
        default: () => [],
    },
    zones: {
        type: Array,
        default: () => [],
    },
    zoneCenters: {
        type: Object,
        default: () => ({}),
    },
    serviceAreaBounds: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: "view", // 'view' | 'picker' | 'draw'
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
    modelValue: {
        type: Object,
        default: null, // { lat, lng } — picker mode
    },
    points: {
        type: Array,
        default: () => [], // [{ lat, lng }] — draw mode
    },
    height: {
        type: String,
        default: "360px",
    },
});

const emit = defineEmits(["update:modelValue", "update:points", "update:zone", "location-error"]);

const INCIDENT_COLORS = {
    leak_report: "#dc2626",
    service_request: "#6366f1",
};

const mapContainer = ref(null);
const locating = ref(false);
let map = null;
let pipeLayer = null;
let incidentLayer = null;
let outageLayer = null;
let pickerMarker = null;
let drawLayer = null;

function defaultCenter() {
    const b = props.serviceAreaBounds;
    return [(b.min_lat + b.max_lat) / 2, (b.min_lng + b.max_lng) / 2];
}

function drawPipeSegments() {
    pipeLayer?.clearLayers();
    for (const segment of props.pipeSegments) {
        const latlngs = segment.points.map((p) => [p.lat, p.lng]);
        L.polyline(latlngs, { color: segment.color, weight: 4, opacity: 0.85 })
            .bindPopup(`<strong>${segment.name}</strong><br>${segment.zone} · ${segment.status_label}`)
            .addTo(pipeLayer);
    }
}

function drawIncidents() {
    incidentLayer?.clearLayers();
    if (props.readOnly) return;

    for (const incident of props.incidents) {
        L.circleMarker([incident.lat, incident.lng], {
            radius: 7,
            color: "#fff",
            weight: 2,
            fillColor: INCIDENT_COLORS[incident.type] || "#6b7280",
            fillOpacity: 0.9,
        })
            .bindPopup(`<strong>${incident.label}</strong><br>${incident.zone} · ${incident.created_at}`)
            .addTo(incidentLayer);
    }
}

function drawOutages() {
    outageLayer?.clearLayers();
    if (props.readOnly) return;

    for (const outage of props.outages) {
        const center = props.zoneCenters[outage.zone];
        if (!center) continue;

        L.circleMarker(center, {
            radius: 12,
            color: "#d97706",
            weight: 2,
            fillColor: "#fbbf24",
            fillOpacity: 0.5,
        })
            .bindPopup(`<strong>${outage.title}</strong><br>${outage.zone} · ${outage.status_label}`)
            .addTo(outageLayer);
    }
}

function drawPicker() {
    if (pickerMarker) {
        map.removeLayer(pickerMarker);
        pickerMarker = null;
    }
    if (props.modelValue) {
        pickerMarker = L.marker([props.modelValue.lat, props.modelValue.lng]).addTo(map);
    }
}

function drawDrawLine() {
    drawLayer?.clearLayers();
    if (props.points.length === 0) return;

    const latlngs = props.points.map((p) => [p.lat, p.lng]);
    L.polyline(latlngs, { color: "#249cac", weight: 4 }).addTo(drawLayer);
    for (const point of props.points) {
        L.circleMarker([point.lat, point.lng], { radius: 5, color: "#0d5460", fillColor: "#249cac", fillOpacity: 1 }).addTo(drawLayer);
    }
}

function handleMapClick(e) {
    const { lat, lng } = e.latlng;

    if (!withinBounds(lat, lng, props.serviceAreaBounds)) {
        emit("location-error", "That point is outside the service area.");
        return;
    }

    if (props.mode === "picker") {
        emit("update:modelValue", { lat, lng });
        const zone = nearestZone(lat, lng, props.zoneCenters);
        if (zone) emit("update:zone", zone);
    } else if (props.mode === "draw") {
        emit("update:points", [...props.points, { lat, lng }]);
    }
}

function useCurrentLocation() {
    if (!navigator.geolocation) {
        emit("location-error", "Geolocation isn't available in this browser.");
        return;
    }

    locating.value = true;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords;
            locating.value = false;

            if (!withinBounds(latitude, longitude, props.serviceAreaBounds)) {
                emit("location-error", "Your current location is outside the service area — click the map or enter a zone manually.");
                return;
            }

            emit("update:modelValue", { lat: latitude, lng: longitude });
            const zone = nearestZone(latitude, longitude, props.zoneCenters);
            if (zone) emit("update:zone", zone);

            map.setView([latitude, longitude], 15);
        },
        () => {
            locating.value = false;
            emit("location-error", "Couldn't get your location — click the map or enter a zone manually.");
        },
    );
}

function undoLastPoint() {
    emit("update:points", props.points.slice(0, -1));
}

function clearPoints() {
    emit("update:points", []);
}

onMounted(() => {
    map = L.map(mapContainer.value).setView(defaultCenter(), 12);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    pipeLayer = L.layerGroup().addTo(map);
    incidentLayer = L.layerGroup().addTo(map);
    outageLayer = L.layerGroup().addTo(map);
    drawLayer = L.layerGroup().addTo(map);

    drawPipeSegments();
    drawIncidents();
    drawOutages();
    drawPicker();
    drawDrawLine();

    if (props.mode !== "view" && !props.readOnly) {
        map.on("click", handleMapClick);
    }
});

onUnmounted(() => {
    map?.remove();
    map = null;
});

watch(() => props.pipeSegments, drawPipeSegments, { deep: true });
watch(() => props.incidents, drawIncidents, { deep: true });
watch(() => props.outages, drawOutages, { deep: true });
watch(() => props.modelValue, drawPicker, { deep: true });
watch(() => props.points, drawDrawLine, { deep: true });

defineExpose({ useCurrentLocation });
</script>

<template>
    <div class="relative rounded-2xl overflow-hidden border border-ocean-100 dark:border-white/10 shadow-soft">
        <div ref="mapContainer" :style="{ height }" class="w-full"></div>

        <div v-if="mode === 'picker' && !readOnly" class="absolute top-3 right-3 z-[900]">
            <button
                type="button"
                @click="useCurrentLocation"
                :disabled="locating"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-white/95 dark:bg-neutral-900/95 text-ocean-700 dark:text-neutral-200 shadow-elevated border border-ocean-100 dark:border-white/10 hover:bg-white dark:hover:bg-neutral-800 disabled:opacity-50 backdrop-blur"
            >
                {{ locating ? "Locating…" : "📍 Use My Current Location" }}
            </button>
        </div>

        <div v-if="mode === 'draw' && !readOnly" class="absolute top-3 right-3 z-[900] flex gap-2">
            <button
                type="button"
                @click="undoLastPoint"
                :disabled="points.length === 0"
                class="px-3 py-2 rounded-lg text-xs font-semibold bg-white/95 dark:bg-neutral-900/95 text-ocean-700 dark:text-neutral-200 shadow-elevated border border-ocean-100 dark:border-white/10 hover:bg-white dark:hover:bg-neutral-800 disabled:opacity-50 backdrop-blur"
            >
                Undo Point
            </button>
            <button
                type="button"
                @click="clearPoints"
                :disabled="points.length === 0"
                class="px-3 py-2 rounded-lg text-xs font-semibold bg-white/95 dark:bg-neutral-900/95 text-red-600 dark:text-red-400 shadow-elevated border border-ocean-100 dark:border-white/10 hover:bg-white dark:hover:bg-neutral-800 disabled:opacity-50 backdrop-blur"
            >
                Clear
            </button>
        </div>

        <div v-if="mode === 'draw' && !readOnly" class="absolute bottom-3 left-3 z-[900] px-3 py-1.5 rounded-lg text-xs font-medium bg-white/95 dark:bg-neutral-900/95 text-ocean-700 dark:text-neutral-200 shadow-soft backdrop-blur">
            Click the map to trace the pipe along the road · {{ points.length }} point{{ points.length === 1 ? "" : "s" }}
        </div>
    </div>
</template>
