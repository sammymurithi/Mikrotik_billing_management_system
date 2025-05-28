<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router, usePage, Head } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    vouchers: Object,
    routers: Array,
    selectedRouter: [String, Number, null],
    profiles: Array,
});

const form = ref({
    count: 10,
    profile: '',
    limit_mb: 300,
    router_id: props.selectedRouter || '',
});

const filterRouter = ref(props.selectedRouter || '');
const filterStatus = ref('');
const selectedVouchers = ref([]);
const batchAction = ref('');
const perPage = ref('10');

const profiles = computed(() => props.profiles || []);

function generateVouchers() {
    router.post(route('vouchers.store'), form.value, { preserveScroll: true });
}

function deleteVoucher(id) {
    if (confirm('Delete this voucher?')) {
        router.delete(route('vouchers.destroy', id), { preserveScroll: true });
    }
}

function disableVoucher(id) {
    if (confirm('Disable this voucher?')) {
        router.post(route('vouchers.disable', id), {}, { preserveScroll: true });
    }
}

function applyFilters() {
    const params = {};
    if (filterRouter.value) {
        params.router_id = filterRouter.value;
    }
    if (filterStatus.value) {
        params.status = filterStatus.value;
    }
    router.get(route('vouchers.index'), params, { preserveScroll: true });
}

function handleProfileChange(profile) {
    if (profile) {
        const selectedProfile = profiles.value.find(p => p.name === profile);
        if (selectedProfile) {
            // Set a default limit_mb since it's not in the profile model
            form.value.limit_mb = 300; // Default value
        }
    }
}

function toggleSelectAll(event) {
    if (event.target.checked) {
        selectedVouchers.value = props.vouchers?.data?.map(v => v.id) || [];
    } else {
        selectedVouchers.value = [];
    }
}

function isAllSelected() {
    return props.vouchers?.data?.length > 0 && selectedVouchers.value.length === props.vouchers.data.length;
}

function executeBatchAction() {
    if (!batchAction.value || selectedVouchers.value.length === 0) {
        return;
    }

    if (batchAction.value === 'delete') {
        if (confirm(`Delete ${selectedVouchers.value.length} selected vouchers?`)) {
            router.post(route('vouchers.batch-delete'), {
                ids: selectedVouchers.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedVouchers.value = [];
                }
            });
        }
    } else if (batchAction.value === 'disable') {
        if (confirm(`Disable ${selectedVouchers.value.length} selected vouchers?`)) {
            router.post(route('vouchers.batch-disable'), {
                ids: selectedVouchers.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedVouchers.value = [];
                }
            });
        }
    } else if (batchAction.value === 'print') {
        printSelectedVouchers();
    }
}

function printSelectedVouchers() {
    // Get the selected vouchers data
    const vouchersToPrint = props.vouchers.data.filter(v => selectedVouchers.value.includes(v.id));
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Generate HTML content for printing
    let printContent = `
        <html>
        <head>
            <title>Vouchers</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .voucher-container { display: flex; flex-wrap: wrap; justify-content: center; }
                .voucher {
                    border: 1px solid #ccc;
                    border-radius: 8px;
                    padding: 15px;
                    margin: 10px;
                    width: 250px;
                    text-align: center;
                }
                .voucher-header { font-weight: bold; margin-bottom: 10px; font-size: 16px; }
                .voucher-code { font-size: 18px; font-weight: bold; margin: 15px 0; padding: 10px; background: #f5f5f5; border-radius: 5px; }
                .package-name { margin: 10px 0; font-weight: bold; }
                .instructions { font-style: italic; font-size: 12px; color: #666; margin-top: 15px; }
                @media print {
                    .no-print { display: none; }
                    .page-break { page-break-after: always; }
                }
            </style>
        </head>
        <body>
            <div class="no-print" style="margin-bottom: 20px; text-align: center;">
                <button onclick="window.print()" style="padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Vouchers</button>
            </div>
            <h1 style="text-align: center;">Internet Access Vouchers</h1>
            <div class="voucher-container">`;
    
    // Add each voucher to the print content
    vouchersToPrint.forEach((voucher, index) => {
        // Get profile name (could be object or string)
        const profileName = typeof voucher.profile === 'object' ? voucher.profile.name : voucher.profile;
        
        printContent += `
            <div class="voucher">
                <div class="voucher-header">JTG Networks Voucher</div>
                <div class="package-name">${profileName}</div>
                <div class="voucher-code">${voucher.username}</div>
                <div class="instructions">Use this code as both username and password to access the internet</div>
            </div>`;
        
        // Add page break after every 8 vouchers
        if ((index + 1) % 8 === 0) {
            printContent += '<div class="page-break"></div>';
        }
    });
    
    printContent += `
            </div>
        </body>
        </html>`;
    
    // Write to the new window and print
    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
}

