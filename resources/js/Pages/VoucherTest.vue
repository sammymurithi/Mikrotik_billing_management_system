<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';

defineOptions({
    layout: AppLayout,
});

// Form data
const username = ref('');
const password = ref('');
const loading = ref(false);
const result = ref(null);
const error = ref(null);
const logs = ref([]);

// Test authentication
async function testAuthentication() {
    loading.value = true;
    error.value = null;
    result.value = null;
    
    // Add log entry
    logs.value.push({
        time: new Date().toLocaleTimeString(),
        message: `Attempting authentication with username: ${username.value}`,
        type: 'info'
    });
    
    try {
        // Make the authentication request
        const response = await fetch('/api/captive-portal/authenticate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                username: username.value,
                password: password.value
            })
        });
        
        // Parse the response
        const data = await response.json();
        
        // Log the raw response
        logs.value.push({
            time: new Date().toLocaleTimeString(),
            message: `Raw response: ${JSON.stringify(data)}`,
            type: response.ok ? 'success' : 'error'
        });
        
        // Set the result
        result.value = data;
        
        // Add appropriate log entry
        if (data.success) {
            logs.value.push({
                time: new Date().toLocaleTimeString(),
                message: 'Authentication successful!',
                type: 'success'
            });
        } else {
            logs.value.push({
                time: new Date().toLocaleTimeString(),
                message: `Authentication failed: ${data.message}`,
                type: 'error'
            });
        }
    } catch (err) {
        // Set the error
        error.value = err.message;
        
        // Add error log entry
        logs.value.push({
            time: new Date().toLocaleTimeString(),
            message: `Error: ${err.message}`,
            type: 'error'
        });
    } finally {
        loading.value = false;
    }
}

// Clear logs
function clearLogs() {
    logs.value = [];
}
</script>

<template>
    <Head title="Voucher Authentication Test" />
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Voucher Authentication Test</h1>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Test Form -->
                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Test Authentication</h2>
                        
                        <form @submit.prevent="testAuthentication" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username (Voucher Code)</label>
                                <input 
                                    type="text" 
                                    v-model="username" 
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                                <input 
                                    type="text" 
                                    v-model="password" 
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white"
                                    required
                                >
                            </div>
                            
                            <div>
                                <button 
                                    type="submit" 
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-800"
                                    :disabled="loading"
                                >
                                    {{ loading ? 'Testing...' : 'Test Authentication' }}
                                </button>
                            </div>
                        </form>
                        
                        <!-- Result -->
                        <div v-if="result" class="mt-6 p-4 rounded-md" :class="result.success ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'">
                            <h3 class="font-semibold" :class="result.success ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'">
                                {{ result.success ? 'Success' : 'Failed' }}
                            </h3>
                            <p class="text-sm mt-1" :class="result.success ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                                {{ result.message }}
                            </p>
                            <pre v-if="result.debug_id" class="mt-2 text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto">
                                Debug ID: {{ result.debug_id }}
                            </pre>
                        </div>
                        
                        <!-- Error -->
                        <div v-if="error" class="mt-6 p-4 bg-red-100 dark:bg-red-900/30 rounded-md">
                            <h3 class="font-semibold text-red-800 dark:text-red-300">Error</h3>
                            <p class="text-sm mt-1 text-red-700 dark:text-red-400">{{ error }}</p>
                        </div>
                    </div>
                    
                    <!-- Logs -->
                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Debug Logs</h2>
                            <button 
                                @click="clearLogs" 
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                            >
                                Clear
                            </button>
                        </div>
                        
                        <div class="h-96 overflow-y-auto bg-gray-100 dark:bg-gray-800 p-4 rounded-md">
                            <div v-if="logs.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-4">
                                No logs yet. Test authentication to see logs.
                            </div>
                            
                            <div 
                                v-for="(log, index) in logs" 
                                :key="index" 
                                class="mb-2 text-sm p-2 rounded" 
                                :class="{
                                    'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300': log.type === 'info',
                                    'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300': log.type === 'success',
                                    'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300': log.type === 'error'
                                }"
                            >
                                <div class="font-mono text-xs text-gray-500 dark:text-gray-400">[{{ log.time }}]</div>
                                <div>{{ log.message }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                    <h3 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2">How to use this test page:</h3>
                    <ol class="list-decimal list-inside text-sm text-yellow-700 dark:text-yellow-400 space-y-1">
                        <li>Enter a valid voucher code (username) and password from a voucher you've generated</li>
                        <li>Click "Test Authentication" to send the authentication request</li>
                        <li>View the results and debug logs to diagnose any issues</li>
                        <li>Check the server logs for more detailed information (debug_id will help locate the specific log entries)</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</template>
