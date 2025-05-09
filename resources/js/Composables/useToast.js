import { ref } from 'vue';

const toasts = ref([]);

export function useToast() {
    const showToast = (message, type = 'success', duration = 3000) => {
        const id = Date.now();
        toasts.value.push({
            id,
            message,
            type,
            duration
        });

        setTimeout(() => {
            removeToast(id);
        }, duration);
    };

    const removeToast = (id) => {
        const index = toasts.value.findIndex(toast => toast.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    return {
        toasts,
        showToast,
        removeToast
    };
} 