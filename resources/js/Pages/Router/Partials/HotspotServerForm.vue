<script setup>
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    interfaces: {
        type: Array,
        required: true
    },
    formData: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update', 'reset']);
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Hotspot Server Setup</h3>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div>
                <InputLabel for="interface" value="Interface" />
                <select
                    id="interface"
                    v-model="formData.interface"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                >
                    <option v-for="iface in interfaces" :key="iface['.id']" :value="iface['.id']">
                        {{ iface.name }}
                    </option>
                </select>
            </div>

            <div>
                <InputLabel for="addressPool" value="Address Pool" />
                <TextInput
                    id="addressPool"
                    v-model="formData.addressPool"
                    type="text"
                    placeholder="e.g., hotspot-pool"
                    class="mt-1 block w-full"
                />
            </div>

            <div>
                <InputLabel for="profile" value="Profile" />
                <TextInput
                    id="profile"
                    v-model="formData.profile"
                    type="text"
                    placeholder="e.g., default"
                    class="mt-1 block w-full"
                />
            </div>

            <div>
                <InputLabel for="dnsName" value="DNS Name" />
                <TextInput
                    id="dnsName"
                    v-model="formData.dnsName"
                    type="text"
                    placeholder="e.g., hotspot.example.com"
                    class="mt-1 block w-full"
                />
            </div>

            <div>
                <InputLabel for="htmlDirectory" value="HTML Directory" />
                <TextInput
                    id="htmlDirectory"
                    v-model="formData.htmlDirectory"
                    type="text"
                    placeholder="e.g., hotspot"
                    class="mt-1 block w-full"
                />
            </div>

            <div>
                <InputLabel for="loginBy" value="Login By" />
                <select
                    id="loginBy"
                    v-model="formData.loginBy"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                >
                    <option value="http-chap">HTTP-CHAP</option>
                    <option value="http-pap">HTTP-PAP</option>
                    <option value="cookie">Cookie</option>
                    <option value="https">HTTPS</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end space-x-4">
            <PrimaryButton @click="$emit('reset')" class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Reset</span>
            </PrimaryButton>
            <PrimaryButton @click="$emit('update')" class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Apply Changes</span>
            </PrimaryButton>
        </div>
    </div>
</template> 