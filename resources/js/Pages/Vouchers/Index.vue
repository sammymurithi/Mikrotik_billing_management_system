<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
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
const selectedVouchers = ref([]);
const batchAction = ref('');

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

function filterByRouter() {
    router.get(route('vouchers.index'), { router_id: filterRouter.value }, { preserveScroll: true });
}

function handleProfileChange(profile) {
    if (profile) {
        const selectedProfile = profiles.value.find(p => p.name === profile);
        if (selectedProfile) {
            form.value.limit_mb = selectedProfile.limit_mb;
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

function executeBatchAction() {
    if (!selectedVouchers.value.length) {
        alert('Please select at least one voucher');
        return;
    }

    if (batchAction.value === 'delete') {
        if (confirm('Delete selected vouchers?')) {
            router.post(route('vouchers.batch-delete'), { ids: selectedVouchers.value }, { preserveScroll: true });
        }
    } else if (batchAction.value === 'disable') {
        if (confirm('Disable selected vouchers?')) {
            router.post(route('vouchers.batch-disable'), { ids: selectedVouchers.value }, { preserveScroll: true });
        }
    }
}

const page = usePage();
</script>

<template>
    <AppLayout title="Vouchers">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Vouchers
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Generate Vouchers Form -->
                    <form @submit.prevent="generateVouchers" class="mb-8">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Count</label>
                                <input type="number" v-model="form.count" min="1" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Profile</label>
                                <select v-model="form.profile" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option v-for="profile in profiles" :key="profile.id" :value="profile.name">
                                        {{ profile.name }} ({{ profile.rate_limit }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Router</label>
                                <select v-model="form.router_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option v-for="router in routers" :key="router.id" :value="router.id">
                                        {{ router.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Data Limit (MB)</label>
                                <input type="number" v-model="form.limit_mb" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                Generate Vouchers
                            </button>
                        </div>
                    </form>

                    <!-- Vouchers Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profile</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Router</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Used At</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="voucher in vouchers.data" :key="voucher.id">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ voucher.username }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ voucher.password }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ voucher.profile }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ voucher.router?.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="{
                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                            'bg-green-100 text-green-800': voucher.status === 'active',
                                            'bg-red-100 text-red-800': voucher.status === 'used',
                                            'bg-gray-100 text-gray-800': voucher.status === 'expired'
                                        }">
                                            {{ voucher.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(voucher.created_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ voucher.used_at ? formatDate(voucher.used_at) : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button @click="disableVoucher(voucher)" v-if="voucher.status === 'active'" class="text-red-600 hover:text-red-900 mr-2">
                                            Disable
                                        </button>
                                        <button @click="deleteVoucher(voucher)" class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        <Pagination :links="vouchers.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
