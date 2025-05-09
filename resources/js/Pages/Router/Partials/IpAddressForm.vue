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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">IP Addresses</h3>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div>
                <InputLabel for="interface" value="Select Interface" />
                <select
                    id="interface"
                    v-model="formData.interface"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                >
                    <option value="">Select an interface</option>
                    <option v-for="iface in interfaces" :key="iface['.id']" :value="iface['.id']">
                        {{ iface.name }}
                    </option>
                </select>
            </div>

            <div>
                <InputLabel for="address" value="IP Address" />
                <TextInput
                    id="address"
                    v-model="formData.address"
                    type="text"
                    placeholder="e.g., 192.168.88.1/24"
                    class="mt-1 block w-full"
                />
            </div>

            <div>
                <InputLabel for="network" value="Network" />
                <TextInput
                    id="network"
                    v-model="formData.network"
                    type="text"
                    placeholder="e.g., 192.168.88.0/24"
                    class="mt-1 block w-full"
                />
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