import { ref, onUnmounted } from 'vue';

/**
 * Composable for delayed loading state on table filters, searches, and pagination.
 * Prevents skeleton flicker on fast (< delayMs) responses.
 */
export function useTableLoading(delayMs = 120) {
    const isLoading = ref(false);
    let timer = null;

    function start() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            isLoading.value = true;
        }, delayMs);
    }

    function stop() {
        clearTimeout(timer);
        timer = null;
        isLoading.value = false;
    }

    /**
     * Returns Inertia visit callbacks { onStart, onFinish } for router.get/post calls.
     */
    const inertiaCallbacks = {
        onStart: () => start(),
        onFinish: () => stop(),
    };

    onUnmounted(() => {
        clearTimeout(timer);
    });

    return {
        isLoading,
        start,
        stop,
        inertiaCallbacks,
    };
}
