<template>
    <Head title="Payments" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Payments</h2>
        </template>

        <!-- Flash Messages -->
        <div v-if="flash?.success" class="mb-4">
            <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ flash.success }}</span>
            </div>
        </div>

        <div v-if="flash?.error" class="mb-4">
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ flash.error }}</span>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Payment Statistics -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Payment Statistics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h4 class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</h4>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">KES {{ stats?.total_revenue || 0 }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h4 class="text-sm text-gray-600 dark:text-gray-400">Total Transactions</h4>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.total_transactions || 0 }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h4 class="text-sm text-gray-600 dark:text-gray-400">Successful</h4>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats?.successful_transactions || 0 }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h4 class="text-sm text-gray-600 dark:text-gray-400">Failed</h4>
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats?.failed_transactions || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Transactions</h3>
                            <button
                                @click="showPaymentModal = true"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                            >
                                New Payment
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Transaction ID
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Phone Number
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="transaction in transactions.data" :key="transaction.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ transaction.transaction_id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            KES {{ transaction.amount }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="{
                                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': transaction.status === 'completed',
                                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': transaction.status === 'failed',
                                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': transaction.status === 'pending'
                                                }"
                                            >
                                                {{ transaction.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ transaction.mpesa_phone_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(transaction.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button
                                                @click="viewTransactionDetails(transaction)"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3"
                                            >
                                                View
                                            </button>
                                            <button
                                                v-if="transaction.status === 'failed'"
                                                @click="retryPayment(transaction)"
                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                            >
                                                Retry
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4" v-if="transactions.links && transactions.links.length > 0">
                            <Pagination 
                                :links="transactions.links"
                                :current-page="transactions.current_page"
                                :total-pages="transactions.last_page"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <Modal :show="showPaymentModal" @close="showPaymentModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Make a Payment
                </h2>

                <form @submit.prevent="submitPayment">
                    <div class="mb-4">
                        <InputLabel for="amount" value="Amount (KES)" />
                        <TextInput
                            id="amount"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.amount"
                            required
                            min="1"
                        />
                        <InputError :message="form.errors.amount" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="phone_number" value="M-Pesa Phone Number" />
                        <TextInput
                            id="phone_number"
                            type="tel"
                            class="mt-1 block w-full"
                            v-model="form.phone_number"
                            required
                            placeholder="e.g., 0797925090"
                            @input="formatPhoneNumber"
                        />
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Enter your M-Pesa phone number starting with 0 (e.g., 0797925090)
                        </p>
                        <InputError :message="form.errors.phone_number" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <SecondaryButton @click="showPaymentModal = false" class="mr-3">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Pay with M-Pesa</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Transaction Details Modal -->
        <Modal :show="showDetailsModal" @close="showDetailsModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Transaction Details
                </h2>

                <div v-if="selectedTransaction" class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaction ID</h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ selectedTransaction.transaction_id }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Amount</h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">KES {{ selectedTransaction.amount }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ selectedTransaction.status }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone Number</h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ selectedTransaction.mpesa_phone_number }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ formatDate(selectedTransaction.created_at) }}</p>
                    </div>
                    <div v-if="selectedTransaction.mpesa_receipt_number">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">M-Pesa Receipt</h3>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ selectedTransaction.mpesa_receipt_number }}</p>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    transactions: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            current_page: 1,
            last_page: 1
        })
    },
    stats: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            total_transactions: 0,
            successful_transactions: 0,
            failed_transactions: 0,
            pending_transactions: 0
        })
    },
    flash: {
        type: Object,
        default: () => ({})
    }
});

const transactions = ref(props.transactions);
const stats = ref(props.stats);
const flash = ref(props.flash);

const showPaymentModal = ref(false);
const showDetailsModal = ref(false);
const selectedTransaction = ref(null);

const form = useForm({
    amount: '',
    phone_number: ''
});

onMounted(() => {
    fetchTransactions();
    fetchStats();
});

const fetchTransactions = () => {
    router.get(route('payments.transactions'), {}, {
        preserveState: true,
        onSuccess: (page) => {
            transactions.value = page.props.transactions;
        }
    });
};

const fetchStats = () => {
    router.get(route('payments.stats'), {}, {
        preserveState: true,
        onSuccess: (page) => {
            stats.value = page.props.stats;
        }
    });
};

const submitPayment = () => {
    form.post(route('payments.initiate'), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            form.reset();
            fetchTransactions();
            fetchStats();
        }
    });
};

const formatPhoneNumber = (event) => {
    let value = event.target.value;
    
    // Remove any non-digit characters
    value = value.replace(/\D/g, '');
    
    // If number starts with +254, replace with 0
    if (value.startsWith('254')) {
        value = '0' + value.substring(3);
    }
    
    // Ensure number starts with 0
    if (value.length > 0 && value[0] !== '0') {
        value = '0' + value;
    }
    
    // Limit to 10 digits
    value = value.substring(0, 10);
    
    form.phone_number = value;
};

const viewTransactionDetails = (transaction) => {
    selectedTransaction.value = transaction;
    showDetailsModal.value = true;
};

const retryPayment = (transaction) => {
    form.amount = transaction.amount;
    form.phone_number = transaction.mpesa_phone_number;
    showPaymentModal.value = true;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-KE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script> 