import { onMounted, onUnmounted, ref } from "vue";

/**
 * Fades/slides an element in the first time it scrolls into view.
 * No-ops (element stays visible) when the user prefers reduced motion.
 */
export function useScrollReveal(options = {}) {
    const target = ref(null);
    const revealed = ref(false);
    let observer = null;

    onMounted(() => {
        if (typeof window === "undefined" || !target.value) return;

        if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            revealed.value = true;
            return;
        }

        observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    revealed.value = true;
                    observer.disconnect();
                }
            },
            { threshold: 0.15, rootMargin: "0px 0px -10% 0px", ...options },
        );

        observer.observe(target.value);
    });

    onUnmounted(() => observer?.disconnect());

    return { target, revealed };
}
