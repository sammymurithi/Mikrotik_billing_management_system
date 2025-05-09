<script setup>
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Toast from '@/Components/Toast.vue';

const { showToast } = useToast();

const props = defineProps({
    interfaces: {
        type: Array,
        required: true
    },
    routerId: {
        type: [String, Number],
        required: true
    },
    showModal: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'showModal']);

const form = useForm({
    type: '',
    name: '',
    enabled: true,
    mtu: '',
    mac_address: '',
    comment: ''
});

const showAdvancedOptions = ref(false);
const editingInterface = ref(null);

const interfaceTypes = [
    { value: 'ether', label: 'Ethernet' },
    { value: 'wlan', label: 'Wireless' },
    { value: 'bridge', label: 'Bridge' },
    { value: 'vlan', label: 'VLAN' },
    { value: 'pppoe', label: 'PPPoE' },
    { value: 'pptp', label: 'PPTP' },
    { value: 'l2tp', label: 'L2TP' },
    { value: 'sstp', label: 'SSTP' },
    { value: 'ovpn', label: 'OpenVPN' },
    { value: 'gre', label: 'GRE' },
    { value: 'ipip', label: 'IPIP' },
    { value: 'eoip', label: 'EoIP' },
    { value: 'ipsec', label: 'IPSec' },
    { value: 'bonding', label: 'Bonding' },
    { value: 'vrrp', label: 'VRRP' },
    { value: 'vxlan', label: 'VXLAN' }
];

const resetForm = () => {
    form.reset();
    showAdvancedOptions.value = false;
    editingInterface.value = null;
};

const handleSubmit = () => {
    if (editingInterface.value) {
        form.put(route('routers.interfaces.update', [props.routerId, editingInterface.value.id]), {
            onSuccess: () => {
                showToast('Interface updated successfully', 'success');
                handleClose();
            },
            onError: (errors) => {
                showToast(Object.values(errors)[0], 'error');
            }
        });
    } else {
        form.post(route('routers.interfaces.store', props.routerId), {
            onSuccess: () => {
                showToast('Interface created successfully', 'success');
                handleClose();
            },
            onError: (errors) => {
                showToast(Object.values(errors)[0], 'error');
            }
        });
    }
};

const handleEdit = (iface) => {
    editingInterface.value = iface;
    form.type = iface.type;
    form.name = iface.name;
    form.enabled = iface.running;
    form.mtu = iface.mtu || '';
    form.mac_address = iface.mac_address || '';
    form.comment = iface.comment || '';
    emit('showModal');
};

const handleDelete = (iface) => {
    if (confirm('Are you sure you want to delete this interface?')) {
        router.delete(route('routers.interfaces.destroy', [props.routerId, iface.id]), {
            onSuccess: () => {
                showToast('Interface deleted successfully', 'success');
            },
            onError: () => {
                showToast('Failed to delete interface', 'error');
            }
        });
    }
};

const handleToggleStatus = (iface) => {
    router.post(route('routers.interfaces.toggle', [props.routerId, iface.id]), {}, {
        onSuccess: () => {
            showToast(`Interface ${iface.running ? 'disabled' : 'enabled'} successfully`, 'success');
        },
        onError: () => {
            showToast('Failed to toggle interface status', 'error');
        }
    });
};

const handleClose = () => {
    emit('close');
    resetForm();
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Network Interfaces</h3>
            </div>
            <button
                @click="emit('showModal')"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Interface
            </button>
        </div>

        <!-- Existing Interfaces Table -->
        <div v-if="interfaces && interfaces.length > 0" class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="iface in interfaces" :key="iface.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                                <span class="font-medium">{{ iface.name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ iface.type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': iface.running,
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': !iface.running
                                }"
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                            >
                                {{ iface.running ? 'Running' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button
                                    @click="handleToggleStatus(iface)"
                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                >
                                    {{ iface.running ? 'Disable' : 'Enable' }}
                                </button>
                                <button
                                    @click="handleEdit(iface)"
                                    class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                                <button
                                    @click="handleDelete(iface)"
                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
            No interfaces found
        </div>

        <!-- New Interface Modal -->
        <Modal :show="showModal" @close="handleClose">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ editingInterface ? 'Edit Interface' : 'Add Interface' }}
                    </h2>
                    <button @click="handleClose" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <InputLabel for="interfaceType" value="Interface Type" />
                        <select
                            id="interfaceType"
                            v-model="form.type"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            :disabled="editingInterface"
                        >
                            <option value="">Select interface type</option>
                            <option v-for="type in interfaceTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel for="name" value="Interface Name" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g., ether1, wlan1"
                        />
                    </div>

                    <div class="flex items-center">
                        <input
                            id="enabled"
                            v-model="form.enabled"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600"
                        />
                        <InputLabel for="enabled" value="Enable Interface" class="ml-2" />
                    </div>

                    <!-- Advanced Options Toggle -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <button
                            type="button"
                            @click="showAdvancedOptions = !showAdvancedOptions"
                            class="flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300"
                        >
                            <svg
                                class="w-4 h-4 mr-1"
                                :class="{ 'transform rotate-180': showAdvancedOptions }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            Advanced Options
                        </button>
                    </div>

                    <!-- Advanced Options -->
                    <div v-if="showAdvancedOptions" class="space-y-4 pt-4">
                        <div>
                            <InputLabel for="mtu" value="MTU" />
                            <TextInput
                                id="mtu"
                                v-model="form.mtu"
                                type="number"
                                class="mt-1 block w-full"
                                placeholder="1500"
                            />
                        </div>
                        <div>
                            <InputLabel for="macAddress" value="MAC Address" />
                            <TextInput
                                id="macAddress"
                                v-model="form.mac_address"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="00:00:00:00:00:00"
                            />
                        </div>
                        <div>
                            <InputLabel for="comment" value="Comment" />
                            <TextInput
                                id="comment"
                                v-model="form.comment"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Optional comment"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 mt-6">
                        <button
                            type="button"
                            @click="handleClose"
                            class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-600 focus:bg-gray-400 dark:focus:bg-gray-600 active:bg-gray-500 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Cancel
                        </button>
                        <PrimaryButton type="submit" class="flex items-center space-x-2" :disabled="form.processing">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ editingInterface ? 'Update Interface' : 'Create Interface' }}</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Toast />
    </div>
</template> 