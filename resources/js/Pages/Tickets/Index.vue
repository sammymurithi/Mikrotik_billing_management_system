<template>
    <Head title="Support Tickets" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Support Tickets</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Success Message -->
                    <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                    </div>

                    <!-- Filters and Create Ticket Button -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
                        <!-- Filters -->
                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                            <div class="flex items-center">
                                <font-awesome-icon icon="fa-filter" class="mr-2 text-gray-500 dark:text-gray-400" />
                                <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">Filters:</span>
                            </div>
                            
                            <!-- Status Filter -->
                            <div>
                                <select 
                                    v-model="filters.status" 
                                    @change="filterTickets"
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            
                            <!-- Priority Filter -->
                            <div>
                                <select 
                                    v-model="filters.priority" 
                                    @change="filterTickets"
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                                >
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            
                            <!-- Clear Filters Button -->
                            <button 
                                v-if="hasActiveFilters"
                                @click="clearFilters" 
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                            >
                                Clear Filters
                            </button>
                        </div>
                        
                        <!-- Create Ticket Button -->
                        <button 
                            @click="showCreateModal = true" 
                            class="px-4 py-2 bg-indigo-600 dark:bg-indigo-700 text-white rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                        >
                            <font-awesome-icon icon="fa-ticket-alt" class="mr-2" />
                            Create New Ticket
                        </button>
                    </div>

                    <!-- Tickets Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Priority</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="tickets.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No tickets found. Create a new ticket to get started.
                                    </td>
                                </tr>
                                <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        #{{ ticket.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ ticket.title }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span 
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                            :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': ticket.priority === 'low',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': ticket.priority === 'medium',
                                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': ticket.priority === 'high'
                                            }"
                                        >
                                            {{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span 
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                            :class="{
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': ticket.status === 'open',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': ticket.status === 'closed'
                                            }"
                                        >
                                            {{ ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ new Date(ticket.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link 
                                            :href="route('tickets.show', ticket.id)" 
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"
                                        >
                                            View
                                        </Link>
                                        <button 
                                            v-if="ticket.status === 'open'"
                                            @click="confirmClose(ticket)" 
                                            class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300 mr-3"
                                        >
                                            Close
                                        </button>
                                        <button 
                                            @click="confirmDelete(ticket)" 
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        <Pagination 
                            :links="tickets.links" 
                            :current-page="tickets.current_page"
                            :total-pages="tickets.last_page"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Ticket Modal -->
        <CreateTicket 
            :show="showCreateModal" 
            @close="showCreateModal = false" 
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="confirmingDeletion" @close="confirmingDeletion = false">
            <template #title>
                Delete Ticket
            </template>
            <template #content>
                Are you sure you want to delete this ticket? This action cannot be undone.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingDeletion = false">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="deleteTicket"
                >
                    Delete Ticket
                </DangerButton>
            </template>
        </ConfirmationModal>
        
        <!-- Close Ticket Confirmation Modal -->
        <ConfirmationModal :show="confirmingClosure" @close="confirmingClosure = false">
            <template #title>
                Close Ticket
            </template>
            <template #content>
                Are you sure you want to close this ticket? This will mark the issue as resolved.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingClosure = false">
                    Cancel
                </SecondaryButton>

                <button
                    class="ml-3 inline-flex items-center px-4 py-2 bg-yellow-600 dark:bg-yellow-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 dark:hover:bg-yellow-600 focus:bg-yellow-700 dark:focus:bg-yellow-600 active:bg-yellow-900 dark:active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="closeTicket"
                >
                    Close Ticket
                </button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import CreateTicket from './Partials/CreateTicket.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    tickets: Object,
    filters: Object,
});

const showCreateModal = ref(false);
const confirmingDeletion = ref(false);
const confirmingClosure = ref(false);
const ticketToDelete = ref(null);
const ticketToClose = ref(null);

// Set up reactive filters
const filters = ref({
    status: props.filters.status || '',
    priority: props.filters.priority || '',
});

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return filters.value.status || filters.value.priority;
});

const form = useForm({
    status: 'closed',
});

const confirmDelete = (ticket) => {
    ticketToDelete.value = ticket;
    confirmingDeletion.value = true;
};

const deleteTicket = () => {
    form.delete(route('tickets.destroy', ticketToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
        },
    });
};

const confirmClose = (ticket) => {
    ticketToClose.value = ticket;
    confirmingClosure.value = true;
};

const closeTicket = () => {
    // Reset the form to ensure clean data
    form.reset();
    form.status = 'closed';
    
    form.put(route('tickets.update', ticketToClose.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingClosure.value = false;
            // Update the ticket status in the UI immediately
            ticketToClose.value.status = 'closed';
        },
    });
};

// Filter tickets based on selected filters
const filterTickets = () => {
    router.get(route('tickets.index'), {
        status: filters.value.status,
        priority: filters.value.priority,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Clear all active filters
const clearFilters = () => {
    filters.value.status = '';
    filters.value.priority = '';
    filterTickets();
};
</script>
