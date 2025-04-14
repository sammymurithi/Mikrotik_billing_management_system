<template>
    <app-layout title="Router Overview">
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
              <h2 class="text-2xl font-bold mb-6">Router Overview</h2>
              <div v-if="router" class="space-y-6">
                <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-lg shadow">
                  <h3 class="text-lg font-semibold mb-4">System Resources</h3>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <p class="text-sm text-gray-600 dark:text-gray-300">Uptime</p>
                      <p class="text-base font-medium">{{ router.uptime }}</p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-600 dark:text-gray-300">CPU Load</p>
                      <p class="text-base font-medium">{{ router.cpu_load }}%</p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-600 dark:text-gray-300">Free Memory</p>
                      <p class="text-base font-medium">{{ formatMemory(router.free_memory) }}</p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-600 dark:text-gray-300">Total Memory</p>
                      <p class="text-base font-medium">{{ formatMemory(router.total_memory) }}</p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else-if="error" class="text-red-600 dark:text-red-400 p-4">
                Error: {{ error }}
              </div>
              <div v-else class="text-gray-600 dark:text-gray-400 p-4">
                Loading router data...
              </div>
            </div>
          </div>
        </div>
      </div>
    </app-layout>
  </template>
  
  <script>
  import AppLayout from '@/Layouts/AppLayout.vue';
  
  export default {
    components: { AppLayout },
    props: ['router', 'error'],
    methods: {
      formatMemory(bytes) {
        const mb = bytes / (1024 * 1024);
        return `${mb.toFixed(2)} MB`;
      },
    },
  };
  </script>