<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Banner from '@/Components/Banner.vue';
import StepperComponent from '@/Components/StepperComponent.vue';
import { ref, reactive } from 'vue';
import axios from 'axios';

// Props
const props = defineProps({
    debug: Boolean,
    timestamp: String
});

// Stepper configuration
const steps = [
    { title: 'Add Router', description: 'Basic router connection details' },
    { title: 'Configure Interfaces', description: 'Setup network interfaces' },
    { title: 'Assign IP Address', description: 'Configure IP addressing' },
    { title: 'DNS Settings', description: 'Configure DNS servers' },
    { title: 'Address Pool', description: 'Create IP address pools' },
    { title: 'DHCP Client', description: 'Setup WAN DHCP client' },
    { title: 'DHCP Server', description: 'Configure LAN DHCP server' },
    { title: 'Firewall Rules', description: 'Setup basic firewall rules' },
    { title: 'Hotspot', description: 'Configure captive portal' },
    { title: 'Review', description: 'Review and apply configuration' }
];

const currentStep = ref(0);
const showPassword = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const isLoading = ref(false);

const routerForm = useForm({
    name: '',
    ip_address: '',
    username: '',
    password: '',
    port: '8728',
});

// Submit router creation form
const submitRouterForm = async () => {
    errorMessage.value = '';
    successMessage.value = '';
    isLoading.value = true;
    
    try {
        const response = await routerForm.post(route('routers.store'));
        successMessage.value = 'Router added successfully!';
        currentStep.value++;
    } catch (error) {
        errorMessage.value = error.message || 'Failed to create router';
    } finally {
        isLoading.value = false;
    }
};

const nextStep = () => {
    if (currentStep.value < steps.length - 1) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const handleStepSubmit = () => {
    if (currentStep.value === 0) {
        submitRouterForm();
    } else {
        nextStep();
    }
};
</script>

<template>
    <Head title="Configure Router" />
    
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ currentStep === 0 ? 'Add New Router' : `Configure Router: ${steps[currentStep].title}` }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Debug info if provided -->
                <div v-if="debug" class="bg-blue-50 p-4 mb-4 rounded-lg">
                    <p>Debug mode enabled. Timestamp: {{ timestamp }}</p>
                </div>
                
                <!-- Error Banner -->
                <Banner v-if="errorMessage" type="danger" class="mb-4">
                    <template #title>Error</template>
                    <template #content>
                        <p>{{ errorMessage }}</p>
                    </template>
                </Banner>
                
                <!-- Success Banner -->
                <Banner v-if="successMessage" type="success" class="mb-4">
                    <template #title>Success</template>
                    <template #content>
                        <p>{{ successMessage }}</p>
                    </template>
                </Banner>
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Stepper -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <StepperComponent :steps="steps" :currentStep="currentStep" />
                    </div>
                    
                    <!-- Step Content -->
                    <div class="p-6">
                        <!-- Step 1: Router Details -->
                        <div v-if="currentStep === 0" class="space-y-6">
                            <form @submit.prevent="handleStepSubmit" class="space-y-6">
                                <div>
                                    <InputLabel for="router_name" value="Router Name" />
                                    <TextInput
                                        id="router_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="routerForm.name"
                                        required
                                        autofocus
                                    />
                                    <InputError class="mt-2" :message="routerForm.errors.name" />
                                </div>
                                
                                <div>
                                    <InputLabel for="router_ip" value="IP Address" />
                                    <TextInput
                                        id="router_ip"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="routerForm.ip_address"
                                        required
                                        placeholder="e.g., 192.168.1.1"
                                    />
                                    <InputError class="mt-2" :message="routerForm.errors.ip_address" />
                                </div>
                                
                                <div>
                                    <InputLabel for="router_username" value="Username" />
                                    <TextInput
                                        id="router_username"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="routerForm.username"
                                        required
                                    />
                                    <InputError class="mt-2" :message="routerForm.errors.username" />
                                </div>
                                
                                <div class="relative">
                                    <InputLabel for="router_password" value="Password" />
                                    <TextInput
                                        id="router_password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="mt-1 block w-full"
                                        v-model="routerForm.password"
                                        required
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500 transition-colors duration-200"
                                        style="top: 30px;"
                                        @click="showPassword = !showPassword"
                                    >
                                        <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                    <InputError class="mt-2" :message="routerForm.errors.password" />
                                </div>
                                
                                <div>
                                    <InputLabel for="router_port" value="API Port" />
                                    <TextInput
                                        id="router_port"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="routerForm.port"
                                        required
                                    />
                                    <InputError class="mt-2" :message="routerForm.errors.port" />
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Default port is 8728 for API and 8729 for API-SSL</p>
                                </div>
                                
                                <div class="flex justify-end">
                                    <PrimaryButton :disabled="isLoading" :class="{ 'opacity-25': isLoading }">
                                        <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ isLoading ? 'Connecting...' : 'Connect to Router' }}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Placeholder for other steps -->
                        <div v-if="currentStep > 0" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ steps[currentStep].title }}</h3>
                            <p class="text-gray-500 dark:text-gray-400">{{ steps[currentStep].description }}</p>
                            
                            <div class="flex justify-between mt-8">
                                <PrimaryButton @click="prevStep">Back</PrimaryButton>
                                <PrimaryButton @click="nextStep">Next</PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
