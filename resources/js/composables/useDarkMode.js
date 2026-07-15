import { ref } from "vue";

const STORAGE_KEY = "aquameter-theme";

// Module-level ref: shared across every component that imports this file,
// so the toggle stays in sync everywhere without needing Pinia/Vuex.
const isDark = ref(false);

function applyClass(value) {
    if (typeof document === "undefined") return;
    document.documentElement.classList.toggle("dark", value);
}

/**
 * Call once, as early as possible (see resources/js/app.js), to sync the
 * reactive state with whatever theme was already applied by the inline
 * blocking script in app.blade.php (which runs before Vue even loads,
 * to avoid a flash of the wrong theme).
 */
function initDarkMode() {
    if (typeof document === "undefined") return;
    isDark.value = document.documentElement.classList.contains("dark");
}

function setDarkMode(value) {
    isDark.value = value;
    applyClass(value);
    if (typeof localStorage !== "undefined") {
        localStorage.setItem(STORAGE_KEY, value ? "dark" : "light");
    }
}

function toggleDarkMode() {
    setDarkMode(!isDark.value);
}

export function useDarkMode() {
    return { isDark, initDarkMode, toggleDarkMode, setDarkMode };
}
