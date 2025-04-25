<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Create New Hotspot Profile</h2>
            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="name" value="Profile Name" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="rate_limit" value="Rate Limit" />
                    <TextInput
                        id="rate_limit"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.rate_limit"
                        placeholder="e.g., 20M/15M"
                    />
                    <InputError class="mt-2" :message="form.errors.rate_limit" />
                </div>

                <div>
                    <InputLabel for="shared_users" value="Shared Users" />
                    <TextInput
                        id="shared_users"
                        type="number"
                        class="mt-1 block w-full"
                        v-model="form.shared_users"
                        min="1"
                    />
                    <InputError class="mt-2" :message="form.errors.shared_users" />
                </div>

                <div>
                    <InputLabel for="mac_cookie_timeout" value="MAC Cookie Timeout" />
                    <div class="flex gap-2">
                        <select
                            v-model="selectedMacCookieTimeout"
                            @change="updateMacCookieTimeout"
                            class="mt-1 block w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Custom</option>
                            <option value="30m">30 minutes</option>
                            <option value="1h">1 hour</option>
                            <option value="2h">2 hours</option>
                            <option value="4h">4 hours</option>
                            <option value="8h">8 hours</option>
                            <option value="1d">1 day</option>
                        </select>
                        <div class="w-2/3">
                            <TextInput
                                id="mac_cookie_timeout"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.mac_cookie_timeout"
                                placeholder="e.g., 1h30m or 2h45m"
                            />
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Time after which the MAC cookie will expire
                    </p>
                    <InputError class="mt-2" :message="form.errors.mac_cookie_timeout" />
                </div>

                <div>
                    <InputLabel for="keepalive_timeout" value="Keepalive Timeout" />
                    <div class="flex gap-2">
                        <select
                            v-model="selectedKeepaliveTimeout"
                            @change="updateKeepaliveTimeout"
                            class="mt-1 block w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Custom</option>
                            <option value="1m">1 minute</option>
                            <option value="2m">2 minutes</option>
                            <option value="5m">5 minutes</option>
                            <option value="10m">10 minutes</option>
                            <option value="15m">15 minutes</option>
                            <option value="30m">30 minutes</option>
                        </select>
                        <div class="w-2/3">
                            <TextInput
                                id="keepalive_timeout"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.keepalive_timeout"
                                placeholder="e.g., 5m or 10m"
                            />
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Time between keepalive packets
                    </p>
                    <InputError class="mt-2" :message="form.errors.keepalive_timeout" />
                </div>

                <div>
                    <InputLabel for="session_timeout" value="Session Timeout" />
                    <div class="flex gap-2">
                        <select
                            v-model="selectedSessionTimeout"
                            @change="updateSessionTimeout"
                            class="mt-1 block w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Custom</option>
                            <option value="30m">30 minutes</option>
                            <option value="1h">1 hour</option>
                            <option value="2h">2 hours</option>
                            <option value="4h">4 hours</option>
                            <option value="8h">8 hours</option>
                            <option value="1d">1 day</option>
                        </select>
                        <div class="w-2/3">
                            <TextInput
                                id="session_timeout"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.session_timeout"
                                placeholder="e.g., 1h30m or 2h45m"
                            />
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Time after which the user session will expire (e.g., 1h, 30m, 1d)
                    </p>
                    <InputError class="mt-2" :message="form.errors.session_timeout" />
                </div>

                <div>
                    <InputLabel for="price" value="Price" />
                    <TextInput
                        id="price"
                        type="number"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full"
                        v-model="form.price"
                        placeholder="e.g., 9.99"
                    />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Package price in Kes (will be shown in captive portal)
                    </p>
                    <InputError class="mt-2" :message="form.errors.price" />
                </div>

                <div>
                    <InputLabel for="router_id" value="Router" />
                    <select
                        id="router_id"
                        v-model="form.router_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="">Select a router</option>
                        <option v-for="router in routers" :key="router.id" :value="router.id">
                            {{ router.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.router_id" />
                </div>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing" class="mr-3">Create Profile</PrimaryButton>
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { convertToMikrotikTime } from '@/Utils/timeConverter';

const props = defineProps({
    show: Boolean,
    routers: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['close', 'created']);

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

const selectedMacCookieTimeout = ref('');
const selectedKeepaliveTimeout = ref('');
const selectedSessionTimeout = ref('');

const updateMacCookieTimeout = () => {
    if (selectedMacCookieTimeout.value) {
        form.mac_cookie_timeout = selectedMacCookieTimeout.value;
    }
};

const updateKeepaliveTimeout = () => {
    if (selectedKeepaliveTimeout.value) {
        form.keepalive_timeout = selectedKeepaliveTimeout.value;
    }
};

const updateSessionTimeout = () => {
    if (selectedSessionTimeout.value) {
        form.session_timeout = selectedSessionTimeout.value;
    }
};

const submit = () => {
    // Only convert time values if they are not empty
    if (form.mac_cookie_timeout) {
        form.mac_cookie_timeout = convertToMikrotikTime(form.mac_cookie_timeout);
    }
    if (form.keepalive_timeout) {
        form.keepalive_timeout = convertToMikrotikTime(form.keepalive_timeout);
    }
    if (form.session_timeout) {
        form.session_timeout = convertToMikrotikTime(form.session_timeout);
    }
    
    console.log('Submitting form data:', form.data());
    console.log('Price value:', form.price);

    form.post(route('hotspot.profiles.store'), {
        onSuccess: () => {
            console.log('Profile created successfully with price:', form.price);
            // Reset form and selections
            form.reset();
            selectedMacCookieTimeout.value = '';
            selectedKeepaliveTimeout.value = '';
            selectedSessionTimeout.value = '';
            
            // Close the modal
            emit('close');
            emit('created');
        },
        onError: (errors) => {
            console.error('Error creating profile:', errors);
            alert('Error creating profile: ' + (errors.error || 'Unknown error'));
        }
    });
};
</script>

<style>
.dp__input {
    width: 100%;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
}
.dp__input:focus {
    border-color: #6366f1;
    outline: none;
    box-shadow: 0 0 0 1px #6366f1;
}
</style>