<script setup>
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">DNS Settings</h3>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div>
                <InputLabel for="servers" value="DNS Servers" />
                <TextInput
                    id="servers"
                    v-model="formData.servers"
                    type="text"
                    placeholder="e.g., 8.8.8.8,8.8.4.4"
                    class="mt-1 block w-full"
                />
            </div>

            <div>
                <InputLabel for="allowRemoteRequests" value="Allow Remote Requests" />
                <select
                    id="allowRemoteRequests"
                    v-model="formData.allowRemoteRequests"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                >
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>

            <div>
                <InputLabel for="maxUdpPacketSize" value="Max UDP Packet Size" />
                <TextInput
                    id="maxUdpPacketSize"
                    v-model="formData.maxUdpPacketSize"
                    type="text"
                    placeholder="e.g., 4096"
                    class="mt-1 block w-full"
                />
            </div>

            <div>
                <InputLabel for="queryServerTimeout" value="Query Server Timeout" />
                <TextInput
                    id="queryServerTimeout"
                    v-model="formData.queryServerTimeout"
                    type="text"
                    placeholder="e.g., 2s"
                    class="mt-1 block w-full"
                />
            </div>

            <div>
                <InputLabel for="queryTotalTimeout" value="Query Total Timeout" />
                <TextInput
                    id="queryTotalTimeout"
                    v-model="formData.queryTotalTimeout"
                    type="text"
                    placeholder="e.g., 10s"
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