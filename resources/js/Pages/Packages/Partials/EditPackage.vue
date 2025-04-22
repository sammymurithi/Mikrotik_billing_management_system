<template>
    <div>
        <Modal :show="show" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Edit Package
                </h2>

                <form @submit.prevent="submit" class="mt-6">
                    <div class="mt-4">
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

                <div class="mt-4">
                    <InputLabel for="rate_limit" value="Rate Limit" />
                    <TextInput
                        id="rate_limit"
                        v-model="form.rate_limit"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g., 2M/1M (2Mbps download/1Mbps upload)"
                    />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Format: Download/Upload (e.g., 2M/1M, 512k/256k, 10M/5M)
                    </p>
                    <InputError :message="form.errors.rate_limit" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="shared_users" value="Shared Users" />
                    <TextInput
                        id="shared_users"
                        v-model="form.shared_users"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g., 5"
                    />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Maximum number of users that can share this profile
                    </p>
                    <InputError :message="form.errors.shared_users" class="mt-2" />
                </div>

                <div class="mt-4">
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
                    <InputError :message="form.errors.mac_cookie_timeout" class="mt-2" />
                </div>

                <div class="mt-4">
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
                    <InputError :message="form.errors.keepalive_timeout" class="mt-2" />
                </div>

                <div class="mt-4">
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
                    <InputError :message="form.errors.session_timeout" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="price" value="Price" />
                    <TextInput
                        id="price"
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

                <div class="mt-4">
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

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        Cancel
                    </SecondaryButton>

                    <PrimaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Update
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch, ref, onMounted } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { convertToMikrotikTime, convertFromMikrotikTime } from '@/Utils/timeConverter';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    show: Boolean,
    profile: Object,
    routers: Array,
});

const emit = defineEmits(['close', 'updated']);

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

// Watch for changes to the profile prop
watch(
    () => props.profile,
    (newProfile) => {
        if (newProfile) {
            try {
                // Load profile data into the form
                form.name = newProfile.name || '';
                form.rate_limit = newProfile.rate_limit || '';
                form.shared_users = newProfile.shared_users || '';
                form.mac_cookie_timeout = convertFromMikrotikTime(newProfile.mac_cookie_timeout) || '00:00:00';
                form.keepalive_timeout = convertFromMikrotikTime(newProfile.keepalive_timeout) || '00:00:00';
                form.session_timeout = convertFromMikrotikTime(newProfile.session_timeout) || '00:00:00';
                form.price = newProfile.price || '';
                form.router_id = newProfile.router_id || '';
                
                // Reset the select dropdowns
                selectedMacCookieTimeout.value = '';
                selectedKeepaliveTimeout.value = '';
                
                console.log('Profile loaded successfully:', form);
            } catch (error) {
                console.error('Error loading profile data:', error);
            }
        }
    }
);

// Watch for changes to the show prop
watch(
    () => props.show,
    (visible) => {
        console.log('EditPackage show changed:', visible);
    }
);

const closeModal = () => {
    console.log('Closing modal');
    emit('close');
};

const submit = () => {
    const formData = {
        ...form.data(),
        mac_cookie_timeout: convertToMikrotikTime(form.mac_cookie_timeout || '00:00:00'),
        keepalive_timeout: convertToMikrotikTime(form.keepalive_timeout || '00:00:00'),
        session_timeout: convertToMikrotikTime(form.session_timeout || '00:00:00'),
    };
    
    console.log('Submitting form data:', formData);
    
    form.put(route('hotspot.profiles.update', props.profile.id), {
        onSuccess: () => {
            // First emit events
            emit('close');
            emit('updated');
            
            // Then redirect to index page after a short delay to ensure events are processed
            setTimeout(() => {
                window.location.href = route('hotspot.profiles.index');
            }, 100);
        },
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