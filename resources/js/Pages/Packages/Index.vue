<template>
        <Head title="Packages" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Hotspot Profiles</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Alert -->
                <div v-if="page.props.flash && page.props.flash.success" class="mb-4">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ page.props.flash.success }}</span>
                    </div>
                </div>

                <!-- Error Alert -->
                <div v-if="page.props.flash && page.props.flash.error" class="mb-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ page.props.flash.error }}</span>
                    </div>
                </div>

                <div v-if="editMode" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium">Edit Package: {{ selectedProfile ? selectedProfile.name : '' }}</h3>
                            <button
                                @click="cancelEdit"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-400 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            >
                                Cancel Edit
                            </button>
                        </div>
                        
                        <!-- Edit Form -->
                        <form @submit.prevent="updateProfile" class="space-y-6" v-if="selectedProfile">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="name" value="Name" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        placeholder="e.g., Premium Package"
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="rate_limit" value="Rate Limit" />
                                    <TextInput
                                        id="rate_limit"
                                        v-model="form.rate_limit"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 2M/1M"
                                    />
                                    <InputError :message="form.errors.rate_limit" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="shared_users" value="Shared Users" />
                                    <TextInput
                                        id="shared_users"
                                        v-model="form.shared_users"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 5"
                                    />
                                    <InputError :message="form.errors.shared_users" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="router_id" value="Router" />
                                    <select
                                        v-model="form.router_id"
                                        id="router_id"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">Select a router</option>
                                        <option v-for="router in routers" :key="router.id" :value="router.id">
                                            {{ router.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.router_id" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="mac_cookie_timeout" value="MAC Cookie Timeout" />
                                    <TextInput
                                        id="mac_cookie_timeout"
                                        v-model="form.mac_cookie_timeout"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 1h"
                                    />
                                    <InputError :message="form.errors.mac_cookie_timeout" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="keepalive_timeout" value="Keepalive Timeout" />
                                    <TextInput
                                        id="keepalive_timeout"
                                        v-model="form.keepalive_timeout"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 2m"
                                    />
                                    <InputError :message="form.errors.keepalive_timeout" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="session_timeout" value="Session Timeout" />
                                    <TextInput
                                        id="session_timeout"
                                        v-model="form.session_timeout"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 1h"
                                    />
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Time after which the user session will expire (e.g., 1h, 30m, 1d)
                                    </p>
                                    <InputError :message="form.errors.session_timeout" class="mt-2" />
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end">
                                <PrimaryButton :disabled="form.processing" class="ml-3">
                                    Update Profile
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium">Manage Hotspot Profiles</h3>
                            <button
                                @click="showCreateModal = true"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-400 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            >
                                Create New Profile
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rate Limit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Shared Users</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">MAC Cookie Timeout</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keepalive Timeout</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Session Timeout</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Router</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="hotspotPackage in hotspotProfiles" :key="hotspotPackage.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ hotspotPackage.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ hotspotPackage.rate_limit }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ hotspotPackage.shared_users }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ hotspotPackage.mac_cookie_timeout }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ hotspotPackage.keepalive_timeout }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ hotspotPackage.session_timeout }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ hotspotPackage.router_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button
                                                @click="startEdit(hotspotPackage)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <Link
                                                :href="route('hotspot.profiles.destroy', hotspotPackage.id)"
                                                method="delete"
                                                as="button"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Create Package Modal -->
        <CreatePackage
            :show="showCreateModal"
            :routers="routers"
            @close="showCreateModal = false"
            @created="fetchProfiles"
        />

        <!-- We're using inline editing instead of a modal -->
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import CreatePackage from './Partials/CreatePackage.vue';

const page = usePage();

defineProps({
    hotspotProfiles: {
        type: Array,
        required: false,
        default: () => [],
    },
    routers: {
        type: Array,
        required: false,
        default: () => [],
    },
});

const showCreateModal = ref(false);
const editMode = ref(false);
const selectedProfile = ref(null);

const form = useForm({
    name: '',
    rate_limit: '',
    shared_users: '',
    mac_cookie_timeout: '',
    keepalive_timeout: '',
    session_timeout: '',
    router_id: '',
});

const startEdit = (profile) => {
    console.log('Starting edit for profile:', profile);
    // Make a deep copy of the profile to avoid reactivity issues
    selectedProfile.value = JSON.parse(JSON.stringify(profile));
    
    // Populate the form with the profile data
    form.name = profile.name || '';
    form.rate_limit = profile.rate_limit || '';
    form.shared_users = profile.shared_users || '';
    form.mac_cookie_timeout = profile.mac_cookie_timeout || '';
    form.keepalive_timeout = profile.keepalive_timeout || '';
    form.session_timeout = profile.session_timeout || '';
    form.router_id = profile.router_id || '';
    
    // Enable edit mode
    editMode.value = true;
    
    // Scroll to the top of the page to show the edit form
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEdit = () => {
    editMode.value = false;
    selectedProfile.value = null;
    form.reset();
};

const updateProfile = () => {
    if (!selectedProfile.value) return;
    
    console.log('Updating profile with data:', form.data());
    
    form.put(route('hotspot.profiles.update', selectedProfile.value.id), {
        onSuccess: () => {
            console.log('Profile updated successfully');
            editMode.value = false;
            selectedProfile.value = null;
            fetchProfiles();
        },
        onError: (errors) => {
            console.error('Error updating profile:', errors);
        },
    });
};

const deleteProfile = (id) => {
    if (confirm('Are you sure you want to delete this package?')) {
        router.delete(route('hotspot.profiles.destroy', id), {
            onSuccess: () => {
                router.reload();
            },
            onError: (errors) => {
                console.error('Error deleting profile:', errors);
            },
        });
    }
};

const fetchProfiles = () => {
    router.reload();
};

// Auto-hide alerts after 5 seconds
onMounted(() => {
    if (page.props.flash && (page.props.flash.success || page.props.flash.error)) {
        setTimeout(() => {
            router.reload();
        }, 5000);
    }
});
</script>