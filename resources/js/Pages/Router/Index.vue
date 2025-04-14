<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref, onMounted } from 'vue';

const props = defineProps({
    routers: {
        type: Array,
        required: true
    }
});

const routerStatuses = ref({});

const deleteRouter = (routerId) => {
    if (confirm('Are you sure you want to delete this router?')) {
        router.delete(route('routers.destroy', routerId), {
            preserveScroll: true,
            onSuccess: () => {
                // Remove the router from the local state
                const index = props.routers.findIndex(r => r.id === routerId);
                if (index !== -1) {
                    props.routers.splice(index, 1);
                }
            }
        });
    }
};

const checkRouterConnection = async (routerId) => {
    try {
        const response = await fetch(route('routers.check-connection', routerId));
        const data = await response.json();
        routerStatuses.value[routerId] = {
            connected: data.connected,
            message: data.message
        };
    } catch (error) {
        routerStatuses.value[routerId] = {
            connected: false,
            message: 'Error checking connection'
        };
    }
};

const checkAllRouters = () => {
    props.routers.forEach(router => {
        checkRouterConnection(router.id);
    });
};

onMounted(() => {
    checkAllRouters();
});
</script>

<template>
    <Head title="Routers" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Routers
                </h2>
                <div class="flex space-x-4">
                    <PrimaryButton @click="checkAllRouters" class="ml-4">
                        Test All Connections
                    </PrimaryButton>
                    <Link
                        :href="route('routers.create')"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    >
                        Add Router
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP Address</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="router in routers" :key="router.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ router.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ router.ip_address }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div
                                                    :class="{
                                                        'bg-green-500': routerStatuses[router.id]?.connected,
                                                        'bg-red-500': !routerStatuses[router.id]?.connected,
                                                        'animate-pulse': !routerStatuses[router.id]
                                                    }"
                                                    class="w-3 h-3 rounded-full mr-2"
                                                ></div>
                                                <span>{{ routerStatuses[router.id]?.message || 'Checking...' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-4">
                                                <Link
                                                    :href="route('routers.show', router.id)"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    :href="route('routers.edit', router.id)"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    @click.prevent="deleteRouter(router.id)"
                                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 