function changePerPage() {
    router.get(route('vouchers.index'), {
        per_page: perPage.value,
        router_id: filterRouter.value || undefined,
        status: filterStatus.value || undefined
    }, {
        preserveState: true,
        replace: true
    });
}

const page = usePage();

function formatDate(dateString) {
    if (!dateString) return '-';
    
    const date = new Date(dateString);
    const now = new Date();
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    
    // Check if date is today
    if (date.toDateString() === now.toDateString()) {
        return 'Today at ' + new Intl.DateTimeFormat('en-US', {
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        }).format(date);
    }
    
    // Check if date is yesterday
    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday at ' + new Intl.DateTimeFormat('en-US', {
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        }).format(date);
    }
    
    // If within the last 7 days, show day of week
    const daysDiff = Math.floor((now - date) / (1000 * 60 * 60 * 24));
    if (daysDiff < 7) {
        return new Intl.DateTimeFormat('en-US', {
            weekday: 'long',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        }).format(date);
    }
    
    // Otherwise, show full date
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    }).format(date);
}
</script>

<template>
    <Head title="Vouchers" />
    
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Vouchers
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    
                    <!-- Generate Vouchers Form -->
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Create New Vouchers</h3>
                    <form @submit.prevent="generateVouchers" class="mb-8">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Count</label>
                                <input type="number" v-model="form.count" min="1" max="100" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile</label>
                                <select v-model="form.profile" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                                    <option v-for="profile in profiles" :key="profile.id" :value="profile.name">
                                        {{ profile.name }} ({{ profile.rate_limit }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router</label>
                                <select v-model="form.router_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                                    <option v-for="router in routers" :key="router.id" :value="router.id">
                                        {{ router.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Limit (MB)</label>
                                <input type="number" v-model="form.limit_mb" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="bg-emerald-500 text-white px-4 py-2 rounded-md hover:bg-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-700 transition-colors duration-200 font-medium shadow-sm hover:shadow">
                                Generate
                            </button>
                        </div>
                    </form>

                    <!-- Vouchers List Section -->
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mt-8 mb-4">Manage Vouchers</h3>
                    
                    <!-- Filtering and Batch Actions -->
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter by Router</label>
                                <select v-model="filterRouter" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                                    <option value="">All Routers</option>
                                    <option v-for="router in routers" :key="router.id" :value="router.id">
                                        {{ router.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter by Status</label>
                                <select v-model="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="used">Used</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                            <div class="mt-6">
                                <button @click="applyFilters" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-800">
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <select v-model="batchAction" @change="executeBatchAction" class="rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                                <option value="">Batch Actions</option>
                                <option value="print">Print Selected</option>
                                <option value="disable">Disable Selected</option>
                                <option value="delete">Delete Selected</option>
                            </select>
                            <button @click="executeBatchAction" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-800 transition-colors duration-200 font-medium shadow-sm hover:shadow" :disabled="!batchAction || !selectedVouchers.length">
                                Execute
                            </button>
                        </div>
                    </div>
                    
                    <!-- Vouchers Table -->
                    <div class="overflow-x-auto max-w-full">
                        <table class="w-full min-w-max divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected()" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Voucher Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Profile</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Router</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">Created</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">Used</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="voucher in vouchers.data" :key="voucher.id" :class="{ 'bg-indigo-50 dark:bg-indigo-900': selectedVouchers.includes(voucher.id) }">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input type="checkbox" v-model="selectedVouchers" :value="voucher.id" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700">
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ voucher.username }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ typeof voucher.profile === 'object' ? voucher.profile.name : voucher.profile }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ voucher.router?.name }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{ 
                                            'bg-emerald-500 text-white dark:bg-emerald-600': voucher.status === 'active',
                                            'bg-amber-500 text-white dark:bg-amber-600': voucher.status === 'used',
                                            'bg-gray-500 text-white dark:bg-gray-600': voucher.status === 'expired'
                                        }">
                                            {{ voucher.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                        {{ formatDate(voucher.created_at) }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                        {{ formatDate(voucher.used_at) }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                        <div class="flex space-x-2">
                                            <button @click="disableVoucher(voucher.id)" class="px-3 py-1 text-sm rounded-md bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 transition-colors duration-200" v-if="voucher.status === 'active'">
                                                Disable
                                            </button>
                                            <button @click="deleteVoucher(voucher.id)" class="px-3 py-1 text-sm rounded-md bg-rose-500 text-white hover:bg-rose-600 dark:bg-rose-600 dark:hover:bg-rose-700 transition-colors duration-200">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="vouchers.data.length === 0">
                                    <td colspan="9" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">No vouchers found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination and Per Page Controls -->
                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Show</span>
                            <select v-model="perPage" @change="changePerPage" class="rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="text-sm text-gray-700 dark:text-gray-300">per page</span>
                        </div>
                        <Pagination :links="vouchers.links" :current-page="vouchers.current_page" :total-pages="vouchers.last_page" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
