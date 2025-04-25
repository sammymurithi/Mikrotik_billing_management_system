<template>
    <select
        :id="id"
        :value="modelValue"
        @input="updateValue"
        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
        :class="{ 'border-red-500 dark:border-red-400': error }"
        v-bind="$attrs"
    >
        <slot></slot>
    </select>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: null
    },
    modelValue: {
        type: [String, Number, Boolean, Object],
        default: ''
    },
    error: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const updateValue = (event) => {
    emit('update:modelValue', event.target.value);
};
</script>
  