<template>
        <Head title="Packages" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Hotspot Profiles</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Banner for notifications -->
                <Banner v-if="page.props.jetstream.flash?.banner" />

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
                        <form @submit.prevent="updateProfile" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="edit_name" value="Name" />
                                    <TextInput
                                        id="edit_name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        placeholder="e.g., Premium Package"
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="edit_rate_limit" value="Rate Limit" />
                                    <TextInput
                                        id="edit_rate_limit"
                                        v-model="form.rate_limit"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 20M/15M"
                                    />
                                    <InputError :message="form.errors.rate_limit" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="edit_shared_users" value="Shared Users" />
                                    <TextInput
                                        id="edit_shared_users"
                                        v-model="form.shared_users"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 5"
                                    />
                                    <InputError :message="form.errors.shared_users" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="edit_router_id" value="Router" />
                                    <select
                                        v-model="form.router_id"
                                        id="edit_router_id"
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
                                    <InputLabel for="edit_mac_cookie_timeout" value="MAC Cookie Timeout" />
                                    <TextInput
                                        id="edit_mac_cookie_timeout"
                                        v-model="form.mac_cookie_timeout"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 1h"
                                    />
                                    <InputError :message="form.errors.mac_cookie_timeout" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="edit_keepalive_timeout" value="Keepalive Timeout" />
                                    <TextInput
                                        id="edit_keepalive_timeout"
                                        v-model="form.keepalive_timeout"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 2m"
                                    />
                                    <InputError :message="form.errors.keepalive_timeout" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="edit_session_timeout" value="Session Timeout" />
                                    <TextInput
                                        id="edit_session_timeout"
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
                                
                                <div>
                                    <InputLabel for="edit_price" value="Price" />
                                    <TextInput
                                        id="edit_price"
                                        v-model="form.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., 9.99"
                                    />
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Package price in Kes (will be shown in captive portal)
                                    </p>
                                    <InputError :message="form.errors.price" class="mt-2" />
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end mt-6">
                                <PrimaryButton :disabled="form.processing" class="ml-3" type="submit">
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
                            <div class="flex space-x-4">
                                <button
                                    @click="syncProfiles"
                                    class="inline-flex items-center px-4 py-2 bg-emerald-600 dark:bg-emerald-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 dark:hover:bg-emerald-400 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                    :disabled="syncLoading"
                                >
                                    <svg v-if="syncLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Sync from MikroTik
                                </button>
                                <button
                                    @click="showCreateModal = true"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-400 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    Create New Profile
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rate Limit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Router</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="hotspotPackage in hotspotProfiles" :key="hotspotPackage.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ hotspotPackage.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ hotspotPackage.rate_limit }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            {{ hotspotPackage.router_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            {{ hotspotPackage.price ? `$${hotspotPackage.price}` : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{ 
                                                'bg-emerald-500 text-white dark:bg-emerald-600': hotspotPackage.synced,
                                                'bg-amber-500 text-white dark:bg-amber-600': !hotspotPackage.synced
                                            }">
                                                {{ hotspotPackage.synced ? 'Synced' : 'Local Only' }}
                                            </span>
                                        </td>
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
import Banner from '@/Components/Banner.vue';

const page = usePage();
const props = defineProps({
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
const syncLoading = ref(false);
const selectedProfile = ref(null);

const form = useForm({
    name: '',
    rate_limit: '',
    shared_users: '',
    mac_cookie_timeout: '',
    keepalive_timeout: '',
    session_timeout: '',
    price: '',
    router_id: '',
});

const startEdit = (profile) => {
    // Scroll to top immediately
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Enable edit mode first
    editMode.value = true;
    
    // Make a deep copy of the profile to avoid reactivity issues
    selectedProfile.value = JSON.parse(JSON.stringify(profile));
    
    // Reset and clear the form
    form.reset();
    form.clearErrors();
    
    // Set form data immediately
    form.name = profile.name || '';
    form.rate_limit = profile.rate_limit || '';
    form.shared_users = profile.shared_users || '';
    form.mac_cookie_timeout = profile.mac_cookie_timeout || '';
    form.keepalive_timeout = profile.keepalive_timeout || '';
    form.session_timeout = profile.session_timeout || '';
    form.price = profile.price || '';
    form.router_id = profile.router_id;
};

const cancelEdit = () => {
    editMode.value = false;
    selectedProfile.value = null;
    form.reset();
    form.clearErrors();
};

const updateProfile = () => {
    if (!selectedProfile.value) {
        return;
    }
    
    // Show a confirmation if the price has been changed
    if (selectedProfile.value.price !== form.price) {
        if (!confirm('You are changing the price of this package. This will only affect the price shown in the captive portal and will not be stored on the MikroTik router. Continue?')) {
            return;
        }
    }
    
    router.put(route('hotspot.profiles.update', selectedProfile.value.id), form.data(), {
        preserveScroll: true,
        onSuccess: () => {
            editMode.value = false;
            selectedProfile.value = null;
            form.reset();
            form.clearErrors();
            fetchProfiles();
            // Set success banner
            page.props.jetstream.flash = {
                banner: 'Package updated successfully',
                bannerStyle: 'success'
            };
            router.reload();
        },
        onError: (errors) => {
            // Set error banner
            page.props.jetstream.flash = {
                banner: 'Error updating profile: ' + (errors.error || 'Unknown error'),
                bannerStyle: 'danger'
            };
            router.reload();
        },
    });
};

const deleteProfile = (id) => {
    if (confirm('Are you sure you want to delete this package?')) {
        router.delete(route('hotspot.profiles.destroy', id), {
            onSuccess: () => {
                // Set success banner
                page.props.jetstream.flash = {
                    banner: 'Package deleted successfully',
                    bannerStyle: 'success'
                };
                router.reload();
            },
            onError: (errors) => {
                // Set error banner
                page.props.jetstream.flash = {
                    banner: 'Error deleting profile: ' + (errors.error || 'Unknown error'),
                    bannerStyle: 'danger'
                };
                router.reload();
            },
        });
    }
};

const fetchProfiles = () => {
    router.reload();
};

const syncProfiles = () => {
    syncLoading.value = true;
    
    // Get the selected router or sync all routers
    const routerId = selectedProfile.value ? selectedProfile.value.router_id : null;
    const syncAll = !routerId;
    
    router.post(route('hotspot.profiles.sync'), {
        router_id: routerId,
        sync_all: syncAll
    }, {
        preserveScroll: true,
        onFinish: () => {
            syncLoading.value = false;
        }
    });
};

// Remove the auto-hide alerts section since Banner component handles this
onMounted(() => {
    // Remove the auto-hide alerts code since Banner component handles this
});
</script>