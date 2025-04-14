<template>
    <div class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
            <button
                v-if="links[0].url"
                @click="goToPage(links[0].url)"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                Previous
            </button>
            <button
                v-if="links[links.length - 1].url"
                @click="goToPage(links[links.length - 1].url)"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                Next
            </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Showing page
                    <span class="font-medium">{{ currentPage }}</span>
                    of
                    <span class="font-medium">{{ totalPages }}</span>
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <button
                        v-for="(link, index) in links"
                        :key="index"
                        @click="goToPage(link.url)"
                        :disabled="!link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-indigo-50 dark:bg-indigo-900 border-indigo-500 dark:border-indigo-700 text-indigo-600 dark:text-indigo-300'
                                : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                        ]"
                        v-html="link.label"
                    ></button>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        required: true
    },
    currentPage: {
        type: Number,
        required: true
    },
    totalPages: {
        type: Number,
        required: true
    }
});

const goToPage = (url) => {
    if (url) {
        router.visit(url, {
            preserveScroll: true,
            preserveState: true
        });
    }
};
</script>

<style scoped>
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}
</style>
  