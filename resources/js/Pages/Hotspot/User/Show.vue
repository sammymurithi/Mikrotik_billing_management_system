<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    hotspotUser: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <Head title="Hotspot User Details" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Hotspot User Details
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('hotspot.users.edit', { id: hotspotUser.id })"
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 dark:bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 dark:hover:bg-yellow-600 focus:bg-yellow-700 dark:focus:bg-yellow-600 active:bg-yellow-900 dark:active:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    >
                        Edit User
                    </Link>
                    <Link
                        :href="route('hotspot.users.sessions', { id: hotspotUser.id })"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 dark:hover:bg-blue-600 focus:bg-blue-700 dark:focus:bg-blue-600 active:bg-blue-900 dark:active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    >
                        View Sessions
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- User Information -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">User Information</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ hotspotUser.username }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">MAC Address</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ hotspotUser.mac_address }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ hotspotUser.router_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Uptime</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ hotspotUser.uptime }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Connection Details -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Connection Details</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bytes In</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ hotspotUser.bytes_in }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bytes Out</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ hotspotUser.bytes_out }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Packets In</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ hotspotUser.packets_in }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Packets Out</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ hotspotUser.packets_out }}</p>
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