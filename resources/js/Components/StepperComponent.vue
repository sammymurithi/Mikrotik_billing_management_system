<script setup>
import { computed } from 'vue';

const props = defineProps({
    steps: {
        type: Array,
        required: true
    },
    currentStep: {
        type: Number,
        required: true
    }
});

// Tell Vue to inherit attributes (like class) on the root element
defineOptions({
    inheritAttrs: true
});
</script>

<template>
    <!-- Wrapper div to receive parent classes -->
    <div v-bind="$attrs">
        <!-- Desktop stepper (visible on medium screens and up) -->
        <div class="hidden md:block">
            <!-- Step title and progress indicator -->
            <div class="flex justify-between items-center mb-3">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-800 mr-3 shadow-md">
                    <span class="text-blue-600 dark:text-blue-300 font-medium">{{ currentStep + 1 }}</span>
                </div>
                <h3 class="text-lg font-medium text-blue-600 dark:text-blue-500">{{ steps[currentStep].title }}</h3>
            </div>
            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Step {{ currentStep + 1 }} of {{ steps.length }}</span>
        </div>
        
        <!-- Progress bar -->
        <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700 mb-6 shadow-inner">
            <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" :style="{ width: `${(currentStep / (steps.length - 1)) * 100}%` }"></div>
        </div>
        
        <!-- Step bubbles -->
        <ol class="flex items-start justify-between w-full px-4 relative">
            <!-- Connection line between steps -->
            <div class="absolute top-4 left-0 w-full h-0.5 bg-gray-200 dark:bg-gray-700 -z-10"></div>
            
            <li v-for="(step, index) in steps" :key="index"
                class="flex flex-col items-center mx-1" 
                :style="{ width: `${100/steps.length}%`, maxWidth: '120px' }"
            >
                <div class="flex items-center justify-center w-8 h-8 rounded-full mb-3 shadow-md transition-all duration-200"
                    :class="{
                        'bg-blue-600 dark:bg-blue-700 scale-110': index < currentStep,
                        'bg-blue-100 dark:bg-blue-800 border-2 border-blue-600 scale-125 ring-4 ring-blue-100 dark:ring-blue-900': index === currentStep,
                        'bg-gray-100 dark:bg-gray-700': index > currentStep
                    }">
                    <svg v-if="index < currentStep" class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                    </svg>
                    <span v-else-if="index === currentStep" class="text-blue-600 dark:text-blue-300 font-medium">{{ index + 1 }}</span>
                    <span v-else class="text-gray-500 dark:text-gray-400">{{ index + 1 }}</span>
                </div>
                <span class="text-xs font-medium text-center w-full px-1 py-1 rounded transition-colors duration-200" :class="{
                    'text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/50': index === currentStep,
                    'text-blue-600 dark:text-blue-400': index < currentStep,
                    'text-gray-500 dark:text-gray-400': index > currentStep
                }">{{ step.title }}</span>
            </li>
        </ol>
        </div>
        
        <!-- Mobile stepper (visible on small screens) -->
        <div class="md:hidden mb-4 sm:mb-5">
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-800 mr-2">
                        <span class="text-blue-600 dark:text-blue-300 font-medium">{{ currentStep + 1 }}</span>
                    </div>
                    <span class="text-blue-600 dark:text-blue-500 font-medium">{{ steps[currentStep].title }}</span>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">Step {{ currentStep + 1 }} of {{ steps.length }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: `${(currentStep / (steps.length - 1)) * 100}%` }"></div>
            </div>
        </div>
    </div>
</template>
