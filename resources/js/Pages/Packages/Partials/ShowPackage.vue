<template>
    <div v-if="show" class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Package Details</h3>

                    <div class="mt-4">
                        <p><strong>Name:</strong> {{ profile.name }}</p>
                        <p><strong>Rate Limit:</strong> {{ profile.rate_limit || '-' }}</p>
                        <p><strong>Shared Users:</strong> {{ profile.shared_users || '-' }}</p>
                        <p><strong>MAC Cookie Timeout:</strong> {{ profile.mac_cookie_timeout || '-' }}</p>
                        <p><strong>Keepalive Timeout:</strong> {{ profile.keepalive_timeout || '-' }}</p>
                        <p><strong>Router:</strong> {{ profile.router_name }}</p>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button
                            @click="$emit('close')"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: Boolean,
    profile: Object,
});

defineEmits(['close']);
</script>