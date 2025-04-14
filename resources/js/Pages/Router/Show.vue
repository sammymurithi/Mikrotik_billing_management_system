<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { CheckCircleIcon, XCircleIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    router: {
        type: Object,
        required: true
    }
});

const connectionStatus = ref('Checking...');
const isConnected = ref(false);
const connectionMessage = ref('');

const checkConnection = async () => {
    try {
        const response = await fetch(route('routers.check-connection', props.router.id));
        const data = await response.json();
        connectionStatus.value = data.connected ? 'Connected' : 'Disconnected';
        isConnected.value = data.connected;
        connectionMessage.value = data.message;
    } catch (error) {
        connectionStatus.value = 'Error';
        isConnected.value = false;
        connectionMessage.value = 'Error checking connection';
    }
};

onMounted(() => {
    checkConnection();
    // Check connection status every 30 seconds
    const interval = setInterval(checkConnection, 30000);
    return () => clearInterval(interval);
});
</script>

<template>
    <Head title="Router Details" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Router Details
                </h2>
                <div class="flex space-x-4">
                    <Link
                        :href="route('routers.edit', router.id)"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    >
                        Edit Router
                    </Link>
                    <Link
                        :href="route('routers.index')"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    >
                        Back to Routers
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Router Information -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-lg font-medium">Router Information</h3>
                                    <div class="mt-4 space-y-2">
                                        <div>
                                            <span class="font-semibold">Name:</span>
                                            <span class="ml-2">{{ router.name }}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold">IP Address:</span>
                                            <span class="ml-2">{{ router.ip_address }}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold">Username:</span>
                                            <span class="ml-2">{{ router.username }}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold">Port:</span>
                                            <span class="ml-2">{{ router.port }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Connection Status -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-lg font-medium">Connection Status</h3>
                                    <div class="mt-4">
                                        <div class="flex items-center space-x-3">
                                            <CheckCircleIcon
                                                v-if="isConnected"
                                                class="h-6 w-6 text-green-500"
                                            />
                                            <XCircleIcon
                                                v-else-if="connectionStatus === 'Disconnected'"
                                                class="h-6 w-6 text-red-500"
                                            />
                                            <ClockIcon
                                                v-else
                                                class="h-6 w-6 text-gray-500 animate-spin"
                                            />
                                            <div>
                                                <div class="font-medium">{{ connectionStatus }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ connectionMessage }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 